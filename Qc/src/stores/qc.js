import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api.js'
import imageCompression from 'browser-image-compression'

const MAX_PHOTOS = 20

const COMPRESSION_OPTIONS = {
  maxSizeMB: 0.5,
  maxWidthOrHeight: 1600,
  useWebWorker: true,
  initialQuality: 0.8,
}

async function compressPhoto(file) {
  try {
    return await imageCompression(file, COMPRESSION_OPTIONS)
  } catch (err) {
    console.warn('Kompresi foto gagal, upload file asli:', err)
    return file
  }
}

const STATUS_LABEL = {
  SCHEDULED: 'Terjadwal',
  ARRIVED: 'Tiba',
  WAITING_QC: 'Belum QC',
  QC_IN_PROGRESS: 'Belum QC',
  QC_DONE: 'Sudah QC',
  UNLOADING: 'Bongkar',
  FINISHED: 'Selesai',
}

/**
 * Store untuk layar "Daftar Kendaraan QC" (list per tanggal janji).
 */
export const useQcListStore = defineStore('qcList', () => {
  const loading = ref(false)
  const errorMessage = ref(null)

  const tanggal = ref(new Date().toISOString().slice(0, 10))
  const list = ref([])
  const belumQc = ref(0)
  const sudahQc = ref(0)

  async function fetchList() {
    loading.value = true
    errorMessage.value = null
    try {
      const { data } = await api.get('/qc', { params: { tanggal: tanggal.value } })
      list.value = data.data
      belumQc.value = data.belum_qc
      sudahQc.value = data.sudah_qc
    } catch (err) {
      errorMessage.value = err.response?.data?.message ?? 'Gagal memuat daftar kendaraan.'
    } finally {
      loading.value = false
    }
  }

  function statusLabel(status) {
    return STATUS_LABEL[status] ?? status
  }

  function isDone(status) {
    return status === 'QC_DONE'
  }

  return {
    loading,
    errorMessage,
    tanggal,
    list,
    belumQc,
    sudahQc,
    fetchList,
    statusLabel,
    isDone,
  }
})

/**
 * Store untuk layar "Input Metrik QC" (satu IML pada satu waktu).
 * MC, IMP, dan OT semuanya satu nilai per IML, diisi langsung oleh
 * operator. Foto murni dokumentasi (tidak ada nilai per foto lagi).
 *
 * Dipakai sebagai singleton — panggil initialize(noIml) tiap kali
 * masuk ke IML yang berbeda supaya state di-reset dan data dimuat ulang.
 */
export const useQcInputStore = defineStore('qcInput', () => {
  const currentNoIml = ref(null)

  const loading = ref(false)
  const submitting = ref(false)
  const uploadingPhoto = ref(false)
  const compressingPhoto = ref(false)
  const errorMessage = ref(null)

  const iml = ref(null)

  const mc = ref('')
  const imp = ref('')
  const ot = ref('')

  const photos = computed(() => iml.value?.photos ?? [])
  const photoCount = computed(() => photos.value.length)
  // QC yang sudah selesai dikunci: foto tidak boleh ditambah/dihapus lagi.
  const isLocked = computed(() => iml.value?.status === 'QC_DONE')
  const canAddPhoto = computed(() => photoCount.value < MAX_PHOTOS && !isLocked.value)
  const remainingPhotos = computed(() => MAX_PHOTOS - photoCount.value)

  function resetState() {
    loading.value = false
    submitting.value = false
    uploadingPhoto.value = false
    compressingPhoto.value = false
    errorMessage.value = null
    iml.value = null
    mc.value = ''
    imp.value = ''
    ot.value = ''
  }

  async function initialize(noIml) {
    if (currentNoIml.value === noIml && iml.value) return // sudah dimuat
    currentNoIml.value = noIml
    resetState()
    await fetchLookup()
  }

  async function fetchLookup() {
    loading.value = true
    errorMessage.value = null
    try {
      const { data } = await api.get(`/qc/lookup/${currentNoIml.value}`)
      iml.value = data.data
    } catch (err) {
      errorMessage.value = err.response?.data?.message ?? 'Gagal memuat data IML.'
      iml.value = null
    } finally {
      loading.value = false
    }
  }

  async function uploadPhoto(file) {
    if (isLocked.value) {
      errorMessage.value = 'QC untuk IML ini sudah selesai, foto tidak bisa diubah lagi.'
      return false
    }
    if (!canAddPhoto.value) {
      errorMessage.value = `Maksimal ${MAX_PHOTOS} foto per IML sudah tercapai.`
      return false
    }

    uploadingPhoto.value = true
    errorMessage.value = null

    compressingPhoto.value = true
    const compressedFile = await compressPhoto(file)
    compressingPhoto.value = false

    const formData = new FormData()
    formData.append('photo', compressedFile, file.name)

    try {
      const { data } = await api.post(`/qc/${currentNoIml.value}/photos`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      iml.value.photos.push(data.data)
      return true
    } catch (err) {
      errorMessage.value = err.response?.data?.message ?? 'Gagal mengunggah foto.'
      return false
    } finally {
      uploadingPhoto.value = false
    }
  }

  async function deletePhoto(photoId) {
    if (isLocked.value) {
      errorMessage.value = 'QC untuk IML ini sudah selesai, foto tidak bisa dihapus.'
      return false
    }
    errorMessage.value = null
    try {
      await api.delete(`/qc/${currentNoIml.value}/photos/${photoId}`)
      iml.value.photos = iml.value.photos.filter((p) => p.id !== photoId)
      return true
    } catch (err) {
      errorMessage.value = err.response?.data?.message ?? 'Gagal menghapus foto.'
      return false
    }
  }

  async function submitQc() {
    errorMessage.value = null

    if (photoCount.value === 0) {
      errorMessage.value = 'Minimal 1 foto dokumentasi wajib diunggah.'
      return false
    }
    if (mc.value === '' || imp.value === '' || ot.value === '') {
      errorMessage.value = 'MC, IMP, dan OT wajib diisi.'
      return false
    }

    submitting.value = true
    try {
      const { data } = await api.post(`/qc/${currentNoIml.value}/submit`, {
        mc: mc.value,
        imp: imp.value,
        ot: ot.value,
      })
      iml.value = data.data
      return true
    } catch (err) {
      errorMessage.value = err.response?.data?.message ?? 'Gagal menyimpan QC.'
      return false
    } finally {
      submitting.value = false
    }
  }

  return {
    loading,
    submitting,
    uploadingPhoto,
    compressingPhoto,
    errorMessage,
    iml,
    mc,
    imp,
    ot,
    photos,
    photoCount,
    isLocked,
    canAddPhoto,
    remainingPhotos,
    maxPhotos: MAX_PHOTOS,
    initialize,
    uploadPhoto,
    deletePhoto,
    submitQc,
  }
})
<script setup>
import { onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useQcInputStore } from '../stores/qc.js'
 
const props = defineProps({
  noIml: { type: String, required: true },
})
 
const emit = defineEmits(['back', 'saved'])
 
const store = useQcInputStore()
const {
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
  maxPhotos,
} = storeToRefs(store)
const { uploadPhoto, deletePhoto, submitQc } = store
 
const fileInput = ref(null)
 
onMounted(() => {
  store.initialize(props.noIml)
})
// Kalau operator berpindah IML tanpa unmount komponen (misal lewat
// router-link antar IML), muat ulang data untuk IML yang baru.
watch(() => props.noIml, (newNoIml) => store.initialize(newNoIml))
 
function openCamera() {
  if (!canAddPhoto.value) return
  fileInput.value?.click()
}

// Laravel mengirim tanggal sebagai ISO string (2025-09-02T00:00:00.000000Z).
// Tampilkan cuma bagian tanggalnya, format dd-mm-yyyy.
function formatDate(isoDate) {
  if (!isoDate) return '-'
  const d = new Date(isoDate)
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yyyy = d.getFullYear()
  return `${dd}-${mm}-${yyyy}`
}

async function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (file) await uploadPhoto(file)
  e.target.value = '' // reset supaya bisa pilih file yang sama lagi kalau perlu
}

async function handleDeletePhoto(photoId) {
  if (confirm('Hapus foto ini?')) await deletePhoto(photoId)
}

async function handleSubmit() {
  const ok = await submitQc()
  if (ok) emit('saved', iml.value)
}
</script>

<template>
  <div class="qc-screen">
    <header class="qc-header">
      <button class="btn-back" @click="emit('back')">←</button>
      <h1>INPUT QC MATERIAL</h1>
    </header>

    <div v-if="loading" class="qc-loading">Memuat data...</div>

    <template v-else-if="iml">
      <section class="vehicle-card">
        <div class="vehicle-card-top">
          <div>
            <div class="nopol">{{ iml.no_kendaraan }}</div>
          </div>
          <span class="badge">{{ iml.nama_material }}</span>
        </div>
        <div class="vehicle-card-grid">
          <div>
            <div class="label">Tanggal Janji</div>
            <div class="value">{{ formatDate(iml.tanggal_janji) }}</div>
          </div>
          <div>
            <div class="label">No IML</div>
            <div class="value">{{ iml.no_iml }}</div>
          </div>
        </div>
        <div>
          <div class="label">Vendor</div>
          <div class="value">{{ iml.id_vendor }} - {{ iml.vendor }}</div>
        </div>
        <div>
          <div class="label">Material</div>
          <div class="value">{{ iml.id_material }} – {{ iml.nama_material }}</div>
        </div>
      </section>

      <p v-if="isLocked" class="locked-banner">
        QC untuk IML ini sudah selesai. Data di bawah bersifat baca saja.
      </p>

      <section class="metrics-section">
        <h2>QC METRICS ENTRY</h2>

        <div class="metric-row">
          <div class="metric-label">
            <div class="metric-title">MC (%)</div>
            <div class="metric-sub">Moisture Content</div>
          </div>
          <input
            v-model="mc"
            type="number"
            step="0.1"
            min="0"
            max="100"
            class="metric-input"
            placeholder="0.0"
            :disabled="isLocked"
          />
        </div>

        <div class="metric-row">
          <div class="metric-label">
            <div class="metric-title">IMP (%)</div>
            <div class="metric-sub">Impurity Level</div>
          </div>
          <input
            v-model="imp"
            type="number"
            step="0.1"
            min="0"
            max="100"
            class="metric-input"
            placeholder="0.0"
            :disabled="isLocked"
          />
        </div>

        <div class="metric-row">
          <div class="metric-label">
            <div class="metric-title">OT (%)</div>
            <div class="metric-sub">Other Textile</div>
          </div>
          <input
            v-model="ot"
            type="number"
            step="0.1"
            min="0"
            max="100"
            class="metric-input"
            placeholder="0.0"
            :disabled="isLocked"
          />
        </div>
      </section>

      <section class="evidence-section">
        <div class="evidence-header">
          <h2>EVIDENCE DOCUMENTATION</h2>
          <span class="photo-counter">{{ photoCount }} / {{ maxPhotos }}</span>
        </div>

        <button
          v-if="!isLocked"
          class="btn-take-photo"
          :disabled="!canAddPhoto || uploadingPhoto || compressingPhoto"
          @click="openCamera"
        >
          <span v-if="compressingPhoto">Mengompres foto...</span>
          <span v-else-if="uploadingPhoto">Mengunggah...</span>
          <span v-else>📷 + Ambil Foto</span>
        </button>
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          capture="environment"
          class="hidden-file-input"
          @change="onFileSelected"
        />
        <p v-if="isLocked" class="limit-note">Foto tidak bisa diubah karena QC sudah selesai.</p>
        <p v-else-if="!canAddPhoto" class="limit-note">
          Batas maksimal {{ maxPhotos }} foto sudah tercapai.
        </p>
        <p v-else class="limit-note">Sisa {{ remainingPhotos }} foto lagi.</p>

        <div class="photo-grid">
          <div v-for="(photo, idx) in photos" :key="photo.id" class="photo-item">
            <img :src="photo.url" :alt="`Foto ${idx + 1}`" />
            <button
              v-if="!isLocked"
              class="btn-delete-photo"
              @click="handleDeletePhoto(photo.id)"
            >×</button>
          </div>
        </div>
      </section>

      <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>

      <button
        class="btn-submit"
        :disabled="submitting || isLocked"
        @click="handleSubmit"
      >
        {{ isLocked ? 'QC SUDAH SELESAI' : (submitting ? 'MENYIMPAN...' : '💾 SIMPAN QC') }}
      </button>
    </template>

    <p v-else class="error-message">{{ errorMessage || 'Data tidak ditemukan.' }}</p>
  </div>
</template>

<style scoped>
.qc-screen {
  background: #fdf1f1;
  min-height: 100vh;
  padding: 16px;
  font-family: system-ui, sans-serif;
}

.qc-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.qc-header h1 {
  font-size: 16px;
  font-weight: 700;
  color: #b91c1c;
  letter-spacing: 0.02em;
}
.btn-back {
  background: none;
  border: none;
  font-size: 20px;
  color: #b91c1c;
  cursor: pointer;
}

.qc-loading {
  text-align: center;
  padding: 40px 0;
  color: #888;
}

.locked-banner {
  background: #d1fae5;
  color: #065f46;
  font-size: 12px;
  font-weight: 600;
  padding: 10px 12px;
  border-radius: 10px;
  margin-bottom: 16px;
}

.vehicle-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.vehicle-card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}
.nopol {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}
.badge {
  background: #fde8e8;
  color: #b91c1c;
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 999px;
}
.vehicle-card-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}
.label {
  font-size: 11px;
  color: #9ca3af;
  text-transform: uppercase;
  margin-bottom: 2px;
}
.value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.metrics-section,
.evidence-section {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.metrics-section h2,
.evidence-section h2 {
  font-size: 12px;
  font-weight: 700;
  color: #b91c1c;
  letter-spacing: 0.03em;
  margin-bottom: 12px;
}

.metric-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f3f4f6;
}
.metric-row:last-child {
  border-bottom: none;
}
.metric-title {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
}
.metric-sub {
  font-size: 11px;
  color: #9ca3af;
}
.metric-input,
.metric-select {
  width: 100px;
  text-align: right;
  padding: 8px 10px;
  border: 1px solid #f3d4d4;
  border-radius: 8px;
  background: #fdf1f1;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}
.metric-input:disabled,
.metric-select:disabled {
  opacity: 0.6;
}
.metric-select {
  width: 140px;
  text-align: left;
}

.evidence-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.photo-counter {
  font-size: 12px;
  font-weight: 600;
  color: #b91c1c;
}

.btn-take-photo {
  width: 100%;
  padding: 14px;
  margin-top: 8px;
  border: 2px dashed #f3d4d4;
  border-radius: 10px;
  background: #fdf6f6;
  color: #b91c1c;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
}
.btn-take-photo:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.hidden-file-input {
  display: none;
}
.limit-note {
  font-size: 11px;
  color: #9ca3af;
  margin: 6px 0 12px;
}

.photo-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.photo-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #fce8e8;
  aspect-ratio: 1;
}
.photo-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.btn-delete-photo {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  font-size: 13px;
  line-height: 1;
  cursor: pointer;
}

.error-message {
  color: #b91c1c;
  font-size: 13px;
  background: #fde8e8;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 12px;
}

.btn-submit {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 10px;
  background: #b91c1c;
  color: #fff;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
}
.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
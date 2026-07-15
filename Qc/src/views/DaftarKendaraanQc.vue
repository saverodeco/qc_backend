<script setup>
import { ref } from 'vue'
import { onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useQcListStore } from '../stores/qc.js'

const router = useRouter()
const store = useQcListStore()
const { loading, errorMessage, tanggal, list, belumQc, sudahQc } = storeToRefs(store)
const { fetchList, statusLabel, isDone } = store

onMounted(fetchList)
watch(tanggal, fetchList)

function openQc(noIml) {
  router.push({ name: 'input-metrik-qc', params: { noIml } })
}

// Pencarian IML langsung, tanpa perlu nunggu tanggal janji-nya muncul di
// tabel. Validasi (nomor terdaftar/belum, status QC_DONE, dst) tetap
// ditangani di layar Input Metrik QC lewat store.initialize(), sama
// seperti kalau dibuka dari tombol "QC" di tabel.
const searchNoIml = ref('')

function searchIml() {
  const value = searchNoIml.value.trim()
  if (!value) return
  openQc(value)
  searchNoIml.value = ''
}
</script>

<template>
  <div class="list-screen">
    <header class="list-header">
      <h1>DAFTAR KENDARAAN QC</h1>
    </header>

    <div class="date-card">
      <div>
        <div class="label">Tanggal Inspeksi</div>
        <div class="date-value">{{ tanggal }}</div>
      </div>
      <input v-model="tanggal" type="date" class="date-input" />
    </div>

    <div class="summary-cards">
      <div class="summary-card">
        <div class="summary-icon">🟡</div>
        <div class="summary-label">Belum QC</div>
        <div class="summary-value">{{ String(belumQc).padStart(2, '0') }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-icon">🟢</div>
        <div class="summary-label">Sudah QC</div>
        <div class="summary-value">{{ String(sudahQc).padStart(2, '0') }}</div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-title-row">
        <span class="table-title">Daftar Kendaraan</span>
        <div class="search-box">
          <input
            v-model="searchNoIml"
            type="text"
            placeholder="Cari No IML..."
            @keydown.enter="searchIml"
          />
          <button class="search-btn" @click="searchIml">🔍</button>
        </div>
      </div>

      <div v-if="loading" class="table-loading">Memuat...</div>

      <table v-else class="qc-table">
        <thead>
          <tr>
            <th>IML</th>
            <th>Vendor</th>
            <th>Material</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in list" :key="row.no_iml">
            <td>{{ row.no_iml }}</td>
            <td>{{ row.vendor }}</td>
            <td>{{ row.nama_material }}</td>
            <td>
              <span
                class="status-pill"
                :class="isDone(row.status) ? 'status-done' : 'status-pending'"
              >
                {{ statusLabel(row.status) }}
              </span>
            </td>
            <td>
              <button v-if="isDone(row.status)" class="btn-done" disabled>✓</button>
              <button v-else class="btn-qc" @click="openQc(row.no_iml)">QC</button>
            </td>
          </tr>
          <tr v-if="list.length === 0">
            <td colspan="5" class="empty-row">Tidak ada kendaraan untuk tanggal ini.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="monitor-banner">
      <div>
        <div class="monitor-title">Monitor QC Progress</div>
        <div class="monitor-sub">Lacak efisiensi inspeksi material hari ini.</div>
      </div>
      <span class="monitor-icon">📊</span>
    </div>

    <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
  </div>
</template>

<style scoped>
.list-screen {
  background: #fdf1f1;
  min-height: 100vh;
  padding: 16px;
  font-family: system-ui, sans-serif;
}

.list-header h1 {
  font-size: 16px;
  font-weight: 700;
  color: #b91c1c;
  letter-spacing: 0.02em;
  margin-bottom: 16px;
}

.date-card,
.summary-cards,
.table-card {
  margin-bottom: 16px;
}

.date-card {
  background: #fff;
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.label {
  font-size: 10px;
  color: #b91c1c;
  text-transform: uppercase;
  font-weight: 600;
}
.date-value {
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
  margin-top: 2px;
}
.date-input {
  border: none;
  background: none;
  font-size: 13px;
  color: #b91c1c;
}

.summary-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.summary-card {
  background: #fff;
  border-radius: 12px;
  padding: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.summary-icon {
  font-size: 14px;
}
.summary-label {
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  margin: 4px 0 2px;
}
.summary-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

.table-card {
  background: #fff;
  border-radius: 12px;
  padding: 14px 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.table-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}
.table-title {
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
}
.search-box {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fdf1f1;
  border: 1px solid #f3d4d4;
  border-radius: 8px;
  padding: 4px 4px 4px 10px;
}
.search-box input {
  border: none;
  background: none;
  font-size: 12px;
  color: #1f2937;
  width: 120px;
}
.search-box input:focus {
  outline: none;
}
.search-btn {
  background: #b91c1c;
  color: #fff;
  border: none;
  border-radius: 6px;
  width: 26px;
  height: 26px;
  font-size: 12px;
  cursor: pointer;
  flex-shrink: 0;
}
.table-loading {
  padding: 20px 0;
  text-align: center;
  color: #9ca3af;
}

.qc-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.qc-table th {
  text-align: left;
  color: #b91c1c;
  font-weight: 600;
  padding: 8px 6px;
  border-bottom: 1px solid #f3d4d4;
  background: #fdf1f1;
}
.qc-table td {
  padding: 8px 6px;
  border-bottom: 1px solid #f9fafb;
  color: #1f2937;
}
.empty-row {
  text-align: center;
  color: #9ca3af;
  padding: 16px 0;
}

.status-pill {
  font-size: 10px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 999px;
}
.status-pending {
  background: #fef3c7;
  color: #92400e;
}
.status-done {
  background: #d1fae5;
  color: #065f46;
}

.btn-qc {
  background: #b91c1c;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}
.btn-done {
  background: #d1fae5;
  color: #065f46;
  border: none;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 11px;
}

.monitor-banner {
  background: #b91c1c;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #fff;
}
.monitor-title {
  font-size: 13px;
  font-weight: 700;
}
.monitor-sub {
  font-size: 11px;
  opacity: 0.85;
  margin-top: 2px;
}
.monitor-icon {
  font-size: 20px;
}

.error-message {
  color: #b91c1c;
  font-size: 13px;
  background: #fde8e8;
  padding: 10px 12px;
  border-radius: 8px;
  margin-top: 12px;
}
</style>
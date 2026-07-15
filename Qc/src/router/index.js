import { createRouter, createWebHistory } from 'vue-router'
import DaftarKendaraanQc from '../views/DaftarKendaraanQc.vue'
import InputMetrikQc from '../views/Inputmetrikqc.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/qc',
    },
    {
      path: '/qc',
      name: 'daftar-kendaraan-qc',
      component: DaftarKendaraanQc,
    },
    {
      path: '/qc/:noIml',
      name: 'input-metrik-qc',
      component: InputMetrikQc,
      props: true,
    },
  ],
})

export default router
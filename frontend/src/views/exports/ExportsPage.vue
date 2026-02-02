<script setup lang="ts">
import { ref } from 'vue'
import { Download } from 'lucide-vue-next'

type ExportItem = {
  id: number
  name: string
  description: string
  supportsPdf: boolean
  supportsExcel: boolean
}

const items = ref<ExportItem[]>([
  {
    id: 1,
    name: 'Stock actuel',
    description: 'Liste complète des produits avec quantités et valeur du stock.',
    supportsPdf: true,
    supportsExcel: true
  },
  {
    id: 2,
    name: 'Mouvements',
    description: 'Historique des mouvements (entrées / sorties) pour une période donnée.',
    supportsPdf: true,
    supportsExcel: true
  },
  {
    id: 3,
    name: 'Inventaires',
    description: 'Résultats détaillés des inventaires réalisés.',
    supportsPdf: true,
    supportsExcel: false
  },
  {
    id: 4,
    name: 'Prédictions',
    description: 'Prévisions de demande et risques de rupture.',
    supportsPdf: true,
    supportsExcel: true
  }
])
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Exports</h1>
          <p class="text-sm text-gray-500">
            Export des données clés en PDF ou Excel pour le reporting ou l’archivage.
          </p>
        </div>
      </div>

      <!-- Liste des exports -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="item in items"
          :key="item.id"
          class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex flex-col justify-between"
        >
          <div>
            <h2 class="text-sm font-semibold text-gray-800 mb-1">
              {{ item.name }}
            </h2>
            <p class="text-sm text-gray-500 mb-3">
              {{ item.description }}
            </p>
          </div>

          <div class="flex items-center gap-2 mt-2">
            <button
              v-if="item.supportsPdf"
              class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700"
            >
              <Download class="w-4 h-4" />
              PDF
            </button>
            <button
              v-if="item.supportsExcel"
              class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
            >
              <Download class="w-4 h-4" />
              Excel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


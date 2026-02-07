<script setup lang="ts">
import { ref } from 'vue'
import { Download } from 'lucide-vue-next'
import { apiDownload } from '@/services/api'

const isDownloading = ref<string | null>(null)
const error = ref<string | null>(null)

const doExport = async (type: string, format: string) => {
  isDownloading.value = type + format
  error.value = null
  try {
    const date = new Date().toISOString().slice(0, 10)
    if (type === 'stock' && format === 'csv') {
      await apiDownload('/exports/stock/csv', `stock_${date}.csv`)
    } else if (type === 'movements' && format === 'csv') {
      await apiDownload('/exports/movements/csv', `mouvements_${date}.csv`)
    } else if (type === 'inventories' && format === 'csv') {
      await apiDownload('/exports/inventories/csv', `inventaires_${date}.csv`)
    } else {
      error.value = 'Export non disponible pour cette combinaison.'
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du téléchargement'
  } finally {
    isDownloading.value = null
  }
}

const items = [
  {
    id: 'stock',
    name: 'Stock actuel',
    description: 'Liste complète des produits avec quantités et valeur du stock.',
    supportsExcel: true
  },
  {
    id: 'movements',
    name: 'Mouvements',
    description: 'Historique des mouvements (entrées / sorties).',
    supportsExcel: true
  },
  {
    id: 'inventories',
    name: 'Inventaires',
    description: 'Résultats détaillés des inventaires réalisés.',
    supportsExcel: true
  }
]
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Exports</h1>
          <p class="text-sm text-gray-500">
            Export des données en CSV (compatible Excel) pour le reporting ou l'archivage.
          </p>
        </div>
      </div>

      <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ error }}
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
              v-if="item.supportsExcel"
              :disabled="isDownloading !== null"
              @click="doExport(item.id, 'csv')"
              class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
            >
              <Download class="w-4 h-4" />
              {{ isDownloading === item.id + 'csv' ? 'Téléchargement...' : 'Excel / CSV' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

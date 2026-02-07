<script setup lang="ts">
import { ref, computed } from 'vue'
import { FileText, Download } from 'lucide-vue-next'

type Report = {
  id: number
  name: string
  type: 'inventaire' | 'mouvements' | 'stock' | 'predictions'
  period: string
  format: 'PDF' | 'Excel'
  createdAt: string
}

const reports = ref<Report[]>([
  {
    id: 1,
    name: 'Rapport inventaire annuel 2026',
    type: 'inventaire',
    period: '01/01/2026 - 31/12/2026',
    format: 'PDF',
    createdAt: 'Généré il y a 2 jours'
  },
  {
    id: 2,
    name: 'Rapport mouvements Q1',
    type: 'mouvements',
    period: '01/01/2026 - 31/03/2026',
    format: 'Excel',
    createdAt: 'Généré il y a 5 jours'
  },
  {
    id: 3,
    name: 'Rapport valeur du stock',
    type: 'stock',
    period: 'En temps réel',
    format: 'PDF',
    createdAt: 'Généré aujourd’hui'
  }
])

const getTypeLabel = (type: Report['type']) => {
  if (type === 'inventaire') return 'Inventaires'
  if (type === 'mouvements') return 'Mouvements'
  if (type === 'stock') return 'Stock'
  return 'Prédictions'
}

const page = ref(1)
const pageSize = ref(10)

const totalItems = computed(() => reports.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)))

const paginatedReports = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return reports.value.slice(start, start + pageSize.value)
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Rapports</h1>
          <p class="text-sm text-gray-500">
            Génération et téléchargement des rapports d’inventaire, mouvements, stock et prédictions.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition-colors"
          >
            <FileText class="w-4 h-4" />
            Générer un rapport
          </button>
        </div>
      </div>

      <!-- Table des rapports -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Nom</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Type</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Période</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Format</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Généré</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="report in paginatedReports"
                :key="report.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ report.name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ getTypeLabel(report.type) }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ report.period }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ report.format }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ report.createdAt }}
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <button
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100"
                  >
                    <Download class="w-4 h-4" />
                    Télécharger
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 text-xs text-gray-600">
          <div>
            Affichage
            <select
              v-model.number="pageSize"
              class="mx-1 px-2 py-1 border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"
            >
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
            </select>
            éléments par page
          </div>
          <div class="flex items-center gap-2">
            <button
              class="px-2 py-1 border border-gray-300 rounded-md bg-white disabled:opacity-50"
              :disabled="page === 1"
              @click="page--"
            >
              Précédent
            </button>
            <span>
              Page {{ page }} / {{ totalPages }}
            </span>
            <button
              class="px-2 py-1 border border-gray-300 rounded-md bg-white disabled:opacity-50"
              :disabled="page === totalPages"
              @click="page++"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


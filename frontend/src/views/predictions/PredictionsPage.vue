<script setup lang="ts">
import { ref, computed } from 'vue'
import { TrendingUp, CalendarRange } from 'lucide-vue-next'

type PredictionHorizon = '7' | '30' | '90'

type PredictionRow = {
  id: number
  product: string
  code: string
  currentStock: number
  predictedDemand: number
  risk: 'faible' | 'moyen' | 'élevé'
  recommendation: string
}

const horizon = ref<PredictionHorizon>('30')

const baseData: PredictionRow[] = [
  {
    id: 1,
    product: 'Produit A',
    code: 'PRD-001',
    currentStock: 35,
    predictedDemand: 60,
    risk: 'élevé',
    recommendation: 'Commander 30 unités (risque de rupture)'
  },
  {
    id: 2,
    product: 'Produit B',
    code: 'PRD-045',
    currentStock: 120,
    predictedDemand: 80,
    risk: 'faible',
    recommendation: 'Aucun risque de rupture'
  },
  {
    id: 3,
    product: 'Produit C',
    code: 'PRD-089',
    currentStock: 15,
    predictedDemand: 40,
    risk: 'moyen',
    recommendation: 'Surveiller et préparer une commande'
  }
]

const horizonLabel = computed(() => {
  if (horizon.value === '7') return '7 jours'
  if (horizon.value === '30') return '30 jours'
  return '3 mois'
})

// Données simulées : on ajuste la demande en fonction de l’horizon
const predictions = computed(() =>
  baseData.map((row) => {
    let factor = 1
    if (horizon.value === '7') factor = 0.4
    if (horizon.value === '90') factor = 1.6

    const adjustedDemand = Math.round(row.predictedDemand * factor)

    return {
      ...row,
      predictedDemand: adjustedDemand
    }
  })
)

const getRiskClass = (risk: PredictionRow['risk']) => {
  if (risk === 'élevé') return 'bg-red-100 text-red-800'
  if (risk === 'moyen') return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Prédictions de stock</h1>
          <p class="text-sm text-gray-500">
            Projection de la demande et estimation des risques de rupture selon l’horizon choisi.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg shadow-sm">
            <CalendarRange class="w-4 h-4 text-gray-500" />
            <select
              v-model="horizon"
              class="text-sm border-none focus:ring-0 focus:outline-none bg-transparent"
            >
              <option value="7">7 jours</option>
              <option value="30">30 jours</option>
              <option value="90">3 mois</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Résumé -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3 shadow-sm">
          <div class="p-2 rounded-lg bg-blue-50">
            <TrendingUp class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Horizon sélectionné</p>
            <p class="text-sm font-semibold text-gray-800">{{ horizonLabel }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Algorithme</p>
          <p class="text-sm font-semibold text-gray-800">Moyenne mobile + régression simple (simulé)</p>
          <p class="text-xs text-gray-400 mt-1">
            Passage automatique à un modèle plus avancé lorsque l’historique est suffisant.
          </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Recommandation globale</p>
          <p class="text-sm font-semibold text-gray-800">
            Focus sur les produits en risque « moyen » et « élevé » pour éviter les ruptures.
          </p>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Produit</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Code</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Stock actuel</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">
                  Demande prévue ({{ horizonLabel }})
                </th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Risque</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Recommandation</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="row in predictions"
                :key="row.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ row.product }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ row.code }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap">
                  {{ row.currentStock }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap">
                  {{ row.predictedDemand }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    :class="['px-2 py-1 text-xs font-medium rounded-full', getRiskClass(row.risk)]"
                  >
                    {{ row.risk === 'élevé' ? 'Élevé' : row.risk === 'moyen' ? 'Moyen' : 'Faible' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600">
                  {{ row.recommendation }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>


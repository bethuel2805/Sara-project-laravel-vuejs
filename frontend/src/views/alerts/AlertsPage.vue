<script setup lang="ts">
import { ref, computed } from 'vue'
import { Bell } from 'lucide-vue-next'

type AlertType = 'rupture' | 'seuil' | 'expiration' | 'surstock'

type Alert = {
  id: number
  type: AlertType
  product: string
  message: string
  severity: 'info' | 'warning' | 'critical'
  createdAt: string
}

const filterType = ref<AlertType | 'all'>('all')

const alerts = ref<Alert[]>([
  {
    id: 1,
    type: 'rupture',
    product: 'Produit A - PRD-001',
    message: 'Rupture prévue dans 3 jours selon les prédictions.',
    severity: 'critical',
    createdAt: 'Il y a 2h'
  },
  {
    id: 2,
    type: 'seuil',
    product: 'Produit B - PRD-045',
    message: 'Stock minimum atteint (12 unités restantes).',
    severity: 'warning',
    createdAt: 'Il y a 5h'
  },
  {
    id: 3,
    type: 'expiration',
    product: 'Produit C - PRD-089',
    message: 'Expiration dans 7 jours pour 30 unités.',
    severity: 'warning',
    createdAt: 'Hier'
  },
  {
    id: 4,
    type: 'surstock',
    product: 'Produit D - PRD-123',
    message: 'Niveau de stock supérieur à l’optimal depuis 30 jours.',
    severity: 'info',
    createdAt: 'Il y a 3 jours'
  }
])

const filteredAlerts = computed(() => {
  if (filterType.value === 'all') return alerts.value
  return alerts.value.filter((a) => a.type === filterType.value)
})

const getTypeLabel = (type: AlertType) => {
  if (type === 'rupture') return 'Rupture'
  if (type === 'seuil') return 'Seuil minimum'
  if (type === 'expiration') return 'Expiration'
  return 'Surstock'
}

const getSeverityClass = (severity: Alert['severity']) => {
  if (severity === 'critical') return 'bg-red-100 text-red-800'
  if (severity === 'warning') return 'bg-yellow-100 text-yellow-800'
  return 'bg-blue-100 text-blue-800'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Alertes intelligentes</h1>
          <p class="text-sm text-gray-500">
            Suivi des alertes de rupture, seuils, expiration et surstock.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <select
            v-model="filterType"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="all">Tous les types</option>
            <option value="rupture">Rupture</option>
            <option value="seuil">Seuil minimum</option>
            <option value="expiration">Expiration</option>
            <option value="surstock">Surstock</option>
          </select>
        </div>
      </div>

      <!-- Liste des alertes -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
          <div
            v-for="alert in filteredAlerts"
            :key="alert.id"
            class="flex items-start gap-3 px-4 py-4 hover:bg-gray-50 transition-colors"
          >
            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
              <Bell class="w-5 h-5 text-red-500" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                <div class="flex items-center gap-2 min-w-0">
                  <p class="text-sm font-semibold text-gray-800 truncate">
                    {{ alert.product }}
                  </p>
                  <span
                    class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-gray-100 text-gray-700"
                  >
                    {{ getTypeLabel(alert.type) }}
                  </span>
                </div>
                <span class="text-xs text-gray-400 whitespace-nowrap">
                  {{ alert.createdAt }}
                </span>
              </div>
              <p class="text-sm text-gray-600">
                {{ alert.message }}
              </p>
              <div class="mt-2">
                <span
                  :class="['px-2 py-0.5 text-[11px] font-semibold rounded-full', getSeverityClass(alert.severity)]"
                >
                  {{ alert.severity === 'critical' ? 'Critique' : alert.severity === 'warning' ? 'Avertissement' : 'Info' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


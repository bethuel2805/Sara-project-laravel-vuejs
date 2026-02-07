<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Bell } from 'lucide-vue-next'
import { apiFetch } from '@/services/api'

type AlertType = 'rupture' | 'seuil' | 'expiration' | 'surstock'

type Alert = {
  id: string
  type: AlertType
  product_id: number
  product: string
  message: string
  severity: 'info' | 'warning' | 'critical'
  created_at?: string
}

const filterType = ref<AlertType | 'all'>('all')
const alerts = ref<Alert[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

const loadAlerts = async () => {
  isLoading.value = true
  error.value = null
  try {
    const url = filterType.value !== 'all' ? `/alerts?type=${filterType.value}` : '/alerts'
    const res = await apiFetch(url)
    alerts.value = res.data || []
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des alertes'
    alerts.value = []
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  if (minutes < 60) return minutes <= 1 ? 'À l\'instant' : `Il y a ${minutes} min`
  if (hours < 24) return `Il y a ${hours}h`
  if (days < 7) return days === 1 ? 'Hier' : `Il y a ${days} jours`
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const filteredAlerts = computed(() => alerts.value)

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

watch(filterType, () => loadAlerts())
onMounted(() => loadAlerts())
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Alertes intelligentes</h1>
          <p class="text-sm text-gray-500">
            Suivi des alertes de rupture, seuils et surstock.
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
            <option value="surstock">Surstock</option>
          </select>
        </div>
      </div>

      <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ error }}
      </div>

      <!-- Liste des alertes -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="isLoading && alerts.length === 0" class="p-8 text-center text-gray-500">
          Chargement des alertes...
        </div>
        <div v-else-if="filteredAlerts.length === 0" class="p-8 text-center text-gray-500">
          Aucune alerte pour le moment.
        </div>
        <div v-else class="divide-y divide-gray-100">
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
                  {{ formatDate(alert.created_at) }}
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

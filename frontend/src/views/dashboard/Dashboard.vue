<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import PageHeader from '@/components/widgets/PageHeader.vue'
import PageCard from '@/components/widgets/PageCard.vue'
import { apiFetch } from '@/services/api'
import { TrendingUp, TrendingDown, Package, DollarSign, AlertTriangle, RotateCcw, ArrowUp, ArrowDown } from 'lucide-vue-next'

type DashboardStats = {
  summary: {
    total_products: number
    total_categories: number
    total_users: number
    low_stock_products: number
    out_of_stock_products: number
    total_stock_value: number
  }
  movements: {
    period: string
    total: number
    entries: number
    exits: number
    recent: Array<{
      id: number
      type: string
      quantity: number
      date: string
      product: { name: string; code: string }
      user: { name: string }
    }>
  }
  products: {
    top_by_movements: Array<{
      id: number
      name: string
      code: string
      movements_count: number
    }>
    by_category: Array<{
      id: number
      name: string
      count: number
    }>
  }
  charts: {
    movements_over_time: Array<{
      date: string
      entries: number
      exits: number
    }>
  }
  inventories: {
    recent: Array<{
      id: number
      date: string
      status: string
      user: string
      total_difference: number
    }>
  }
}

const selectedPeriod = ref('month')
const isLoading = ref(false)
const error = ref<string | null>(null)
const stats = ref<DashboardStats | null>(null)

const loadStats = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await apiFetch(`/dashboard/stats?period=${selectedPeriod.value}`)
    stats.value = data
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des statistiques'
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateShort = (date: string) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}

const getMovementTypeColor = (type: string) => {
  return type === 'entree' 
    ? 'text-green-600 bg-green-50 border-green-200' 
    : 'text-red-600 bg-red-50 border-red-200'
}

const getInventoryStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: 'Brouillon',
    completed: 'Terminé',
    archived: 'Archivé'
  }
  return labels[status] || status
}

const getInventoryStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    completed: 'bg-green-100 text-green-800',
    archived: 'bg-blue-100 text-blue-800'
  }
  return colors[status] || colors.draft
}

const computedStats = computed(() => {
  if (!stats.value) return null

  return [
    {
      title: 'Total Produits',
      value: stats.value.summary.total_products.toLocaleString('fr-FR'),
      change: null,
      trend: null,
      icon: Package,
      color: 'blue'
    },
    {
      title: 'Valeur du Stock',
      value: formatCurrency(stats.value.summary.total_stock_value),
      change: null,
      trend: null,
      icon: DollarSign,
      color: 'green'
    },
    {
      title: 'Produits en Rupture',
      value: stats.value.summary.out_of_stock_products.toString(),
      change: null,
      trend: 'down',
      icon: AlertTriangle,
      color: 'red'
    },
    {
      title: 'Stock Faible',
      value: stats.value.summary.low_stock_products.toString(),
      change: null,
      trend: 'down',
      icon: RotateCcw,
      color: 'yellow'
    }
  ]
})

const chartData = computed(() => {
  if (!stats.value) return []
  return stats.value.charts.movements_over_time.map(item => ({
    date: formatDateShort(item.date),
    entries: item.entries,
    exits: item.exits
  }))
})

onMounted(() => {
  loadStats()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <PageHeader
        title="Tableau de Bord - Gestion de Stock"
        subtitle="Vue d'ensemble de votre inventaire, des mouvements et des statistiques."
      >
        <template #actions>
          <select
            v-model="selectedPeriod"
            @change="loadStats"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="day">Aujourd'hui</option>
            <option value="week">7 derniers jours</option>
            <option value="month">30 derniers jours</option>
            <option value="year">Cette année</option>
          </select>
        </template>
      </PageHeader>

      <!-- Error message -->
      <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ error }}
      </div>

      <!-- Loading state -->
      <div v-if="isLoading && !stats" class="text-center py-12 text-gray-500">
        Chargement des statistiques...
      </div>

      <!-- Main Content -->
      <main v-else-if="stats">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <PageCard
            v-for="stat in computedStats"
            :key="stat.title"
          >
            <div class="p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-4">
                <div :class="['p-3 rounded-lg', `bg-${stat.color}-50`]">
                  <component :is="stat.icon" :class="['w-6 h-6', `text-${stat.color}-600`]" />
                </div>
                <span
                  v-if="stat.change"
                  :class="[
                    'text-sm font-medium px-2 py-1 rounded flex items-center gap-1',
                    stat.trend === 'up' ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'
                  ]"
                >
                  <TrendingUp v-if="stat.trend === 'up'" class="w-3 h-3" />
                  <TrendingDown v-else class="w-3 h-3" />
                  {{ stat.change }}
                </span>
              </div>
              <h3 class="text-gray-500 text-sm font-medium mb-1">{{ stat.title }}</h3>
              <p class="text-2xl font-bold text-gray-800">{{ stat.value }}</p>
            </div>
          </PageCard>
        </div>

        <!-- Charts and Activities Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <!-- Movements Chart -->
          <PageCard class="lg:col-span-2">
            <div class="p-6">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Mouvements de Stock</h2>
                <div class="flex items-center gap-4 text-sm text-gray-600">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                    <span>Entrées</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded"></div>
                    <span>Sorties</span>
                  </div>
                </div>
              </div>
              <!-- Simple Bar Chart -->
              <div v-if="chartData.length > 0" class="h-64 flex items-end justify-between gap-2">
                <div
                  v-for="(item, index) in chartData"
                  :key="index"
                  class="flex-1 flex flex-col items-center gap-2"
                >
                  <div class="w-full flex flex-col items-center gap-1 justify-end" style="height: 200px;">
                    <div
                      class="w-full bg-green-500 rounded-t transition-all"
                      :style="{
                        height: (() => {
                          const maxValue = Math.max(...chartData.map(d => Math.max(d.entries || 0, d.exits || 0)));
                          return maxValue > 0 ? `${Math.max(5, (item.entries / maxValue * 100))}%` : '5%';
                        })()
                      }"
                    ></div>
                    <div
                      class="w-full bg-red-500 rounded-t transition-all"
                      :style="{
                        height: (() => {
                          const maxValue = Math.max(...chartData.map(d => Math.max(d.entries || 0, d.exits || 0)));
                          return maxValue > 0 ? `${Math.max(5, (item.exits / maxValue * 100))}%` : '5%';
                        })()
                      }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-500 text-center">{{ item.date }}</span>
                </div>
              </div>
              <div v-else class="h-64 flex items-center justify-center text-gray-400 text-sm">
                Aucune donnée disponible
              </div>
              <div class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-3 gap-4 text-center">
                <div>
                  <div class="text-2xl font-bold text-gray-800">{{ stats.movements.total }}</div>
                  <div class="text-xs text-gray-500">Total mouvements</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-green-600">{{ stats.movements.entries }}</div>
                  <div class="text-xs text-gray-500">Entrées</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-red-600">{{ stats.movements.exits }}</div>
                  <div class="text-xs text-gray-500">Sorties</div>
                </div>
              </div>
            </div>
          </PageCard>

          <!-- Recent Movements -->
          <PageCard>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-800 mb-6">Mouvements récents</h2>
              <div class="space-y-4">
                <div
                  v-for="movement in stats.movements.recent.slice(0, 5)"
                  :key="movement.id"
                  class="flex items-start space-x-3 pb-4 border-b border-gray-100 last:border-0"
                >
                  <div :class="['p-2 rounded-lg', getMovementTypeColor(movement.type)]">
                    <ArrowUp v-if="movement.type === 'entree'" class="w-4 h-4" />
                    <ArrowDown v-else class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ movement.product.name }}</p>
                    <p class="text-xs text-gray-500">{{ movement.product.code }}</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="text-xs font-medium text-gray-600">{{ movement.quantity }} unités</span>
                      <span class="text-xs text-gray-400">•</span>
                      <span class="text-xs text-gray-500">{{ movement.user.name }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ formatDate(movement.date) }}</p>
                  </div>
                </div>
              </div>
              <button class="w-full mt-4 text-sm text-blue-600 hover:text-blue-700 font-medium">
                Voir tout →
              </button>
            </div>
          </PageCard>
        </div>

        <!-- Bottom Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Top Products by Movements -->
          <PageCard>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-800 mb-6">Produits les plus actifs</h2>
              <div class="space-y-3">
                <div
                  v-for="(product, index) in stats.products.top_by_movements"
                  :key="product.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-sm">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-800">{{ product.name }}</p>
                      <p class="text-xs text-gray-500">{{ product.code }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-gray-800">{{ product.movements_count }}</p>
                    <p class="text-xs text-gray-500">mouvements</p>
                  </div>
                </div>
              </div>
            </div>
          </PageCard>

          <!-- Products by Category -->
          <PageCard>
            <div class="p-6">
              <h2 class="text-lg font-semibold text-gray-800 mb-6">Produits par catégorie</h2>
              <div class="space-y-3">
                <div
                  v-for="category in stats.products.by_category"
                  :key="category.id"
                  class="flex items-center justify-between"
                >
                  <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-sm font-medium text-gray-800">{{ category.name }}</span>
                      <span class="text-sm text-gray-600">{{ category.count }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div
                        class="bg-blue-500 h-2 rounded-full"
                        :style="{ width: `${(category.count / Math.max(...stats.products.by_category.map(c => c.count))) * 100}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </PageCard>
        </div>

        <!-- Recent Inventories -->
        <PageCard class="mt-6">
          <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">Inventaires récents</h2>
            <div v-if="stats.inventories.recent.length === 0" class="text-center py-8 text-gray-500 text-sm">
              Aucun inventaire récent
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Créé par</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Écart global</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr
                    v-for="inventory in stats.inventories.recent"
                    :key="inventory.id"
                    class="hover:bg-gray-50"
                  >
                    <td class="px-4 py-3 text-gray-800">{{ formatDate(inventory.date) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ inventory.user }}</td>
                    <td class="px-4 py-3">
                      <span
                        :class="['px-2 py-1 text-xs font-medium rounded-full', getInventoryStatusColor(inventory.status)]"
                      >
                        {{ getInventoryStatusLabel(inventory.status) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <span
                        :class="[
                          'px-2 py-1 text-xs font-medium rounded-full',
                          inventory.total_difference < 0
                            ? 'bg-red-100 text-red-800'
                            : inventory.total_difference > 0
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-green-100 text-green-800'
                        ]"
                      >
                        {{ inventory.total_difference }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </PageCard>
      </main>
    </div>
  </div>
</template>

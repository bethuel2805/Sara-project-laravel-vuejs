<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Plus, X, Check, Trash2, Edit } from 'lucide-vue-next'
import { apiFetch } from '@/services/api'
import { useAuth } from '@/composables/useAuth'

const { canManageInventories } = useAuth()

type InventoryStatus = 'draft' | 'completed' | 'archived'

type InventoryItem = {
  id: number
  product_id: number
  product: {
    id: number
    name: string
    code: string
  }
  expected_quantity: number
  actual_quantity: number
  difference: number
  notes?: string
}

type Inventory = {
  id: number
  date: string
  user_id: number
  user?: {
    id: number
    name: string
  }
  status: InventoryStatus
  notes?: string
  items?: InventoryItem[]
  total_difference?: number
}

type Product = {
  id: number
  name: string
  code: string
  stock: number
}

const inventories = ref<Inventory[]>([])
const products = ref<Product[]>([])
const selectedInventory = ref<Inventory | null>(null)
const isModalOpen = ref(false)
const isItemModalOpen = ref(false)
const isEditingItem = ref(false)
const filterStatus = ref<InventoryStatus | 'all'>('all')
const isLoading = ref(false)
const error = ref<string | null>(null)

const newInventory = ref({
  date: new Date().toISOString().slice(0, 16),
  notes: ''
})

const newItem = ref({
  product_id: 0,
  actual_quantity: 0,
  notes: ''
})

const editingItemId = ref<number | null>(null)

const filteredInventories = computed(() => {
  if (filterStatus.value === 'all') return inventories.value
  return inventories.value.filter((inv) => inv.status === filterStatus.value)
})

const page = ref(1)
const pageSize = ref(10)

const totalItems = computed(() => filteredInventories.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)))

const paginatedInventories = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return filteredInventories.value.slice(start, start + pageSize.value)
})

const getStatusLabel = (status: InventoryStatus) => {
  if (status === 'draft') return 'Brouillon'
  if (status === 'completed') return 'Terminé'
  return 'Archivé'
}

const getStatusClass = (status: InventoryStatus) => {
  if (status === 'draft') return 'bg-gray-100 text-gray-800'
  if (status === 'completed') return 'bg-green-100 text-green-800'
  return 'bg-blue-100 text-blue-800'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadInventories = async () => {
  isLoading.value = true
  error.value = null
  try {
    const statusParam = filterStatus.value === 'all' ? '' : `status=${filterStatus.value}`
    const url = statusParam ? `/inventories?${statusParam}` : '/inventories'
    const response = await apiFetch(url)
    inventories.value = response.data || response
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des inventaires'
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

const loadProducts = async () => {
  try {
    const response = await apiFetch('/products')
    products.value = response.data || response
  } catch (err) {
    console.error('Erreur lors du chargement des produits:', err)
  }
}

const loadInventoryDetails = async (id: number) => {
  try {
    const inventory = await apiFetch(`/inventories/${id}`)
    selectedInventory.value = inventory
  } catch (err) {
    console.error('Erreur lors du chargement des détails:', err)
  }
}

const openModal = () => {
  isModalOpen.value = true
  newInventory.value = {
    date: new Date().toISOString().slice(0, 16),
    notes: ''
  }
}

const closeModal = () => {
  isModalOpen.value = false
}

const openItemModal = async (inventory: Inventory) => {
  selectedInventory.value = inventory
  await loadInventoryDetails(inventory.id)
  isItemModalOpen.value = true
  newItem.value = {
    product_id: 0,
    actual_quantity: 0,
    notes: ''
  }
  isEditingItem.value = false
}

const closeItemModal = () => {
  isItemModalOpen.value = false
  selectedInventory.value = null
  editingItemId.value = null
}

const openEditItemModal = async (inventory: Inventory, item: InventoryItem) => {
  selectedInventory.value = inventory
  await loadInventoryDetails(inventory.id)
  isItemModalOpen.value = true
  isEditingItem.value = true
  editingItemId.value = item.id
  newItem.value = {
    product_id: item.product_id,
    actual_quantity: item.actual_quantity,
    notes: item.notes || ''
  }
}

const saveInventory = async () => {
  isLoading.value = true
  error.value = null
  try {
    await apiFetch('/inventories', {
      method: 'POST',
      body: JSON.stringify({
        date: newInventory.value.date,
        notes: newInventory.value.notes || null
      })
    })
    await loadInventories()
    closeModal()
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la création'
  } finally {
    isLoading.value = false
  }
}

const saveItem = async () => {
  if (!selectedInventory.value) return

  isLoading.value = true
  error.value = null
  try {
    if (isEditingItem.value && editingItemId.value) {
      await apiFetch(`/inventories/${selectedInventory.value.id}/items/${editingItemId.value}`, {
        method: 'PUT',
        body: JSON.stringify({
          actual_quantity: newItem.value.actual_quantity,
          notes: newItem.value.notes || null
        })
      })
    } else {
      await apiFetch(`/inventories/${selectedInventory.value.id}/items`, {
        method: 'POST',
        body: JSON.stringify({
          product_id: newItem.value.product_id,
          actual_quantity: newItem.value.actual_quantity,
          notes: newItem.value.notes || null
        })
      })
    }
    await loadInventoryDetails(selectedInventory.value.id)
    newItem.value = {
      product_id: 0,
      actual_quantity: 0,
      notes: ''
    }
    isEditingItem.value = false
    editingItemId.value = null
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de l\'enregistrement'
  } finally {
    isLoading.value = false
  }
}

const deleteItem = async (itemId: number) => {
  if (!selectedInventory.value) return
  if (!confirm('Supprimer cet élément de l\'inventaire ?')) return

  isLoading.value = true
  try {
    await apiFetch(`/inventories/${selectedInventory.value.id}/items/${itemId}`, {
      method: 'DELETE'
    })
    await loadInventoryDetails(selectedInventory.value.id)
    await loadInventories()
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la suppression'
  } finally {
    isLoading.value = false
  }
}

const completeInventory = async (id: number) => {
  if (!confirm('Finaliser cet inventaire ? Le stock sera automatiquement ajusté.')) return

  isLoading.value = true
  error.value = null
  try {
    await apiFetch(`/inventories/${id}/complete`, {
      method: 'POST'
    })
    await loadInventories()
    if (selectedInventory.value?.id === id) {
      await loadInventoryDetails(id)
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la finalisation'
  } finally {
    isLoading.value = false
  }
}

const deleteInventory = async (id: number) => {
  if (!confirm('Supprimer cet inventaire ?')) return

  isLoading.value = true
  try {
    await apiFetch(`/inventories/${id}`, {
      method: 'DELETE'
    })
    await loadInventories()
    if (selectedInventory.value?.id === id) {
      closeItemModal()
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la suppression'
  } finally {
    isLoading.value = false
  }
}

const getProductName = (productId: number) => {
  const product = products.value.find(p => p.id === productId)
  return product ? `${product.name} (${product.code})` : 'Produit inconnu'
}

watch(filterStatus, () => {
  loadInventories()
})

onMounted(() => {
  loadInventories()
  loadProducts()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Inventaires</h1>
          <p class="text-sm text-gray-500">
            Suivi des inventaires physiques, écarts constatés et archivage.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <select
            v-model="filterStatus"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="all">Tous les statuts</option>
            <option value="draft">Brouillon</option>
            <option value="completed">Terminé</option>
            <option value="archived">Archivé</option>
          </select>
          <button
            v-if="canManageInventories"
            @click="openModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition-colors"
          >
            <Plus class="w-4 h-4" />
            Nouvel inventaire
          </button>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ error }}
      </div>

      <!-- Loading state -->
      <div v-if="isLoading && inventories.length === 0" class="text-center py-12 text-gray-500">
        Chargement...
      </div>

      <!-- Table -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Date</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Créé par</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Statut</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Écart global</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="inventory in paginatedInventories"
                :key="inventory.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ formatDate(inventory.date) }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ inventory.user?.name || 'N/A' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusClass(inventory.status)]"
                  >
                    {{ getStatusLabel(inventory.status) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <span
                    :class="[
                      'px-2 py-1 text-xs font-medium rounded-full',
                      (inventory.total_difference || 0) < 0
                        ? 'bg-red-100 text-red-800'
                        : (inventory.total_difference || 0) > 0
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ inventory.total_difference || 0 }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openItemModal(inventory)"
                      class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
                      :title="canManageInventories ? 'Gérer les produits' : 'Voir les détails'"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <template v-if="canManageInventories">
                      <button
                        v-if="inventory.status === 'draft'"
                        @click="completeInventory(inventory.id)"
                        class="p-1.5 text-green-600 hover:bg-green-50 rounded"
                        title="Finaliser"
                      >
                        <Check class="w-4 h-4" />
                      </button>
                      <button
                        v-if="inventory.status === 'draft'"
                        @click="deleteInventory(inventory.id)"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded"
                        title="Supprimer"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </template>
                  </div>
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

    <!-- Modal: Create Inventory -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 relative">
        <button
          @click="closeModal"
          class="absolute right-4 top-4 text-gray-400 hover:text-gray-600"
        >
          <X class="w-5 h-5" />
        </button>

        <h2 class="text-lg font-semibold text-gray-800 mb-1">Nouvel inventaire</h2>
        <p class="text-sm text-gray-500 mb-4">
          Créer une nouvelle campagne d'inventaire physique.
        </p>

        <form class="space-y-4" @submit.prevent="saveInventory">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
            <input
              v-model="newInventory.date"
              type="datetime-local"
              required
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optionnel)</label>
            <textarea
              v-model="newInventory.notes"
              rows="3"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="isLoading"
              class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-50"
            >
              {{ isLoading ? 'Création...' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Manage Items -->
    <div
      v-if="isItemModalOpen && selectedInventory"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-gray-800">
                {{ isEditingItem ? 'Modifier un produit' : 'Ajouter un produit' }}
              </h2>
              <p class="text-sm text-gray-500">
                Inventaire du {{ formatDate(selectedInventory.date) }}
              </p>
            </div>
            <button
              @click="closeItemModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
          <!-- Form: Add/Edit Item -->
          <div v-if="selectedInventory.status === 'draft' && canManageInventories" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form @submit.prevent="saveItem" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Produit</label>
                  <select
                    v-model.number="newItem.product_id"
                    :disabled="isEditingItem"
                    required
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
                  >
                    <option :value="0">Sélectionner un produit</option>
                    <option
                      v-for="product in products"
                      :key="product.id"
                      :value="product.id"
                      :disabled="selectedInventory.items?.some(item => item.product_id === product.id && item.id !== editingItemId)"
                    >
                      {{ product.name }} ({{ product.code }}) - Stock: {{ product.stock }}
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Quantité constatée</label>
                  <input
                    v-model.number="newItem.actual_quantity"
                    type="number"
                    min="0"
                    required
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optionnel)</label>
                  <input
                    v-model="newItem.notes"
                    type="text"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  />
                </div>
              </div>
              <div class="flex justify-end gap-3">
                <button
                  v-if="isEditingItem"
                  type="button"
                  @click="closeItemModal"
                  class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="isLoading || newItem.product_id === 0"
                  class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-50"
                >
                  {{ isLoading ? 'Enregistrement...' : (isEditingItem ? 'Modifier' : 'Ajouter') }}
                </button>
              </div>
            </form>
          </div>

          <!-- Items List -->
          <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Produits inventoriés ({{ selectedInventory.items?.length || 0 }})</h3>
            <div v-if="!selectedInventory.items || selectedInventory.items.length === 0" class="text-sm text-gray-500 text-center py-8">
              Aucun produit ajouté
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Produit</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600">Attendu</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600">Constaté</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600">Écart</th>
                    <th v-if="selectedInventory.status === 'draft' && canManageInventories" class="px-3 py-2 text-right font-semibold text-gray-600">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="item in selectedInventory.items" :key="item.id">
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-800">{{ item.product.name }}</div>
                      <div class="text-xs text-gray-500">{{ item.product.code }}</div>
                    </td>
                    <td class="px-3 py-2 text-right text-gray-600">{{ item.expected_quantity }}</td>
                    <td class="px-3 py-2 text-right text-gray-800 font-medium">{{ item.actual_quantity }}</td>
                    <td class="px-3 py-2 text-right">
                      <span
                        :class="[
                          'px-2 py-1 text-xs font-medium rounded-full',
                          item.difference < 0
                            ? 'bg-red-100 text-red-800'
                            : item.difference > 0
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-green-100 text-green-800'
                        ]"
                      >
                        {{ item.difference > 0 ? '+' : '' }}{{ item.difference }}
                      </span>
                    </td>
                    <td v-if="selectedInventory.status === 'draft' && canManageInventories" class="px-3 py-2 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          @click="openEditItemModal(selectedInventory, item)"
                          class="p-1 text-blue-600 hover:bg-blue-50 rounded"
                          title="Modifier"
                        >
                          <Edit class="w-3.5 h-3.5" />
                        </button>
                        <button
                          @click="deleteItem(item.id)"
                          class="p-1 text-red-600 hover:bg-red-50 rounded"
                          title="Supprimer"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="p-6 border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            Écart total: <span class="font-semibold">{{ selectedInventory.total_difference || 0 }}</span>
          </div>
          <div class="flex gap-3">
            <button
              v-if="canManageInventories && selectedInventory.status === 'draft' && selectedInventory.items && selectedInventory.items.length > 0"
              @click="completeInventory(selectedInventory.id)"
              class="px-4 py-2 text-sm rounded-lg bg-green-600 text-white font-medium hover:bg-green-700"
            >
              Finaliser l'inventaire
            </button>
            <button
              @click="closeItemModal"
              class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

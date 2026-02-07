<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Plus, X, Trash2 } from 'lucide-vue-next'
import { apiFetch, API_BASE_URL } from '@/services/api'
import { useAuth } from '@/composables/useAuth'

const { canManageMovements } = useAuth()

type MovementType = 'entree' | 'sortie'

type Movement = {
  id: number
  product_id: number
  product: {
    id: number
    name: string
    code: string
  }
  type: MovementType
  movement_type: string
  quantity: number
  reason: string | null
  user: {
    id: number
    name: string
    email: string
  }
  date: string
}

type Product = {
  id: number
  name: string
  code: string
  stock: number
}

const isModalOpen = ref(false)
const isLoading = ref(false)
const filterType = ref<MovementType | 'all'>('all')
const movements = ref<Movement[]>([])
const products = ref<Product[]>([])
const errorMessage = ref('')
const successMessage = ref('')

const page = ref(1)
const pageSize = ref(15)
const totalPages = ref(1)
const totalItems = ref(0)

const newMovement = ref({
  product_id: '',
  type: 'entree' as MovementType,
  movement_type: 'achat',
  quantity: 1,
  reason: '',
  date: new Date().toISOString().slice(0, 16).replace('T', ' ')
})

// Types de mouvement selon le type principal
const entryTypes = [
  { value: 'achat', label: 'Achat' },
  { value: 'retour', label: 'Retour' },
  { value: 'correction', label: 'Correction' }
]

const exitTypes = [
  { value: 'vente', label: 'Vente' },
  { value: 'perte', label: 'Perte' },
  { value: 'casse', label: 'Casse' },
  { value: 'expiration', label: 'Expiration' }
]

const movementTypes = computed(() => {
  return newMovement.value.type === 'entree' ? entryTypes : exitTypes
})

// Charger les produits
const loadProducts = async () => {
  try {
    const data = await apiFetch('/products')
    products.value = data
  } catch (error: any) {
    console.error('Erreur lors du chargement des produits:', error)
  }
}

// Charger les mouvements
const loadMovements = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams({
      per_page: pageSize.value.toString(),
      page: page.value.toString()
    })

    if (filterType.value !== 'all') {
      params.append('type', filterType.value)
    }

    const data = await apiFetch(`/movements?${params.toString()}`)
    movements.value = data.data || []
    totalItems.value = data.total || 0
    totalPages.value = data.last_page || 1
  } catch (error: any) {
    errorMessage.value = error.message || 'Erreur lors du chargement des mouvements'
    console.error('Erreur:', error)
  } finally {
    isLoading.value = false
  }
}

// Créer un mouvement
const saveMovement = async () => {
  if (!newMovement.value.product_id || newMovement.value.quantity <= 0) {
    errorMessage.value = 'Veuillez remplir tous les champs requis'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      product_id: parseInt(newMovement.value.product_id),
      type: newMovement.value.type,
      movement_type: newMovement.value.movement_type,
      quantity: newMovement.value.quantity,
      reason: newMovement.value.reason || null,
      date: newMovement.value.date
    }

    await apiFetch('/movements', {
      method: 'POST',
      body: JSON.stringify(payload)
    })

    successMessage.value = 'Mouvement créé avec succès'
    closeModal()
    await loadMovements()
    
    // Effacer le message de succès après 3 secondes
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    errorMessage.value = error.message || 'Erreur lors de la création du mouvement'
  } finally {
    isLoading.value = false
  }
}

// Supprimer un mouvement
const deleteMovement = async (id: number) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce mouvement ? Cette action annulera son effet sur le stock.')) {
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    await apiFetch(`/movements/${id}`, {
      method: 'DELETE'
    })

    successMessage.value = 'Mouvement supprimé avec succès'
    await loadMovements()
    
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error: any) {
    errorMessage.value = error.message || 'Erreur lors de la suppression'
  } finally {
    isLoading.value = false
  }
}

const openModal = () => {
  isModalOpen.value = true
  newMovement.value.movement_type = newMovement.value.type === 'entree' ? 'achat' : 'vente'
}

const closeModal = () => {
  isModalOpen.value = false
  newMovement.value = {
    product_id: '',
    type: 'entree',
    movement_type: 'achat',
    quantity: 1,
    reason: '',
    date: new Date().toISOString().slice(0, 16).replace('T', ' ')
  }
  errorMessage.value = ''
}

const typeLabel = (type: MovementType) => {
  return type === 'entree' ? 'Entrée' : 'Sortie'
}

const typeClass = (type: MovementType) => {
  return type === 'entree' 
    ? 'bg-green-100 text-green-800' 
    : 'bg-red-100 text-red-800'
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatMovementType = (type: string) => {
  const types: Record<string, string> = {
    'achat': 'Achat',
    'retour': 'Retour',
    'correction': 'Correction',
    'vente': 'Vente',
    'perte': 'Perte',
    'casse': 'Casse',
    'expiration': 'Expiration'
  }
  return types[type] || type
}

// Watchers
watch(filterType, () => {
  page.value = 1
  loadMovements()
})

watch(page, () => {
  loadMovements()
})

// Initialisation
onMounted(async () => {
  await Promise.all([loadProducts(), loadMovements()])
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Mouvements de stock</h1>
          <p class="text-sm text-gray-500">
            Suivi des entrées et sorties de stock.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <select
            v-model="filterType"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="all">Tous les types</option>
            <option value="entree">Entrées</option>
            <option value="sortie">Sorties</option>
          </select>
          <button
            v-if="canManageMovements"
            @click="openModal"
            :disabled="isLoading"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Plus class="w-4 h-4" />
            Nouveau mouvement
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
        {{ successMessage }}
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="isLoading && movements.length === 0" class="p-8 text-center text-gray-500">
          Chargement...
        </div>
        <div v-else-if="movements.length === 0" class="p-8 text-center text-gray-500">
          Aucun mouvement enregistré
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Produit</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Type</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Sous-type</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Quantité</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Motif</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Utilisateur</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Date</th>
                <th v-if="canManageMovements" class="px-4 py-3 text-center font-semibold text-gray-600 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="movement in movements"
                :key="movement.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ movement.product.name }} ({{ movement.product.code }})
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    :class="['px-2 py-1 text-xs font-medium rounded-full', typeClass(movement.type)]"
                  >
                    {{ typeLabel(movement.type) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ formatMovementType(movement.movement_type) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap font-medium">
                  {{ movement.quantity }}
                </td>
                <td class="px-4 py-3 text-gray-600">
                  {{ movement.reason || '-' }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ movement.user.name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ formatDate(movement.date) }}
                </td>
                <td v-if="canManageMovements" class="px-4 py-3 text-center whitespace-nowrap">
                  <button
                    @click="deleteMovement(movement.id)"
                    :disabled="isLoading"
                    class="text-red-600 hover:text-red-800 disabled:opacity-50"
                    title="Supprimer"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="movements.length > 0" class="flex flex-col md:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 text-xs text-gray-600">
          <div>
            Affichage de {{ (page - 1) * pageSize + 1 }} à {{ Math.min(page * pageSize, totalItems) }} sur {{ totalItems }} mouvements
          </div>
          <div class="flex items-center gap-2">
            <button
              class="px-2 py-1 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="page === 1 || isLoading"
              @click="page--"
            >
              Précédent
            </button>
            <span>
              Page {{ page }} / {{ totalPages }}
            </span>
            <button
              class="px-2 py-1 border border-gray-300 rounded-md bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="page === totalPages || isLoading"
              @click="page++"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 relative max-h-[90vh] overflow-y-auto">
        <button
          @click="closeModal"
          class="absolute right-4 top-4 text-gray-400 hover:text-gray-600"
        >
          <X class="w-5 h-5" />
        </button>

        <h2 class="text-lg font-semibold text-gray-800 mb-1">Nouveau mouvement</h2>
        <p class="text-sm text-gray-500 mb-4">
          Enregistrer une entrée ou une sortie de stock.
        </p>

        <form class="space-y-4" @submit.prevent="saveMovement">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Produit *</label>
              <select
                v-model="newMovement.product_id"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
              >
                <option value="">Sélectionner un produit</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }} ({{ product.code }}) - Stock: {{ product.stock }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Type de mouvement *</label>
              <select
                v-model="newMovement.type"
                @change="newMovement.movement_type = newMovement.type === 'entree' ? 'achat' : 'vente'"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
              >
                <option value="entree">Entrée</option>
                <option value="sortie">Sortie</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Sous-type *</label>
              <select
                v-model="newMovement.movement_type"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
              >
                <option v-for="type in movementTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Quantité *</label>
              <input
                v-model.number="newMovement.quantity"
                type="number"
                min="1"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Date *</label>
            <input
              v-model="newMovement.date"
              type="datetime-local"
              required
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Motif</label>
            <textarea
              v-model="newMovement.reason"
              rows="3"
              placeholder="Raison du mouvement (optionnel)"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
            ></textarea>
          </div>

          <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="closeModal"
              :disabled="isLoading"
              class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="isLoading"
              class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading">Enregistrement...</span>
              <span v-else>Enregistrer</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

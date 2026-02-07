<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, X, Search, Filter, Pencil, Trash2 } from 'lucide-vue-next'
import PageHeader from '@/components/widgets/PageHeader.vue'
import PageCard from '@/components/widgets/PageCard.vue'
import { apiFetch } from '@/services/api'
import { useAuth } from '@/composables/useAuth'

const { canManageProducts } = useAuth()

type Category = {
  id: number
  name: string
  code: string
}

type Product = {
  id: number
  name: string
  code: string
  category: Category
  supplier: string | null
  price: number
  stock: number
  minStock: number
  optimalStock: number
  createdAt?: string
  updatedAt?: string
}

const isModalOpen = ref(false)
const editingProductId = ref<number | null>(null)
const search = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

const products = ref<Product[]>([])
const categories = ref<Category[]>([])

const newProduct = ref({
  name: '',
  code: '',
  categoryId: null as number | null,
  supplier: '',
  price: 0,
  stock: 0,
  minStock: 0,
  optimalStock: 0
})

// Convertir les données du backend (snake_case) vers camelCase
const convertProductFromApi = (product: any): Product => {
  return {
    id: product.id,
    name: product.name,
    code: product.code,
    category: product.category,
    supplier: product.supplier,
    price: product.price,
    stock: product.stock,
    minStock: product.min_stock || product.minStock || 0,
    optimalStock: product.optimal_stock || product.optimalStock || 0,
    createdAt: product.created_at || product.createdAt,
    updatedAt: product.updated_at || product.updatedAt
  }
}

// Charger les produits depuis le backend
const loadProducts = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await apiFetch('/products')
    products.value = Array.isArray(data) ? data.map(convertProductFromApi) : []
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors du chargement des produits'
  } finally {
    isLoading.value = false
  }
}

// Charger les catégories pour le select
const loadCategories = async () => {
  try {
    const data = await apiFetch('/categories')
    categories.value = data
  } catch (err: any) {
    console.error('Erreur lors du chargement des catégories:', err)
  }
}

onMounted(() => {
  loadProducts()
  loadCategories()
})

const filteredProducts = computed(() => {
  if (!search.value) return products.value
  return products.value.filter((p) =>
    [p.name, p.code, p.category?.name || '', p.supplier || ''].some((field) =>
      field?.toLowerCase().includes(search.value.toLowerCase())
    )
  )
})

const page = ref(1)
const pageSize = ref(10)

const totalItems = computed(() => filteredProducts.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)))

const paginatedProducts = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return filteredProducts.value.slice(start, start + pageSize.value)
})

const openModal = (product?: Product) => {
  if (product) {
    editingProductId.value = product.id
    newProduct.value = {
      name: product.name,
      code: product.code,
      categoryId: product.category?.id ?? null,
      supplier: product.supplier || '',
      price: product.price,
      stock: product.stock,
      minStock: product.minStock,
      optimalStock: product.optimalStock
    }
  } else {
    editingProductId.value = null
    newProduct.value = {
      name: '',
      code: '',
      categoryId: null,
      supplier: '',
      price: 0,
      stock: 0,
      minStock: 0,
      optimalStock: 0
    }
  }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  editingProductId.value = null
  newProduct.value = {
    name: '',
    code: '',
    categoryId: null,
    supplier: '',
    price: 0,
    stock: 0,
    minStock: 0,
    optimalStock: 0
  }
  errorMessage.value = ''
}

const saveProduct = async () => {
  if (!newProduct.value.name || !newProduct.value.code || !newProduct.value.categoryId) {
    errorMessage.value = 'Le nom, le code et la catégorie sont obligatoires'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    const productData = {
      name: newProduct.value.name,
      code: newProduct.value.code,
      category_id: newProduct.value.categoryId,
      supplier: newProduct.value.supplier || null,
      price: Number(newProduct.value.price) || 0,
      stock: Number(newProduct.value.stock) || 0,
      min_stock: Number(newProduct.value.minStock) || 0,
      optimal_stock: Number(newProduct.value.optimalStock) || 0
    }

    if (editingProductId.value) {
      const updated = await apiFetch(`/products/${editingProductId.value}`, {
        method: 'PUT',
        body: JSON.stringify(productData)
      })
      const index = products.value.findIndex((p) => p.id === editingProductId.value)
      if (index !== -1) products.value[index] = convertProductFromApi(updated)
      closeModal()
    } else {
      const created = await apiFetch('/products', {
        method: 'POST',
        body: JSON.stringify(productData)
      })
      products.value.push(convertProductFromApi(created))
      closeModal()
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors de l\'enregistrement du produit'
  } finally {
    isLoading.value = false
  }
}

const deleteProduct = async (product: Product) => {
  if (!confirm(`Supprimer le produit "${product.name}" ? Cette action est irréversible.`)) return

  isLoading.value = true
  errorMessage.value = ''
  try {
    await apiFetch(`/products/${product.id}`, { method: 'DELETE' })
    products.value = products.value.filter((p) => p.id !== product.id)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors de la suppression'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <PageHeader
        title="Produits"
        subtitle="Gestion des produits, niveaux de stock minimum/optimal et fournisseurs."
      >
        <template #actions>
          <div class="relative">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="search"
              type="text"
              placeholder="Rechercher un produit..."
              class="pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
            />
          </div>
          <button
            class="hidden md:inline-flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"
          >
            <Filter class="w-4 h-4" />
            Filtres
          </button>
          <button
            v-if="canManageProducts"
            @click="openModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition-colors"
          >
            <Plus class="w-4 h-4" />
            Nouveau produit
          </button>
        </template>
      </PageHeader>

      <!-- Table -->
      <PageCard>
        <p v-if="errorMessage && products.length === 0" class="text-sm text-red-600 bg-red-50 p-3 rounded mb-4">
          {{ errorMessage }}
        </p>
        <div v-if="isLoading && products.length === 0" class="text-center py-8 text-gray-500">
          Chargement des produits...
        </div>
        <div v-else-if="products.length === 0" class="text-center py-8 text-gray-500">
          Aucun produit trouvé. Créez-en un pour commencer.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Nom</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Code</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Catégorie</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Fournisseur</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Prix</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Stock</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Stock min.</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Stock optimal</th>
                <th v-if="canManageProducts" class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" v-if="products.length > 0">
              <tr
                v-for="product in paginatedProducts"
                :key="product.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ product.name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ product.code }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ product.category?.name || '-' }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ product.supplier || '-' }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap">
                  {{ Number(product.price).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) }}
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <span
                    :class="[
                      'px-2 py-1 text-xs font-medium rounded-full',
                      product.stock <= product.minStock
                        ? 'bg-red-100 text-red-800'
                        : product.stock < product.optimalStock
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ product.stock }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">
                  {{ product.minStock }}
                </td>
                <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">
                  {{ product.optimalStock }}
                </td>
                <td v-if="canManageProducts" class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openModal(product)"
                      class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
                      title="Modifier"
                    >
                      <Pencil class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteProduct(product)"
                      class="p-1.5 text-red-600 hover:bg-red-50 rounded"
                      title="Supprimer"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
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
      </PageCard>
    </div>

    <!-- Modal -->
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

        <h2 class="text-lg font-semibold text-gray-800 mb-1">
          {{ editingProductId ? 'Modifier le produit' : 'Nouveau produit' }}
        </h2>
        <p class="text-sm text-gray-500 mb-4">
          {{ editingProductId ? 'Modifier les informations du produit.' : 'Enregistrer un nouveau produit dans le stock.' }}
        </p>
        <p v-if="errorMessage" class="text-sm text-red-600 bg-red-50 p-2 rounded mb-4">
          {{ errorMessage }}
        </p>

        <form class="space-y-4" @submit.prevent="saveProduct">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Nom</label>
              <input
                v-model="newProduct.name"
                type="text"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Code produit</label>
              <input
                v-model="newProduct.code"
                type="text"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie</label>
              <select
                v-model.number="newProduct.categoryId"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option :value="null">Sélectionner une catégorie</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Fournisseur (optionnel)</label>
              <input
                v-model="newProduct.supplier"
                type="text"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Prix (€)</label>
              <input
                v-model.number="newProduct.price"
                type="number"
                min="0"
                step="0.01"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Stock actuel</label>
              <input
                v-model.number="newProduct.stock"
                type="number"
                min="0"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Stock min.</label>
              <input
                v-model.number="newProduct.minStock"
                type="number"
                min="0"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Stock optimal</label>
              <input
                v-model.number="newProduct.optimalStock"
                type="number"
                min="0"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
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
              class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="!isLoading">Enregistrer</span>
              <span v-else>Enregistrement...</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import PageHeader from '@/components/widgets/PageHeader.vue'
import PageCard from '@/components/widgets/PageCard.vue'
import { apiFetch } from '@/services/api'

type Category = {
  id: number
  name: string
  code: string
  parentId: number | null
  description: string | null
  createdAt?: string
  updatedAt?: string
}

const isModalOpen = ref(false)
const search = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

const categories = ref<Category[]>([])

const newCategory = ref({
  name: '',
  code: '',
  parentId: null as number | null,
  description: ''
})

// Charger les catégories depuis le backend
const loadCategories = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await apiFetch('/categories')
    categories.value = data
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors du chargement des catégories'
  } finally {
    isLoading.value = false
  }
}

// Obtenir le nom de la catégorie parente
const getParentName = (parentId: number | null) => {
  if (!parentId) return null
  const parent = categories.value.find(c => c.id === parentId)
  return parent?.name || null
}

onMounted(() => {
  loadCategories()
})

const filteredCategories = computed(() => {
  if (!search.value) return categories.value
  return categories.value.filter((c) =>
    [c.name, c.code, getParentName(c.parentId) ?? '', c.description ?? ''].some((field) =>
      field?.toLowerCase().includes(search.value.toLowerCase())
    )
  )
})

const page = ref(1)
const pageSize = ref(10)

const totalItems = computed(() => filteredCategories.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)))

const paginatedCategories = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return filteredCategories.value.slice(start, start + pageSize.value)
})

const openModal = () => {
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  newCategory.value = {
    name: '',
    code: '',
    parentId: null,
    description: ''
  }
  errorMessage.value = ''
}

const saveCategory = async () => {
  if (!newCategory.value.name || !newCategory.value.code) {
    errorMessage.value = 'Le nom et le code sont obligatoires'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    const categoryData = {
      name: newCategory.value.name,
      code: newCategory.value.code,
      parentId: newCategory.value.parentId || null,
      description: newCategory.value.description || null
    }

    const created = await apiFetch('/categories', {
      method: 'POST',
      body: JSON.stringify(categoryData)
    })

    // Ajouter la nouvelle catégorie à la liste
    categories.value.push(created)
    closeModal()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors de la création de la catégorie'
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
        title="Catégories"
        subtitle="Organisation hiérarchique des catégories et sous-catégories."
      >
        <template #actions>
          <input
            v-model="search"
            type="text"
            placeholder="Rechercher une catégorie..."
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
          />
          <button
            @click="openModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition-colors"
          >
            <Plus class="w-4 h-4" />
            Nouvelle catégorie
          </button>
        </template>
      </PageHeader>

      <!-- Table -->
      <PageCard>
        <p v-if="errorMessage && categories.length === 0" class="text-sm text-red-600 bg-red-50 p-3 rounded mb-4">
          {{ errorMessage }}
        </p>
        <div v-if="isLoading && categories.length === 0" class="text-center py-8 text-gray-500">
          Chargement des catégories...
        </div>
        <div v-else-if="categories.length === 0" class="text-center py-8 text-gray-500">
          Aucune catégorie trouvée. Créez-en une pour commencer.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Nom</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Code</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Catégorie parente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Description</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" v-if="categories.length > 0">
              <tr
                v-for="category in paginatedCategories"
                :key="category.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ category.name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ category.code }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ getParentName(category.parentId) || '-' }}
                </td>
                <td class="px-4 py-3 text-gray-600">
                  {{ category.description || '-' }}
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
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative">
        <button
          @click="closeModal"
          class="absolute right-4 top-4 text-gray-400 hover:text-gray-600"
        >
          <X class="w-5 h-5" />
        </button>

        <h2 class="text-lg font-semibold text-gray-800 mb-1">Nouvelle catégorie</h2>
        <p class="text-sm text-gray-500 mb-4">
          Créer une catégorie ou sous-catégorie.
        </p>
        <p v-if="errorMessage" class="text-sm text-red-600 bg-red-50 p-2 rounded mb-4">
          {{ errorMessage }}
        </p>

        <form class="space-y-4" @submit.prevent="saveCategory">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nom</label>
            <input
              v-model="newCategory.name"
              type="text"
              required
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
              <input
                v-model="newCategory.code"
                type="text"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie parente (optionnel)</label>
              <select
                v-model.number="newCategory.parentId"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              >
                <option :value="null">Aucune</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea
              v-model="newCategory.description"
              rows="3"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
            ></textarea>
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


<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, X, Edit, Trash2 } from 'lucide-vue-next'
import { apiFetch } from '@/services/api'
import { useAuth } from '@/composables/useAuth'
import PageHeader from '@/components/widgets/PageHeader.vue'
import PageCard from '@/components/widgets/PageCard.vue'

type Role = 'admin' | 'gestionnaire' | 'observateur'

type UserRow = {
  id: number
  name: string
  email: string
  role: Role
  createdAt?: string
  updatedAt?: string
}

const isModalOpen = ref(false)
const isEditMode = ref(false)
const editingUserId = ref<number | null>(null)
const filterRole = ref<Role | 'all'>('all')
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const users = ref<UserRow[]>([])
const { user: currentUser } = useAuth()

const newUser = ref({
  name: '',
  email: '',
  password: '',
  role: 'gestionnaire' as Role
})

// Charger les utilisateurs depuis le backend
const loadUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await apiFetch('/users')
    users.value = data
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors du chargement des utilisateurs'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadUsers()
})

const filteredUsers = computed(() => {
  let filtered = users.value
  if (filterRole.value !== 'all') {
    filtered = filtered.filter((u) => u.role === filterRole.value)
  }
  return filtered
})

const page = ref(1)
const pageSize = ref(10)

const totalItems = computed(() => filteredUsers.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)))

const paginatedUsers = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return filteredUsers.value.slice(start, start + pageSize.value)
})

const getRoleLabel = (role: Role) => {
  if (role === 'admin') return 'Admin'
  if (role === 'gestionnaire') return 'Gestionnaire'
  return 'Observateur'
}

const getRoleClass = (role: Role) => {
  if (role === 'admin') return 'bg-red-100 text-red-800'
  if (role === 'gestionnaire') return 'bg-blue-100 text-blue-800'
  return 'bg-gray-100 text-gray-800'
}

const openModal = () => {
  isModalOpen.value = true
  isEditMode.value = false
  editingUserId.value = null
  resetForm()
}

const openEditModal = (user: UserRow) => {
  isModalOpen.value = true
  isEditMode.value = true
  editingUserId.value = user.id
  newUser.value = {
    name: user.name,
    email: user.email,
    password: '', // Ne pas pré-remplir le mot de passe
    role: user.role
  }
}

const closeModal = () => {
  isModalOpen.value = false
  isEditMode.value = false
  editingUserId.value = null
  resetForm()
  errorMessage.value = ''
  successMessage.value = ''
}

const resetForm = () => {
  newUser.value = {
    name: '',
    email: '',
    password: '',
    role: 'gestionnaire'
  }
}

const saveUser = async () => {
  if (!newUser.value.name || !newUser.value.email) {
    errorMessage.value = 'Le nom et l\'email sont obligatoires'
    return
  }

  if (!isEditMode.value && !newUser.value.password) {
    errorMessage.value = 'Le mot de passe est obligatoire pour créer un utilisateur'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    if (isEditMode.value && editingUserId.value) {
      // Mise à jour
      const updateData: any = {
        name: newUser.value.name,
        email: newUser.value.email,
        role: newUser.value.role
      }
      
      // Inclure le mot de passe seulement s'il est fourni
      if (newUser.value.password) {
        updateData.password = newUser.value.password
      }

      const updated = await apiFetch(`/users/${editingUserId.value}`, {
        method: 'PUT',
        body: JSON.stringify(updateData)
      })

      // Mettre à jour la liste
      const index = users.value.findIndex(u => u.id === editingUserId.value)
      if (index !== -1) {
        users.value[index] = updated
      }

      successMessage.value = 'Utilisateur mis à jour avec succès'
    } else {
      // Création
      const created = await apiFetch('/users', {
        method: 'POST',
        body: JSON.stringify({
          name: newUser.value.name,
          email: newUser.value.email,
          password: newUser.value.password,
          role: newUser.value.role
        })
      })

      users.value.push(created)
      successMessage.value = 'Utilisateur créé avec succès'
    }

    setTimeout(() => {
      closeModal()
    }, 1500)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors de l\'enregistrement'
  } finally {
    isLoading.value = false
  }
}

const deleteUser = async (userId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    await apiFetch(`/users/${userId}`, {
      method: 'DELETE'
    })

    // Retirer l'utilisateur de la liste
    users.value = users.value.filter(u => u.id !== userId)
    successMessage.value = 'Utilisateur supprimé avec succès'
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors de la suppression'
  } finally {
    isLoading.value = false
  }
}

// Vérifier si l'utilisateur peut être supprimé (pas son propre compte)
const canDelete = (userId: number) => {
  return currentUser.value?.id !== userId
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 w-full">
    <div class="w-full px-6 py-6">
      <!-- Header -->
      <PageHeader
        title="Gestion des utilisateurs"
        subtitle="Gestion des comptes, rôles (Admin, Gestionnaire, Observateur) et statut d'accès."
      >
        <template #actions>
          <select
            v-model="filterRole"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
          >
            <option value="all">Tous les rôles</option>
            <option value="admin">Admin</option>
            <option value="gestionnaire">Gestionnaire</option>
            <option value="observateur">Observateur</option>
          </select>
          <button
            @click="openModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition-colors"
          >
            <Plus class="w-4 h-4" />
            Nouvel utilisateur
          </button>
        </template>
      </PageHeader>

      <!-- Messages -->
      <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-600 text-sm">
        {{ successMessage }}
      </div>

      <!-- Table -->
      <PageCard>
        <div v-if="isLoading && users.length === 0" class="text-center py-8 text-gray-500">
          Chargement des utilisateurs...
        </div>
        <div v-else-if="users.length === 0" class="text-center py-8 text-gray-500">
          Aucun utilisateur trouvé. Créez-en un pour commencer.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Nom</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Email</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Rôle</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">Date de création</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="user in paginatedUsers"
                :key="user.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">
                  {{ user.name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ user.email }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    :class="['px-2 py-1 text-xs font-medium rounded-full', getRoleClass(user.role)]"
                  >
                    {{ getRoleLabel(user.role) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ user.createdAt ? new Date(user.createdAt).toLocaleDateString('fr-FR') : '-' }}
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openEditModal(user)"
                      class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      title="Modifier"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <button
                      v-if="canDelete(user.id)"
                      @click="deleteUser(user.id)"
                      :disabled="isLoading"
                      class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
                      title="Supprimer"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                    <span v-else class="text-xs text-gray-400">Vous</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.length > 0" class="flex flex-col md:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 text-xs text-gray-600">
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
          {{ isEditMode ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
        </h2>
        <p class="text-sm text-gray-500 mb-4">
          {{ isEditMode ? 'Modifier les informations de l\'utilisateur.' : 'Créer un compte et lui attribuer un rôle.' }}
        </p>

        <p v-if="errorMessage" class="text-sm text-red-600 bg-red-50 p-2 rounded mb-4">
          {{ errorMessage }}
        </p>
        <p v-if="successMessage" class="text-sm text-green-600 bg-green-50 p-2 rounded mb-4">
          {{ successMessage }}
        </p>

        <form class="space-y-4" @submit.prevent="saveUser">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet</label>
              <input
                v-model="newUser.name"
                type="text"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
              <input
                v-model="newUser.email"
                type="email"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Rôle</label>
              <select
                v-model="newUser.role"
                required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
              >
                <option value="admin">Admin</option>
                <option value="gestionnaire">Gestionnaire</option>
                <option value="observateur">Observateur</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">
                Mot de passe {{ isEditMode ? '(laisser vide pour ne pas modifier)' : '' }}
              </label>
              <input
                v-model="newUser.password"
                type="password"
                :required="!isEditMode"
                placeholder="••••••••"
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
              <span v-if="!isLoading">{{ isEditMode ? 'Mettre à jour' : 'Créer' }}</span>
              <span v-else>{{ isEditMode ? 'Mise à jour...' : 'Création...' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>


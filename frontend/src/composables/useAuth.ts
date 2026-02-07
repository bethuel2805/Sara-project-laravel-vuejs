import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch, API_BASE_URL } from '@/services/api'

export type UserRole = 'admin' | 'gestionnaire' | 'observateur'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
}

const user = ref<User | null>(null)
const isLoading = ref(false)

// Initialiser l'authentification au démarrage (comme dans whatsapp-pro)
const initializeAuth = () => {
  const storedUser = sessionStorage.getItem('sara_user')
  const accessToken = sessionStorage.getItem('sara_token')

  if (storedUser) {
    try {
      user.value = JSON.parse(storedUser)
    } catch (e) {
      sessionStorage.removeItem('sara_user')
      sessionStorage.removeItem('sara_token')
    }
  } else if (accessToken) {
    // Si on a un token mais pas d'utilisateur, récupérer l'utilisateur
    checkAuthFromToken()
  }
}

// Vérifier le token et récupérer l'utilisateur
const checkAuthFromToken = async () => {
  try {
    const userData = await apiFetch('/auth/me')
    user.value = userData
    sessionStorage.setItem('sara_user', JSON.stringify(userData))
  } catch (e) {
    sessionStorage.removeItem('sara_token')
    sessionStorage.removeItem('sara_user')
    user.value = null
  }
}

// Initialiser au chargement du module
initializeAuth()

export function useAuth() {
  const router = useRouter()

  const isAuthenticated = computed(() => user.value !== null)
  const currentUser = computed(() => user.value)
  const userRole = computed(() => user.value?.role || null)

  // Vérifier si l'utilisateur a un rôle spécifique
  const hasRole = (role: UserRole) => {
    return user.value?.role === role
  }

  // Vérifier si l'utilisateur a au moins un des rôles
  const hasAnyRole = (roles: UserRole[]) => {
    return user.value ? roles.includes(user.value.role) : false
  }

  // Vérifier les permissions selon le cahier des charges
  const canManageUsers = computed(() => hasRole('admin'))
  const canManageProducts = computed(() => hasAnyRole(['admin', 'gestionnaire']))
  const canManageCategories = computed(() => hasAnyRole(['admin', 'gestionnaire']))
  const canManageMovements = computed(() => hasAnyRole(['admin', 'gestionnaire']))
  const canManageInventories = computed(() => hasAnyRole(['admin', 'gestionnaire']))
  // Observateur : lecture seule (accès aux pages sans modifier)
  const canViewProducts = computed(() => hasAnyRole(['admin', 'gestionnaire', 'observateur']))
  const canViewCategories = computed(() => hasAnyRole(['admin', 'gestionnaire', 'observateur']))
  const canViewMovements = computed(() => hasAnyRole(['admin', 'gestionnaire', 'observateur']))
  const canViewInventories = computed(() => hasAnyRole(['admin', 'gestionnaire', 'observateur']))
  const canViewReports = computed(() => isAuthenticated.value) // Tous les utilisateurs authentifiés
  const canViewPredictions = computed(() => isAuthenticated.value)
  const canViewAlerts = computed(() => isAuthenticated.value)
  const canExport = computed(() => isAuthenticated.value)

  const login = async (email: string, password: string) => {
    isLoading.value = true
    try {
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        const body = await response.json().catch(() => ({}))
        throw new Error(body.message || 'Identifiants invalides')
      }

      const data = await response.json()
      
      // Stocker dans sessionStorage comme dans whatsapp-pro
      sessionStorage.setItem('sara_token', data.token)
      sessionStorage.setItem('sara_user', JSON.stringify(data.user))
      
      user.value = data.user
      
      return data
    } finally {
      isLoading.value = false
    }
  }

  const register = async (name: string, email: string, password: string, role?: UserRole) => {
    isLoading.value = true
    try {
      const response = await fetch(`${API_BASE_URL}/auth/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ name, email, password, role }),
      })

      // Vérifier si la réponse est du HTML (erreur)
      const contentType = response.headers.get('content-type')
      if (contentType && contentType.includes('text/html')) {
        throw new Error('Le serveur a renvoyé une erreur. Vérifiez que le serveur Laravel est démarré sur le port 8000.')
      }

      if (!response.ok) {
        const body = await response.json().catch(() => ({}))
        throw new Error(body.message || 'Erreur lors de l\'inscription')
      }

      const data = await response.json()
      return data
    } catch (error: any) {
      if (error.message) {
        throw error
      }
      throw new Error('Erreur de connexion au serveur. Vérifiez que le serveur Laravel est démarré.')
    } finally {
      isLoading.value = false
    }
  }

  const canRegister = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/can-register`, {
        headers: {
          'Accept': 'application/json',
        },
      })
      
      // Vérifier si la réponse est du HTML (erreur)
      const contentType = response.headers.get('content-type')
      if (contentType && contentType.includes('text/html')) {
        return { can_register: false, message: 'Le serveur a renvoyé une erreur. Vérifiez que le serveur Laravel est démarré.' }
      }
      
      if (!response.ok) {
        return { can_register: false, message: 'Erreur lors de la vérification' }
      }
      const data = await response.json()
      return data
    } catch (error) {
      return { can_register: false, message: 'Erreur de connexion au serveur. Vérifiez que le serveur Laravel est démarré sur le port 8000.' }
    }
  }

  const logout = () => {
    // Nettoyer sessionStorage comme dans whatsapp-pro
    sessionStorage.removeItem('sara_token')
    sessionStorage.removeItem('sara_user')
    
    user.value = null
    
    router.push('/login')
  }

  // Vérifier si le token est toujours valide
  const checkAuth = async () => {
    const token = sessionStorage.getItem('sara_token')
    if (!token) {
      logout()
      return false
    }

    try {
      // Vérifier le token avec le backend et mettre à jour l'utilisateur
      const userData = await apiFetch('/auth/me')
      user.value = userData
      sessionStorage.setItem('sara_user', JSON.stringify(userData))
      return true
    } catch (e) {
      logout()
      return false
    }
  }

  return {
    user: currentUser,
    isAuthenticated,
    isLoading,
    userRole,
    hasRole,
    hasAnyRole,
    canManageUsers,
    canManageProducts,
    canManageCategories,
    canManageMovements,
    canManageInventories,
    canViewProducts,
    canViewCategories,
    canViewMovements,
    canViewInventories,
    canViewReports,
    canViewPredictions,
    canViewAlerts,
    canExport,
    login,
    register,
    canRegister,
    logout,
    checkAuth,
  }
}

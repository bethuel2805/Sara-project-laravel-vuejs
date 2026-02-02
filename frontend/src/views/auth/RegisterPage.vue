<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Eye, EyeOff } from 'lucide-vue-next'

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const errorMessage = ref('')
const formErrors = ref<Record<string, string>>({})
const isChecking = ref(true)
const registrationDisabled = ref(false)
const disabledMessage = ref('')

const router = useRouter()
const { register, canRegister, isLoading } = useAuth()

onMounted(async () => {
  // Vérifier si l'inscription est possible
  isChecking.value = true
  try {
    const result = await canRegister()
    if (!result.can_register) {
      registrationDisabled.value = true
      disabledMessage.value = result.message || 'L\'inscription est désactivée. Veuillez contacter un administrateur.'
      // Rediriger vers la page de login après 3 secondes
      setTimeout(() => {
        router.push({ name: 'login-page', query: { registrationDisabled: 'true' } })
      }, 3000)
    }
  } catch (error) {
    errorMessage.value = 'Erreur lors de la vérification. Veuillez réessayer.'
  } finally {
    isChecking.value = false
  }
})

const clearError = (field: string) => {
  if (formErrors.value[field]) {
    formErrors.value[field] = ''
  }
}

const validateForm = () => {
  formErrors.value = {}
  let isValid = true

  if (!name.value.trim()) {
    formErrors.value.name = 'Le nom est obligatoire'
    isValid = false
  }

  if (!email.value.trim()) {
    formErrors.value.email = 'L\'email est obligatoire'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    formErrors.value.email = 'Le format d\'email est incorrect'
    isValid = false
  }

  if (!password.value) {
    formErrors.value.password = 'Le mot de passe est obligatoire'
    isValid = false
  } else if (password.value.length < 8) {
    formErrors.value.password = 'Le mot de passe doit contenir au moins 8 caractères'
    isValid = false
  }

  if (!confirmPassword.value) {
    formErrors.value.confirmPassword = 'La confirmation du mot de passe est obligatoire'
    isValid = false
  } else if (password.value !== confirmPassword.value) {
    formErrors.value.confirmPassword = 'Les mots de passe ne correspondent pas'
    isValid = false
  }

  return isValid
}

const handleRegister = async () => {
  if (!validateForm()) {
    return
  }

  errorMessage.value = ''

  try {
    await register(name.value, email.value, password.value)
    // Après inscription réussie, rediriger vers la page de connexion
    await router.push({ name: 'login-page', query: { registered: 'true' } })
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur lors de l\'inscription'
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-linear-to-br from-[#1b3979] via-[#2a4a8f] to-[#1b3979] px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo/App Name Box -->
      <div class="mb-8 flex justify-center">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-2xl border border-white/20">
          <div class="flex flex-col items-center justify-center space-y-3">
            <div class="w-20 h-20 bg-linear-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
              <span class="text-3xl font-bold text-white">S</span>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Sara</h1>
            <p class="text-white/80 text-sm">Créez votre compte</p>
          </div>
        </div>
      </div>

      <!-- Register Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8 space-y-6 backdrop-blur-sm border border-white/10">
        <div class="text-center">
          <h2 class="text-2xl font-bold text-gray-800">Inscription</h2>
          <p class="text-gray-500 mt-2">
            <span v-if="!isChecking && !registrationDisabled">Créez votre compte pour commencer. Le premier compte sera administrateur.</span>
            <span v-else-if="isChecking">Vérification en cours...</span>
            <span v-else>{{ disabledMessage }}</span>
          </p>
        </div>

        <div v-if="isChecking" class="flex items-center justify-center py-8">
          <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <div v-else-if="registrationDisabled" class="text-center py-8">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-yellow-800">{{ disabledMessage }}</p>
            <p class="text-sm text-yellow-600 mt-2">Redirection vers la page de connexion...</p>
          </div>
          <router-link 
            to="/login" 
            class="text-blue-600 hover:text-blue-700 font-semibold transition-colors"
          >
            Aller à la page de connexion
          </router-link>
        </div>

        <template v-else>
          <p v-if="errorMessage" class="text-sm text-red-600 text-center">
            {{ errorMessage }}
          </p>

        <form @submit.prevent="handleRegister" class="space-y-5">
          <!-- Name Input -->
          <div class="space-y-2">
            <label for="name" class="block text-sm font-medium text-gray-700">
              Nom complet
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input
                id="name"
                v-model="name"
                type="text"
                required
                placeholder="Votre nom complet"
                @input="clearError('name')"
                :class="[
                  'block w-full pl-10 pr-3 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-gray-700 placeholder-gray-400',
                  formErrors.name ? 'border-red-300' : 'border-gray-300'
                ]"
              />
            </div>
            <p v-if="formErrors.name" class="text-xs text-red-600">{{ formErrors.name }}</p>
          </div>

          <!-- Email Input -->
          <div class="space-y-2">
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
              </div>
              <input
                id="email"
                v-model="email"
                type="email"
                required
                placeholder="votre@email.com"
                @input="clearError('email')"
                :class="[
                  'block w-full pl-10 pr-3 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-gray-700 placeholder-gray-400',
                  formErrors.email ? 'border-red-300' : 'border-gray-300'
                ]"
              />
            </div>
            <p v-if="formErrors.email" class="text-xs text-red-600">{{ formErrors.email }}</p>
          </div>

          <!-- Password Input -->
          <div class="space-y-2">
            <label for="password" class="block text-sm font-medium text-gray-700">
              Mot de passe
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                @input="clearError('password')"
                :class="[
                  'block w-full pl-10 pr-10 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-gray-700 placeholder-gray-400',
                  formErrors.password ? 'border-red-300' : 'border-gray-300'
                ]"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
              >
                <Eye v-if="!showPassword" class="h-5 w-5" />
                <EyeOff v-else class="h-5 w-5" />
              </button>
            </div>
            <p v-if="formErrors.password" class="text-xs text-red-600">{{ formErrors.password }}</p>
          </div>

          <!-- Confirm Password Input -->
          <div class="space-y-2">
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700">
              Confirmer le mot de passe
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input
                id="confirmPassword"
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                @input="clearError('confirmPassword')"
                :class="[
                  'block w-full pl-10 pr-10 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-gray-700 placeholder-gray-400',
                  formErrors.confirmPassword ? 'border-red-300' : 'border-gray-300'
                ]"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
              >
                <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                <EyeOff v-else class="h-5 w-5" />
              </button>
            </div>
            <p v-if="formErrors.confirmPassword" class="text-xs text-red-600">{{ formErrors.confirmPassword }}</p>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading || !name || !email || !password || !confirmPassword"
            class="w-full py-3 px-4 bg-linear-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98]"
          >
            <span v-if="!isLoading">S'inscrire</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Inscription...
            </span>
          </button>
        </form>

          <!-- Login Link -->
          <div class="text-center text-sm text-gray-600">
            <p>
              Déjà un compte ?
              <router-link to="/login" class="text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                Se connecter
              </router-link>
            </p>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

div[class*="max-w-md"] {
  animation: fadeIn 0.5s ease-out;
}
</style>

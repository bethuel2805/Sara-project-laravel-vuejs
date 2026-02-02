<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const router = useRouter()
const route = useRoute()
const { login, isLoading } = useAuth()

onMounted(() => {
  // Afficher un message de succès si on vient de s'inscrire
  if (route.query.registered === 'true') {
    successMessage.value = 'Inscription réussie ! Vous pouvez maintenant vous connecter.'
    // Effacer le message après 5 secondes
    setTimeout(() => {
      successMessage.value = ''
    }, 5000)
  }
  
  // Afficher un message si l'inscription est désactivée
  if (route.query.registrationDisabled === 'true') {
    errorMessage.value = 'L\'inscription est désactivée. Veuillez contacter un administrateur pour créer un compte.'
  }
})

const handleLogin = async () => {
  if (!email.value || !password.value) {
    return
  }

  errorMessage.value = ''

  try {
    await login(email.value, password.value)
    const redirect = route.query.redirect as string || '/dashboard'
    await router.push(redirect)
  } catch (err: any) {
    errorMessage.value = err.message || 'Erreur de connexion'
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
            <!-- Logo placeholder - remplacez par votre logo -->
            <div class="w-20 h-20 bg-linear-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
              <span class="text-3xl font-bold text-white">S</span>
            </div>
            <!-- App Name -->
            <h1 class="text-3xl font-bold text-white tracking-tight">Sara</h1>
            <p class="text-white/80 text-sm">Bienvenue de retour</p>
          </div>
        </div>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8 space-y-6 backdrop-blur-sm border border-white/10">
        <div class="text-center">
          <h2 class="text-2xl font-bold text-gray-800">Connexion</h2>
          <p class="text-gray-500 mt-2">Connectez-vous à votre compte</p>
        </div>

        <p v-if="errorMessage" class="text-sm text-red-600 text-center">
          {{ errorMessage }}
        </p>
        <p v-if="successMessage" class="text-sm text-green-600 text-center bg-green-50 p-3 rounded-lg">
          {{ successMessage }}
        </p>

        <form @submit.prevent="handleLogin" class="space-y-5">
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
                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-gray-700 placeholder-gray-400"
              />
            </div>
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
                class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-gray-700 placeholder-gray-400"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Remember Me & Forgot Password -->
          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center">
              <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
              <span class="ml-2 text-gray-600">Se souvenir de moi</span>
            </label>
            <a href="#" class="text-blue-600 hover:text-blue-700 font-medium transition-colors">
              Mot de passe oublié ?
            </a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading || !email || !password"
            class="w-full py-3 px-4 bg-linear-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98]"
          >
            <span v-if="!isLoading">Se connecter</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Connexion...
            </span>
          </button>
        </form>

        <!-- Sign Up Link -->
        <div class="text-center text-sm text-gray-600">
          <p>
            Pas encore de compte ?
            <router-link to="/register" class="text-blue-600 hover:text-blue-700 font-semibold transition-colors">
              S'inscrire
            </router-link>
          </p>
        </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animations supplémentaires si nécessaire */
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
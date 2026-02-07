<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSidebar } from '../composables/useSidebar'
import { useAuth } from '../composables/useAuth'
import {
  LayoutDashboard,
  Package,
  FolderTree,
  ArrowRightLeft,
  ClipboardList,
  TrendingUp,
  Bell,
  FileText,
  Download,
  Users,
  ChevronLeft,
  User,
  LogOut
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const { isCollapsed, toggleSidebar } = useSidebar()
const { user, logout, canManageUsers, canManageProducts, canManageCategories, canManageMovements, canManageInventories, canViewProducts, canViewCategories, canViewMovements, canViewInventories } = useAuth()

const menuItems = computed(() => {
  const items = [
    {
      name: 'Dashboard',
      icon: LayoutDashboard,
      path: '/dashboard',
      active: route.path === '/dashboard',
      show: true
    },
    {
      name: 'Produits',
      icon: Package,
      path: '/products',
      active: route.path.startsWith('/products'),
      show: canViewProducts.value
    },
    {
      name: 'Catégories',
      icon: FolderTree,
      path: '/categories',
      active: route.path.startsWith('/categories'),
      show: canViewCategories.value
    },
    {
      name: 'Mouvements',
      icon: ArrowRightLeft,
      path: '/movements',
      active: route.path.startsWith('/movements'),
      show: canViewMovements.value
    },
    {
      name: 'Inventaires',
      icon: ClipboardList,
      path: '/inventories',
      active: route.path.startsWith('/inventories'),
      show: canViewInventories.value
    },
    {
      name: 'Prédictions',
      icon: TrendingUp,
      path: '/predictions',
      active: route.path.startsWith('/predictions'),
      show: true
    },
    {
      name: 'Alertes',
      icon: Bell,
      path: '/alerts',
      active: route.path.startsWith('/alerts'),
      show: true
    },
    {
      name: 'Rapports',
      icon: FileText,
      path: '/reports',
      active: route.path.startsWith('/reports'),
      show: true
    },
    {
      name: 'Exports',
      icon: Download,
      path: '/exports',
      active: route.path.startsWith('/exports'),
      show: true
    },
    {
      name: 'Utilisateurs',
      icon: Users,
      path: '/users',
      active: route.path.startsWith('/users'),
      show: canManageUsers.value
    }
  ]
  
  return items.filter(item => item.show)
})

const navigateTo = (path: string) => {
  router.push(path)
}

const handleLogout = () => {
  logout()
}
</script>

<template>
  <aside
    :class="[
      'fixed left-0 top-0 h-screen bg-linear-to-br from-[#1b3979] via-[#2a4a8f] to-[#1b3979] text-white transition-all duration-300 z-50 shadow-2xl',
      isCollapsed ? 'w-20' : 'w-64'
    ]"
  >
    <div class="flex flex-col h-full overflow-hidden">
      <!-- Logo Section -->
      <div class="p-6 border-b border-white/10 shrink-0">
        <div class="flex items-center justify-between">
          <div v-if="!isCollapsed" class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-linear-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
              <span class="text-xl font-bold text-white">S</span>
            </div>
            <div>
              <h1 class="text-xl font-bold">Sara</h1>
              <p class="text-xs text-white/70">Dashboard</p>
            </div>
          </div>
          <div v-else class="flex justify-center w-full">
            <div class="w-10 h-10 bg-linear-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
              <span class="text-xl font-bold text-white">S</span>
            </div>
          </div>
          <button
            @click="toggleSidebar"
            class="p-2 rounded-lg hover:bg-white/10 transition-colors shrink-0"
            :title="isCollapsed ? 'Expand' : 'Collapse'"
          >
            <ChevronLeft
              :class="['w-5 h-5 transition-transform', isCollapsed ? 'rotate-180' : '']"
            />
          </button>
        </div>
      </div>

      <!-- Navigation Menu (scrollable) -->
      <nav class="p-4 space-y-2 mt-4 flex-1 overflow-y-auto custom-scrollbar">
        <button
          v-for="item in menuItems"
          :key="item.name"
          @click="navigateTo(item.path)"
          :class="[
            'w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200 group',
            item.active
              ? 'bg-white/20 shadow-lg backdrop-blur-sm'
              : 'hover:bg-white/10 hover:shadow-md'
          ]"
          :title="isCollapsed ? item.name : ''"
        >
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          <span v-if="!isCollapsed" class="font-medium">{{ item.name }}</span>
        </button>
      </nav>

      <!-- User Section (fixe en bas mais dans le flux) -->
      <div class="p-4 border-t border-white/10 shrink-0 space-y-2">
        <div v-if="!isCollapsed" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition-colors">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <User class="w-6 h-6" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ user?.name || 'Utilisateur' }}</p>
            <p class="text-xs text-white/70 truncate">{{ user?.email || 'user@sara.com' }}</p>
            <p class="text-xs text-white/50 truncate capitalize">{{ user?.role || '' }}</p>
          </div>
        </div>
        <div v-else class="flex justify-center">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <User class="w-6 h-6" />
          </div>
        </div>
        <button
          @click="handleLogout"
          :class="[
            'w-full flex items-center space-x-3 px-4 py-2 rounded-lg transition-colors',
            isCollapsed ? 'justify-center' : '',
            'hover:bg-red-500/20 text-white'
          ]"
          :title="isCollapsed ? 'Déconnexion' : ''"
        >
          <LogOut class="w-5 h-5 shrink-0" />
          <span v-if="!isCollapsed" class="font-medium">Déconnexion</span>
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
/* Animation pour les transitions */
aside {
  transition: width 0.3s ease-in-out;
}

.custom-scrollbar {
  scrollbar-width: thin;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: 9999px;
}
</style>

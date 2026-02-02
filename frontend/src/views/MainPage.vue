<script setup lang="ts">
import { RouterView } from 'vue-router'
import Sidebar from '@/components/Sidebar.vue'
import { useSidebar } from '@/composables/useSidebar'

const { isCollapsed } = useSidebar()
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar fixe -->
    <Sidebar />

    <!-- Contenu principal qui s'adapte à la largeur de la sidebar -->
    <main :class="['transition-all duration-300', isCollapsed ? 'ml-20' : 'ml-64']">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>

</style>
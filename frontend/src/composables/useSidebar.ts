import { ref } from 'vue'

const isCollapsed = ref(false)

export const useSidebar = () => {
  const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value
  }

  return {
    isCollapsed,
    toggleSidebar
  }
}

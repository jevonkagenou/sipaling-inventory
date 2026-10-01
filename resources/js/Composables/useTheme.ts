import { ref, onMounted } from 'vue'

const isDark = ref(false)
let initialized = false

export function useTheme() {
  function applyTheme(dark: boolean) {
    isDark.value = dark
    if (typeof window !== 'undefined') {
      localStorage.setItem('sipaling-theme', dark ? 'dark' : 'light')
      if (dark) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    }
  }

  function toggleTheme() {
    applyTheme(!isDark.value)
  }

  function initTheme() {
    if (initialized) return
    initialized = true
    if (typeof window !== 'undefined') {
      const savedTheme = localStorage.getItem('sipaling-theme')
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      const prefersDark = savedTheme ? savedTheme === 'dark' : false
      applyTheme(prefersDark)
    }
  }

  onMounted(() => {
    initTheme()
  })

  return {
    isDark,
    toggleTheme,
    applyTheme,
    initTheme,
  }
}

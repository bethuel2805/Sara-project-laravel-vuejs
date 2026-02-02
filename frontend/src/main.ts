import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from "./routers/index.router"

const app = createApp(App)
app.use(router)

// L'authentification est initialisée automatiquement dans useAuth.ts
app.mount('#app')

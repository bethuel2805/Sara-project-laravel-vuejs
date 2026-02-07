import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const routes = [
    {
        name: "main-page",
        path: "/",
        component: () => import("../views/MainPage.vue"),
        redirect: "/dashboard",
        meta: {
            requiresAuth: true,
        },
        children: [
            {
                name: "dashboard",
                path: "/dashboard",
                component: () => import("../views/dashboard/Dashboard.vue")
            },
            {
                name: "movements",
                path: "/movements",
                component: () => import("../views/movements/MovementsPage.vue"),
                meta: {
                    requiresAuth: true,
                    requiresRole: ['admin', 'gestionnaire', 'observateur']
                }
            },
            {
                name: "products",
                path: "/products",
                component: () => import("../views/products/ProductsPage.vue"),
                meta: {
                    requiresAuth: true,
                    requiresRole: ['admin', 'gestionnaire', 'observateur']
                }
            },
            {
                name: "categories",
                path: "/categories",
                component: () => import("../views/categories/CategoriesPage.vue"),
                meta: {
                    requiresAuth: true,
                    requiresRole: ['admin', 'gestionnaire', 'observateur']
                }
            },
            {
                name: "inventories",
                path: "/inventories",
                component: () => import("../views/inventories/InventoriesPage.vue"),
                meta: {
                    requiresAuth: true,
                    requiresRole: ['admin', 'gestionnaire', 'observateur']
                }
            },
            {
                name: "predictions",
                path: "/predictions",
                component: () => import("../views/predictions/PredictionsPage.vue"),
                meta: {
                    requiresAuth: true,
                }
            },
            {
                name: "alerts",
                path: "/alerts",
                component: () => import("../views/alerts/AlertsPage.vue"),
                meta: {
                    requiresAuth: true,
                }
            },
            {
                name: "reports",
                path: "/reports",
                component: () => import("../views/reports/ReportsPage.vue"),
                meta: {
                    requiresAuth: true,
                }
            },
            {
                name: "exports",
                path: "/exports",
                component: () => import("../views/exports/ExportsPage.vue"),
                meta: {
                    requiresAuth: true,
                }
            },
            {
                name: "users",
                path: "/users",
                component: () => import("../views/users/UsersPage.vue"),
                meta: {
                    requiresAuth: true,
                    requiresRole: ['admin']
                }
            },
        ]
    },
    {
        name: "login-page",
        path: "/login",
        component: () => import("../views/auth/LoginPage.vue"),
    },
    {
        name: "register-page",
        path: "/register",
        component: () => import("../views/auth/RegisterPage.vue"),
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

// Guard de navigation (comme dans whatsapp-pro)
router.beforeEach(async (to, _, next) => {
    const { isAuthenticated, hasAnyRole, checkAuth } = useAuth()
    const authRequired = to.meta.requiresAuth as boolean | undefined
    const requiredRoles = to.meta.requiresRole as string[] | undefined

    // Cas 1 : Si la page nécessite une authentification et que l'utilisateur n'est pas connecté
    if (authRequired && !isAuthenticated.value) {
        return next({ name: 'login-page', query: { redirect: to.fullPath } })
    }

    // Cas 2 : Si l'utilisateur est connecté et tente d'accéder à la page de connexion/inscription
    if (!authRequired && isAuthenticated.value && (to.name === 'login-page' || to.name === 'register-page')) {
        return next({ name: 'dashboard' })
    }

    // Cas 3 : Si la page nécessite des rôles spécifiques
    if (authRequired && isAuthenticated.value && requiredRoles) {
        if (!hasAnyRole(requiredRoles as any)) {
            return next({ name: 'dashboard' }) // Rediriger vers le dashboard si pas les permissions
        }
    }

    // Cas 4 : Vérifier le token si authentifié
    if (authRequired && isAuthenticated.value) {
        const isValid = await checkAuth()
        if (!isValid) {
            return next({ name: 'login-page' })
        }
    }

    // Cas 5 : Si tout est ok, autorise la navigation
    next()
})

export default router
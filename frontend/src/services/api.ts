// export const API_BASE_URL = 'http://localhost:3000/api/v1'

   export const API_BASE_URL = 'http://localhost:8000/api/v1'

export function getAuthToken() {
  return sessionStorage.getItem('sara_token')
}

export async function apiFetch(path: string, options: RequestInit = {}) {
  const token = getAuthToken()

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  }

  if (token) {
    (headers as Record<string, string>)['Authorization'] = `Bearer ${token}`
  }

  const res = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  })

  // Gérer les erreurs d'authentification
  if (res.status === 401) {
    sessionStorage.removeItem('sara_token')
    sessionStorage.removeItem('sara_user')
    window.location.href = '/login'
    throw new Error('Session expirée. Veuillez vous reconnecter.')
  }

  if (!res.ok) {
    const errorBody = await res.json().catch(() => ({}))
    throw new Error(errorBody.message || `Erreur API (${res.status})`)
  }

  return res.json()
}


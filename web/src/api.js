const API_BASE =
  import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000/api/v1'

function getToken() {
  return localStorage.getItem('ttt_token')
}

export function setToken(token) {
  if (token) {
    localStorage.setItem('ttt_token', token)
  } else {
    localStorage.removeItem('ttt_token')
  }
}

export function clearToken() {
  localStorage.removeItem('ttt_token')
}

export async function api(path, { method = 'GET', body, auth = false } = {}) {
  const headers = { Accept: 'application/json' }
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  if (auth) {
    const token = getToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }
  }

  const response = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })

  const data = await response.json().catch(() => null)
  if (!response.ok) {
    const error = new Error(data?.error?.message || `HTTP ${response.status}`)
    error.code = data?.error?.code || 'HTTP_ERROR'
    error.status = response.status
    throw error
  }

  return data
}

export { API_BASE }

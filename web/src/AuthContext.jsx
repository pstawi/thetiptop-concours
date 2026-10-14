import { createContext, useContext, useEffect, useState } from 'react'
import { api, clearToken, setToken } from './api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  async function refreshMe() {
    try {
      const me = await api('/me', { auth: true })
      setUser(me)
      return me
    } catch {
      clearToken()
      setUser(null)
      return null
    }
  }

  useEffect(() => {
    const token = localStorage.getItem('ttt_token')
    if (!token) {
      setLoading(false)
      return
    }
    refreshMe().finally(() => setLoading(false))
  }, [])

  async function login(email, password) {
    const data = await api('/auth/login', {
      method: 'POST',
      body: { email, password },
    })
    setToken(data.token)
    setUser(data.user)
    return data.user
  }

  async function register(payload) {
    const data = await api('/auth/register', {
      method: 'POST',
      body: payload,
    })
    setToken(data.token)
    setUser(data.user)
    return data.user
  }

  async function loginOAuthMock(payload) {
    const data = await api('/auth/oauth/mock', {
      method: 'POST',
      body: payload,
    })
    setToken(data.token)
    setUser(data.user)
    return data.user
  }

  function logout() {
    clearToken()
    setUser(null)
  }

  return (
    <AuthContext.Provider
      value={{ user, loading, login, register, loginOAuthMock, logout, refreshMe, setUser }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth hors AuthProvider')
  }
  return ctx
}

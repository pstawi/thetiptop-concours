import { useEffect, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../AuthContext'

export default function ProfilePage() {
  const { user, loading, setUser } = useAuth()
  const [form, setForm] = useState({
    firstName: '',
    lastName: '',
    marketingOptIn: false,
  })
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (user) {
      setForm({
        firstName: user.firstName || '',
        lastName: user.lastName || '',
        marketingOptIn: !!user.marketingOptIn,
      })
    }
  }, [user])

  if (loading) {
    return <main className="page">Chargement…</main>
  }
  if (!user) {
    return <Navigate to="/login" replace />
  }

  async function onSubmit(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    setMessage('')
    try {
      const updated = await api('/me', {
        method: 'PATCH',
        auth: true,
        body: form,
      })
      setUser(updated)
      setMessage('Profil mis à jour.')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="page narrow">
      <h1>Profil</h1>
      <p className="muted">{user.email}</p>
      <form className="stack" onSubmit={onSubmit}>
        <label>
          Prénom
          <input
            value={form.firstName}
            onChange={(e) => setForm({ ...form, firstName: e.target.value })}
            required
          />
        </label>
        <label>
          Nom
          <input
            value={form.lastName}
            onChange={(e) => setForm({ ...form, lastName: e.target.value })}
            required
          />
        </label>
        <label className="check">
          <input
            type="checkbox"
            checked={form.marketingOptIn}
            onChange={(e) =>
              setForm({ ...form, marketingOptIn: e.target.checked })
            }
          />
          Opt-in marketing
        </label>
        {error && <p className="ko">{error}</p>}
        {message && <p className="ok">{message}</p>}
        <button className="btn" type="submit" disabled={busy}>
          Enregistrer
        </button>
      </form>
    </main>
  )
}

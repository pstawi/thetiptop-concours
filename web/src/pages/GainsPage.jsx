import { useEffect, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../AuthContext'

export default function GainsPage() {
  const { user, loading } = useAuth()
  const [items, setItems] = useState([])
  const [error, setError] = useState('')

  useEffect(() => {
    if (!user) {
      return
    }
    api('/me/participations', { auth: true })
      .then((data) => setItems(data.items || []))
      .catch((err) => setError(err.message))
  }, [user])

  if (loading) {
    return <main className="page">Chargement…</main>
  }
  if (!user) {
    return <Navigate to="/login" replace />
  }

  return (
    <main className="page narrow">
      <h1>Mes gains</h1>
      {error && <p className="ko">{error}</p>}
      {!error && items.length === 0 && <p>Aucun gain pour le moment.</p>}
      <ul className="gains-list">
        {items.map((item) => (
          <li key={item.id}>
            <div>
              <strong>{item.prize.label}</strong>
              <span className="muted">Code {item.code}</span>
            </div>
            <div className="meta">
              <span>{new Date(item.participatedAt).toLocaleString('fr-FR')}</span>
              <span>{item.claim?.status || '—'}</span>
            </div>
          </li>
        ))}
      </ul>
    </main>
  )
}

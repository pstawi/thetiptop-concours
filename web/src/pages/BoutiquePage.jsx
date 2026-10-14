import { useState } from 'react'
import { Navigate } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../AuthContext'

export default function BoutiquePage() {
  const { user, loading } = useAuth()
  const [query, setQuery] = useState('')
  const [items, setItems] = useState([])
  const [selected, setSelected] = useState(null)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [busy, setBusy] = useState(false)

  if (loading) {
    return <main className="page">Chargement…</main>
  }
  if (!user) {
    return <Navigate to="/login" replace />
  }
  if (!user.roles?.includes('ROLE_SHOP')) {
    return (
      <main className="page narrow">
        <h1>Boutique</h1>
        <p className="ko">Accès réservé au compte boutique.</p>
      </main>
    )
  }

  async function onSearch(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    setMessage('')
    setSelected(null)
    try {
      const data = await api(`/shop/claims?q=${encodeURIComponent(query)}`, {
        auth: true,
      })
      setItems(data.items || [])
      if ((data.items || []).length === 0) {
        setMessage('Aucun claim trouvé.')
      }
    } catch (err) {
      setError(err.message)
      setItems([])
    } finally {
      setBusy(false)
    }
  }

  async function onDeliver() {
    if (!selected) {
      return
    }
    const ok = window.confirm(
      `Confirmer la remise du lot « ${selected.prize?.label || 'gain'} » à ${selected.participant?.email || 'ce participant'} ?`
    )
    if (!ok) {
      return
    }
    const ok2 = window.confirm('Dernière confirmation : marquer ce claim comme REMIS ?')
    if (!ok2) {
      return
    }

    setBusy(true)
    setError('')
    setMessage('')
    try {
      const updated = await api(`/shop/claims/${selected.id}/deliver`, {
        method: 'POST',
        auth: true,
      })
      setMessage(`Lot remis (${updated.status}).`)
      setItems((prev) =>
        prev.map((item) => (item.id === updated.id ? updated : item))
      )
      setSelected(updated)
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="page narrow">
      <h1>Espace boutique</h1>
      <p className="lead">
        Recherchez un gain par email participant ou code ticket, puis marquez-le
        remis.
      </p>
      <form className="stack" onSubmit={onSearch}>
        <label>
          Email ou code
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="user@… ou AB12CD34EF"
            required
          />
        </label>
        <button className="btn" type="submit" disabled={busy}>
          Rechercher
        </button>
      </form>

      {error && <p className="ko">{error}</p>}
      {message && <p className="ok">{message}</p>}

      <ul className="gains-list">
        {items.map((item) => (
          <li key={item.id}>
            <button
              type="button"
              className={`claim-pick ${selected?.id === item.id ? 'active' : ''}`}
              onClick={() => setSelected(item)}
            >
              <div>
                <strong>{item.prize?.label || 'Gain'}</strong>
                <span className="muted">
                  {item.participant?.email} — {item.codeMasked || item.code}
                </span>
              </div>
              <span>{item.status}</span>
            </button>
          </li>
        ))}
      </ul>

      {selected && (
        <section className="win-box">
          <h2>Détail claim #{selected.id}</h2>
          <p>
            {selected.participant?.firstName} {selected.participant?.lastName} (
            {selected.participant?.email})
          </p>
          <p>
            Lot : <strong>{selected.prize?.label}</strong>
          </p>
          <p className="muted">Statut : {selected.status}</p>
          {selected.status === 'PENDING' ? (
            <button className="btn" type="button" disabled={busy} onClick={onDeliver}>
              Marquer remis
            </button>
          ) : (
            <p className="ok">Déjà traité — remise impossible.</p>
          )}
        </section>
      )}
    </main>
  )
}

import { useEffect, useState } from 'react'
import { Navigate } from 'react-router-dom'
import { api, API_BASE } from '../api'
import { useAuth } from '../AuthContext'

export default function AdminPage() {
  const { user, loading } = useAuth()
  const [stats, setStats] = useState(null)
  const [audit, setAudit] = useState([])
  const [phase, setPhase] = useState('OPEN')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [busy, setBusy] = useState(false)
  const [drawResult, setDrawResult] = useState(null)

  async function load() {
    const [s, a] = await Promise.all([
      api('/admin/stats', { auth: true }),
      api('/admin/audit', { auth: true }),
    ])
    setStats(s)
    setPhase(s.contest?.status || 'OPEN')
    setAudit(a.items || [])
  }

  useEffect(() => {
    if (!user?.roles?.includes('ROLE_ADMIN')) {
      return
    }
    load().catch((err) => setError(err.message))
  }, [user])

  if (loading) {
    return <main className="page">Chargement…</main>
  }
  if (!user) {
    return <Navigate to="/login" replace />
  }
  if (!user.roles?.includes('ROLE_ADMIN')) {
    return (
      <main className="page narrow">
        <h1>Admin</h1>
        <p className="ko">Accès réservé à l’administrateur.</p>
      </main>
    )
  }

  async function changePhase(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    setMessage('')
    try {
      await api('/admin/contest', {
        method: 'PATCH',
        auth: true,
        body: { status: phase },
      })
      setMessage(`Phase → ${phase}`)
      await load()
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function runDraw() {
    if (!window.confirm('Lancer le tirage du gros lot ? Irréversible.')) {
      return
    }
    setBusy(true)
    setError('')
    setMessage('')
    try {
      const result = await api('/admin/draw/grand-prize', {
        method: 'POST',
        auth: true,
      })
      setDrawResult(result)
      setMessage(`Gagnant : ${result.winner.email}`)
      await load()
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  function exportCsv() {
    const token = localStorage.getItem('ttt_token')
    fetch(`${API_BASE}/admin/exports/emails`, {
      headers: { Authorization: `Bearer ${token}` },
    })
      .then(async (r) => {
        if (!r.ok) {
          throw new Error('Export impossible')
        }
        return r.blob()
      })
      .then((blob) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = 'optin-emails.csv'
        a.click()
        URL.revokeObjectURL(url)
      })
      .catch((err) => setError(err.message))
  }

  return (
    <main className="page">
      <h1>Administration</h1>
      {error && <p className="ko">{error}</p>}
      {message && <p className="ok">{message}</p>}

      {stats && (
        <section className="contest-block">
          <h2>Stats</h2>
          <p>
            Phase : <strong>{stats.contest.status}</strong>
          </p>
          <p>
            Tickets : {stats.tickets.used} used / {stats.tickets.available}{' '}
            available / {stats.tickets.total} total
          </p>
          <p>
            Users : {stats.users.total} (opt-in {stats.users.optInPercent}%)
          </p>
          <p>
            Claims : {stats.claims.pending} pending / {stats.claims.delivered}{' '}
            delivered
          </p>
          <ul className="prize-list">
            {stats.prizes.map((p) => (
              <li key={p.code}>
                <strong>{p.label}</strong>
                <span>
                  {p.used} used ({p.percent}%)
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="contest-block">
        <h2>Phase concours</h2>
        <form className="stack" onSubmit={changePhase}>
          <label>
            Nouvelle phase
            <select value={phase} onChange={(e) => setPhase(e.target.value)}>
              <option value="PREVIEW">PREVIEW</option>
              <option value="OPEN">OPEN</option>
              <option value="CLAIM">CLAIM</option>
              <option value="CLOSED">CLOSED</option>
            </select>
          </label>
          <button className="btn" type="submit" disabled={busy}>
            Mettre à jour
          </button>
        </form>
      </section>

      <section className="contest-block">
        <h2>Export & tirage</h2>
        <div className="cta-row">
          <button className="btn ghost" type="button" onClick={exportCsv}>
            Export CSV opt-in
          </button>
          <button className="btn" type="button" disabled={busy} onClick={runDraw}>
            Tirage gros lot
          </button>
        </div>
        {drawResult && (
          <p className="ok">
            Draw #{drawResult.drawId} — {drawResult.winner.firstName}{' '}
            {drawResult.winner.lastName} ({drawResult.winner.email}) — éligibles{' '}
            {drawResult.eligibleCount}
          </p>
        )}
      </section>

      <section className="contest-block">
        <h2>Audit (100 derniers)</h2>
        <ul className="gains-list">
          {audit.map((row) => (
            <li key={row.id}>
              <div>
                <strong>{row.action}</strong>
                <span className="muted">
                  {row.actorEmail || 'system'} — {row.entity}
                </span>
              </div>
              <span className="muted">
                {new Date(row.createdAt).toLocaleString('fr-FR')}
              </span>
            </li>
          ))}
        </ul>
      </section>
    </main>
  )
}

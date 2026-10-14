import { useEffect, useState } from 'react'
import { Link, Navigate } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../AuthContext'

export default function PlayPage() {
  const { user, loading } = useAuth()
  const [code, setCode] = useState('')
  const [contest, setContest] = useState(null)
  const [result, setResult] = useState(null)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api('/contest').then(setContest).catch(() => {})
  }, [])

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
    setResult(null)
    try {
      const data = await api('/codes/redeem', {
        method: 'POST',
        auth: true,
        body: { code },
      })
      setResult(data)
      setCode('')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="page narrow">
      <h1>Jouer</h1>
      {contest && (
        <p className="lead">
          Phase concours : <strong>{contest.status}</strong>
          {contest.status !== 'OPEN' &&
            ' — la saisie de codes n’est possible qu’en phase OPEN.'}
        </p>
      )}
      <form className="stack" onSubmit={onSubmit}>
        <label>
          Code ticket (10 caractères)
          <input
            value={code}
            onChange={(e) => setCode(e.target.value.toUpperCase())}
            maxLength={10}
            minLength={10}
            pattern="[A-Z0-9]{10}"
            placeholder="AB12CD34EF"
            required
          />
        </label>
        {error && <p className="ko">{error}</p>}
        <button className="btn" type="submit" disabled={busy}>
          Valider mon code
        </button>
      </form>
      {result && (
        <section className="win-box" aria-live="polite">
          <h2>Bravo !</h2>
          <p>
            Vous avez gagné : <strong>{result.prize.label}</strong>
          </p>
          <p>{result.instructions}</p>
          <Link to="/gains">Voir mes gains</Link>
        </section>
      )}
    </main>
  )
}

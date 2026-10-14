import { useEffect, useState } from 'react'
import './App.css'

const API_BASE =
  import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000/api/v1'

function App() {
  const [health, setHealth] = useState({ status: 'loading', detail: null })

  useEffect(() => {
    let cancelled = false

    fetch(`${API_BASE}/health`)
      .then(async (response) => {
        const body = await response.json().catch(() => null)
        if (cancelled) {
          return
        }
        if (!response.ok) {
          setHealth({ status: 'error', detail: `HTTP ${response.status}` })
          return
        }
        setHealth({ status: 'ok', detail: body })
      })
      .catch((error) => {
        if (!cancelled) {
          setHealth({ status: 'error', detail: error.message })
        }
      })

    return () => {
      cancelled = true
    }
  }, [])

  return (
    <main className="page">
      <p className="brand">Thé Tip Top</p>
      <h1>Jeu-concours</h1>
      <p className="lead">
        Socle local — front React (Vite) connecté à l&apos;API Symfony.
      </p>
      <section className="health" aria-live="polite">
        <h2>API health</h2>
        {health.status === 'loading' && <p>Vérification en cours…</p>}
        {health.status === 'ok' && (
          <p className="ok">
            Connecté — {health.detail?.service} ({health.detail?.version})
          </p>
        )}
        {health.status === 'error' && (
          <p className="ko">
            API injoignable ({String(health.detail)}). Lancez Symfony sur le port
            8000.
          </p>
        )}
      </section>
      <footer>
        Projet étudiant fictif — Furious Ducks / Thé Tip Top
      </footer>
    </main>
  )
}

export default App

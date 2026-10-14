import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../AuthContext'

export default function HomePage() {
  const { user } = useAuth()
  const [contest, setContest] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    api('/contest')
      .then(setContest)
      .catch((err) => setError(err.message))
  }, [])

  return (
    <main className="page hero-page">
      <p className="brand">Thé Tip Top</p>
      <h1>Jeu-concours</h1>
      <p className="lead">
        Achetez pour plus de 49&nbsp;€, scrappez votre ticket, et tentez de gagner
        l’un de nos lots — remise en boutique uniquement.
      </p>
      <div className="cta-row">
        {user ? (
          <Link className="btn" to="/jouer">
            Jouer maintenant
          </Link>
        ) : (
          <Link className="btn" to="/register">
            Participer
          </Link>
        )}
        <Link className="btn ghost" to="/reglement">
          Règlement
        </Link>
      </div>

      {error && <p className="ko">{error}</p>}
      {contest && (
        <section className="contest-block">
          <h2>Phase actuelle : {contest.status}</h2>
          <ul className="prize-list">
            {contest.prizes?.map((prize) => (
              <li key={prize.code}>
                <strong>{prize.label}</strong>
                <span>{prize.percent}&nbsp;%</span>
              </li>
            ))}
          </ul>
          {contest.grandPrize && (
            <p className="grand">
              Gros lot : {contest.grandPrize.label} — {contest.grandPrize.note}
            </p>
          )}
        </section>
      )}
    </main>
  )
}

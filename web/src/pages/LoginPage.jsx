import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../AuthContext'

export default function LoginPage() {
  const { login, loginOAuthMock } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function onSubmit(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    try {
      await login(email, password)
      navigate('/jouer')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  async function onMock(provider) {
    setBusy(true)
    setError('')
    try {
      await loginOAuthMock({
        provider,
        email: `${provider}.player@thetiptop.local`,
        firstName: provider === 'google' ? 'Google' : 'Facebook',
        lastName: 'Player',
      })
      navigate('/jouer')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="page narrow">
      <h1>Connexion</h1>
      <form className="stack" onSubmit={onSubmit}>
        <label>
          Email
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
        </label>
        <label>
          Mot de passe
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </label>
        {error && <p className="ko">{error}</p>}
        <button className="btn" type="submit" disabled={busy}>
          Se connecter
        </button>
      </form>
      <div className="oauth-row">
        <button type="button" className="btn ghost" disabled={busy} onClick={() => onMock('google')}>
          Continuer avec Google (mock)
        </button>
        <button type="button" className="btn ghost" disabled={busy} onClick={() => onMock('facebook')}>
          Continuer avec Facebook (mock)
        </button>
      </div>
      <p>
        Pas de compte ? <Link to="/register">Inscription</Link>
      </p>
    </main>
  )
}

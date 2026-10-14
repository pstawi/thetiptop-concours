import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../AuthContext'

export default function RegisterPage() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    email: '',
    password: '',
    firstName: '',
    lastName: '',
    marketingOptIn: false,
    acceptCgu: false,
  })
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  function update(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    try {
      await register(form)
      navigate('/jouer')
    } catch (err) {
      setError(err.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="page narrow">
      <h1>Inscription</h1>
      <form className="stack" onSubmit={onSubmit}>
        <label>
          Prénom
          <input
            value={form.firstName}
            onChange={(e) => update('firstName', e.target.value)}
            required
          />
        </label>
        <label>
          Nom
          <input
            value={form.lastName}
            onChange={(e) => update('lastName', e.target.value)}
            required
          />
        </label>
        <label>
          Email
          <input
            type="email"
            value={form.email}
            onChange={(e) => update('email', e.target.value)}
            required
          />
        </label>
        <label>
          Mot de passe
          <input
            type="password"
            value={form.password}
            onChange={(e) => update('password', e.target.value)}
            required
            minLength={8}
          />
        </label>
        <label className="check">
          <input
            type="checkbox"
            checked={form.acceptCgu}
            onChange={(e) => update('acceptCgu', e.target.checked)}
            required
          />
          J’accepte les CGU et la politique de confidentialité
        </label>
        <label className="check">
          <input
            type="checkbox"
            checked={form.marketingOptIn}
            onChange={(e) => update('marketingOptIn', e.target.checked)}
          />
          Je souhaite recevoir des offres (opt-in)
        </label>
        {error && <p className="ko">{error}</p>}
        <button className="btn" type="submit" disabled={busy}>
          Créer mon compte
        </button>
      </form>
      <p>
        Déjà inscrit ? <Link to="/login">Connexion</Link>
      </p>
    </main>
  )
}

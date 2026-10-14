import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { setToken } from '../api'
import { useAuth } from '../AuthContext'

export default function OAuthCallbackPage() {
  const [params] = useSearchParams()
  const { refreshMe, loginOAuthMock } = useAuth()
  const navigate = useNavigate()
  const [error, setError] = useState('')

  useEffect(() => {
    const token = params.get('token')
    const mock = params.get('mock')
    const provider = params.get('provider') || 'google'

    async function run() {
      try {
        if (token) {
          setToken(token)
          await refreshMe()
          navigate('/jouer', { replace: true })
          return
        }
        if (mock === '1') {
          await loginOAuthMock({
            provider,
            email: `${provider}.callback@thetiptop.local`,
            firstName: 'OAuth',
            lastName: 'Callback',
          })
          navigate('/jouer', { replace: true })
          return
        }
        setError('Callback OAuth incomplet.')
      } catch (err) {
        setError(err.message)
      }
    }

    run()
  }, [params, refreshMe, loginOAuthMock, navigate])

  return (
    <main className="page narrow">
      <h1>OAuth</h1>
      {error ? (
        <>
          <p className="ko">{error}</p>
          <Link to="/login">Retour connexion</Link>
        </>
      ) : (
        <p>Finalisation de la connexion…</p>
      )}
    </main>
  )
}

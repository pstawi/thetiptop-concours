import { Link, NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../AuthContext'

export default function Layout() {
  const { user, logout } = useAuth()

  return (
    <div className="shell">
      <header className="topbar">
        <Link to="/" className="brand-link">
          Thé Tip Top
        </Link>
        <nav>
          <NavLink to="/">Accueil</NavLink>
          {user ? (
            <>
              <NavLink to="/jouer">Jouer</NavLink>
              <NavLink to="/gains">Mes gains</NavLink>
              <NavLink to="/profil">Profil</NavLink>
              <button type="button" className="linkish" onClick={logout}>
                Déconnexion
              </button>
            </>
          ) : (
            <>
              <NavLink to="/login">Connexion</NavLink>
              <NavLink to="/register">Inscription</NavLink>
            </>
          )}
        </nav>
      </header>
      <Outlet />
      <footer className="site-footer">
        Projet étudiant fictif — Furious Ducks / Thé Tip Top
      </footer>
    </div>
  )
}

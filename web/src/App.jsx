import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './AuthContext'
import Layout from './components/Layout'
import AdminPage from './pages/AdminPage'
import BoutiquePage from './pages/BoutiquePage'
import GainsPage from './pages/GainsPage'
import HomePage from './pages/HomePage'
import LegalPage from './pages/LegalPage'
import LoginPage from './pages/LoginPage'
import OAuthCallbackPage from './pages/OAuthCallbackPage'
import PlayPage from './pages/PlayPage'
import ProfilePage from './pages/ProfilePage'
import RegisterPage from './pages/RegisterPage'
import './App.css'

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<Layout />}>
            <Route index element={<HomePage />} />
            <Route path="login" element={<LoginPage />} />
            <Route path="register" element={<RegisterPage />} />
            <Route path="jouer" element={<PlayPage />} />
            <Route path="gains" element={<GainsPage />} />
            <Route path="profil" element={<ProfilePage />} />
            <Route path="boutique" element={<BoutiquePage />} />
            <Route path="admin" element={<AdminPage />} />
            <Route path="oauth/callback" element={<OAuthCallbackPage />} />
            <Route
              path="reglement"
              element={
                <LegalPage title="Règlement">
                  <p>
                    Texte règlement placeholder — jeu-concours Thé Tip Top (projet
                    étudiant fictif).
                  </p>
                </LegalPage>
              }
            />
            <Route
              path="cgu"
              element={
                <LegalPage title="CGU">
                  <p>Conditions générales d’utilisation — placeholder.</p>
                </LegalPage>
              }
            />
            <Route
              path="confidentialite"
              element={
                <LegalPage title="Confidentialité">
                  <p>Politique de confidentialité RGPD — placeholder.</p>
                </LegalPage>
              }
            />
            <Route
              path="mentions-legales"
              element={
                <LegalPage title="Mentions légales">
                  <p>
                    Mentions légales placeholder. Projet étudiant fictif — Furious
                    Ducks / Thé Tip Top.
                  </p>
                </LegalPage>
              }
            />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

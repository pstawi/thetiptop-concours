# Thé Tip Top — Jeu-concours

Application web du jeu-concours Thé Tip Top (Objectif 2 — Furious Ducks).

> Projet étudiant fictif.

## Stack

| Couche | Techno |
| --- | --- |
| Front | React (JavaScript) + Vite — `web/` |
| API | Symfony 7 — `api/` |
| BDD | PostgreSQL 16 |
| Emails locaux | Mailpit |
| Auth (plus tard) | JWT Bearer + formulaire + OAuth (mock si pas de clés) |

## Prérequis

- PHP ≥ 8.2 + Composer
- Node.js ≥ 20 + npm
- Docker Desktop (Compose)
- Symfony CLI (recommandé) : https://symfony.com/download

## Démarrage local (PowerShell)

Depuis la racine du dépôt `thetiptop-concours` :

### 1. Variables d’environnement

```powershell
Copy-Item .env.example .env
Copy-Item api\.env.example api\.env
Copy-Item web\.env.example web\.env
```

Si `api/.env` existe déjà (créé par Composer), vérifiez au minimum `CORS_ALLOW_ORIGIN`, `DATABASE_URL` et `MAILER_DSN`.

### 2. Dépendances

```powershell
cd api
composer install
cd ..\web
npm install
cd ..
```

### 3. PostgreSQL + Mailpit

```powershell
docker compose -f docker\docker-compose.yml up -d
```

- Postgres : `127.0.0.1:5432` (user/mdp/db : `thetiptop`)
- Mailpit UI : http://127.0.0.1:8025
- SMTP : `127.0.0.1:1025`

Arrêt :

```powershell
docker compose -f docker\docker-compose.yml down
```

### 4. API Symfony

```powershell
cd api
symfony server:start -d --port=8000
cd ..
```

Healthcheck : http://127.0.0.1:8000/api/v1/health

Sans Symfony CLI :

```powershell
cd api
php -S 127.0.0.1:8000 -t public
```

### 5. Front Vite

```powershell
cd web
npm run dev
```

Ouvrir http://127.0.0.1:5173 — la page affiche l’état du healthcheck API.

## Structure

```
api/                 Symfony 7 (API /api/v1)
web/                 React + Vite
docker/              Compose local (postgres, mailpit)
.env.example         Variables Docker (racine)
api/.env.example     Variables API
web/.env.example     Variables front (VITE_*)
```

## Cahier des charges

Le CDC exécutable reste dans le dépôt pédagogique du mastère :

`projet mastere/rendu/objectif-2/cahier/CDC_Objectif2_Implementation.md`

Ce dépôt ne contient que le code de l’application.

## Statut

Étape 1 — socle local (API health, CORS, Docker postgres/mailpit, front Vite).

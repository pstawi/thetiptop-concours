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
- Extension PHP `pdo_pgsql` (WAMP : décommenter `extension=pdo_pgsql` et `extension=pgsql` dans `php.ini`)
- PostgreSQL 16+ local **ou** Docker Desktop (Compose)
- Ajouter `C:\Program Files\PostgreSQL\<version>\bin` au PATH (pour `libpq` / `psql`)
- Node.js ≥ 20 + npm
- Symfony CLI (recommandé) : https://symfony.com/download
- Mailpit (Docker) optionnel en local pour les emails — sinon plus tard

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

### 6. Migrations + seed concours

Avec le service PHP Docker (recommandé sous Windows / WAMP sans `pdo_pgsql`) :

```powershell
docker compose -f docker\docker-compose.yml up -d postgres mailpit
docker compose -f docker\docker-compose.yml build php
docker compose -f docker\docker-compose.yml run --rm php php bin/console doctrine:migrations:migrate --no-interaction
```

Seed **500 000** codes (long : typiquement quelques minutes selon machine) :

```powershell
docker compose -f docker\docker-compose.yml run --rm php php bin/console app:seed-contest
```

Options :

| Option | Effet |
| --- | --- |
| `--force` | Purge tickets / lots / état concours puis re-seed (**refusé si `APP_ENV=prod`**) |
| `--tickets=1000` | Total réduit pour smoke-test local (répartition proportionnelle) |

Si le seed a déjà tourné, relancer sans `--force` échoue volontairement.

Comptes démo créés :

| Email | Mot de passe | Rôle |
| --- | --- | --- |
| `admin@thetiptop.local` | `Admin123!` | `ROLE_ADMIN` |
| `shop@thetiptop.local` | `Shop123!` | `ROLE_SHOP` |
| `user@thetiptop.local` | `User123!` | `ROLE_USER` |

Contrôle d’intégrité post-seed : total = cible et chaque `COUNT(prize_type) = stock_target` (échec sinon).

**Temps d’exécution mesuré (local, 2026-10-14)** : seed **500 000** tickets en **~51 s** (PostgreSQL 18 natif Windows, PHP 8.2, batches de 2000 inserts). Le smoke-test `--tickets=1000` tourne en ~4 s. La commande affiche aussi la durée à chaque run.

Sans Docker PHP (PHP hôte avec `pdo_pgsql`) :

```powershell
cd api
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-contest
```

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

Étape 2 — modèle de données Doctrine, migration, CLI `app:seed-contest` (500k codes + intégrité %).

# Installation & Troubleshooting Guide (`INSTALL.md`)

## Prerequisites

- **Option A (Recommended — Single Command Docker Setup)**:
  - Docker Engine & Docker Compose (`docker compose version`)
  - Ports `8000` (Web App) and `3306` (MySQL 8) free on localhost
- **Option B (Local Native Setup without Docker)**:
  - PHP 8.2+ (with `pdo_mysql`, `intl`, `zip`, `xml`, `ctype`, `iconv`)
  - Composer 2.x
  - MySQL 8.0 server

---

## Option A — Quick Start with Docker Compose (1 Command)

From the project root directory (`retaurant/`):

```bash
docker compose up --build
```

What happens automatically on startup (`docker/entrypoint.sh`):
1. Starts the `mysql:8.0` container (`db`) with database `restaurant` (`restaurant` / `restaurant`).
2. Waits until MySQL passes its readiness check.
3. Runs `php bin/console doctrine:migrations:migrate --no-interaction`.
4. Loads the demo fixtures via `php bin/console doctrine:fixtures:load --no-interaction` (50 ingredients, 20 seasonal dishes, 6 base preparations, 5 suppliers, 3 menus, 3 staff roles, and sample orders).
5. Serves the application at **http://localhost:8000**.

---

## Option B — Local Native Installation

1. **Install PHP dependencies**:
   ```bash
   composer install
   ```
2. **Configure `.env`** (already pre-configured with default credentials):
   ```dotenv
   DATABASE_URL="mysql://restaurant:restaurant@127.0.0.1:3306/restaurant?serverVersion=8.0&charset=utf8mb4"
   ```
3. **Create database, run migrations, and load demo data**:
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console doctrine:fixtures:load --no-interaction
   ```
4. **Start the local web server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   Open **http://127.0.0.1:8000**.

---

## Demo Accounts & Credentials

All demo accounts share the password `password`:

| Role | Email | Password | Accessible Screens |
|---|---|---|---|
| **Manager** (`ROLE_MANAGER`) | `manager@restaurant.local` | `password` | All screens: Dashboard (`/dashboard`), Back-Office (`/backoffice`), Waiter (`/waiter`), Kitchen (`/kitchen`), Menu (`/`) |
| **Waiter** (`ROLE_WAITER`) | `waiter@restaurant.local` | `password` | Waiter Order Screen (`/waiter`), Menu (`/`) |
| **Kitchen / Chef** (`ROLE_KITCHEN`) | `kitchen@restaurant.local` | `password` | Kitchen Display Screen (`/kitchen`), Menu (`/`) |

---

## Running Automated Tests

```bash
./vendor/bin/phpunit
```

---

## Common Troubleshooting

- **Port `3306` or `8000` already in use**:
  Stop any local MySQL service (`sudo systemctl stop mysql`) or change the host port mapping in `compose.yaml` (e.g., `"8080:8000"`).
- **Resetting the database and demo fixtures**:
  ```bash
  docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
  ```
- **Open Food Facts API timeout / offline environment**:
  The application handles offline environments gracefully by keeping locally seeded allergens on each ingredient and displaying a clear notification in `/backoffice`.

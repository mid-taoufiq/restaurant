# Le Bistro 60 — Restaurant Management System (PFE Challenge EMC)

## Chosen Subject
**Subject 2 — Gestion d'un restaurant : carte, cuisine et stocks** (*Restaurant Management: Menu, Kitchen, and Inventory*).

Built with **Symfony 7.4**, **PHP 8.3**, **MySQL 8.0**, **Doctrine ORM & Migrations**, **Twig**, and **PHPUnit 12**.

---

## Levels Reached

| Level | Theme | Status | Summary of Implementation |
|---|---|---|---|
| **Level 1** | Modéliser | ✅ Completed | Complete domain model (12 entities), MySQL 8 migration, realistic fixtures (50 ingredients, 20 dishes, 6 reusable base preparations, 5 suppliers, 3 composed menus), and Back-Office UI (`/backoffice`). |
| **Level 2** | Faire respecter les règles | ✅ Completed | Automatic stock deduction on dish service (`serveOrderItem`), automatic dish unavailability when any ingredient is short, unit conversions (`kg/g`, `L/mL`, `piece`), physical inventory count with discrepancy calculation, loss recording, historical cost snapshot, and automated PHPUnit test suite. |
| **Level 3** | En faire un produit | ✅ Completed | Role-based authentication (`ROLE_MANAGER`, `ROLE_WAITER`, `ROLE_KITCHEN`), Dining Room Waiter Order screen (`/waiter`), Kitchen Display Screen (`/kitchen`), and Manager Dashboard (`/dashboard`: dish margins, best-sellers, restocking alerts). |
| **Level 4** | S'ouvrir au monde | ✅ Completed | **Open Food Facts API** integration by barcode (`OpenFoodFactsService`) to fetch/normalize product allergens and propagate them automatically to dishes on the menu card. |
| **Level 5** | Livrer | ✅ Completed | Single-command `docker compose up --build` environment (`Dockerfile`, `compose.yaml`, `docker/entrypoint.sh`) with automatic migrations + fixtures and step-by-step `INSTALL.md`. |

---

## What Works
- **Seasonal Menu & Allergens (`/`)**: Displays composed menus and all 20 dishes with real-time availability and aggregated allergens from direct ingredients and base preparations.
- **Waiter Order Taking (`/waiter`)**: Allows dining room staff to create orders by table number (1–60), blocks ordering unavailable dishes, and transmits orders to the kitchen.
- **Kitchen Display (`/kitchen`)**: Tracks dish preparation lifecycle (`pending -> preparing -> ready -> served`). Marking a dish `served` deducts recipe & base preparation ingredients in their converted stock units and snapshots the dish cost.
- **Manager Dashboard (`/dashboard`)**: Computes real-time recipe cost and gross margin (€ and %) for every dish, ranks best-selling served dishes with realized historical margins, and lists low-stock restocking suggestions by supplier.
- **Back-Office & Inventory (`/backoffice`)**: Record supplier deliveries, log kitchen waste/losses, run physical inventory counts with automatic discrepancy calculation, create dishes, and sync ingredient allergens via Open Food Facts barcodes.

## What Remains for Future Iterations (With 1 Extra Week)
- Real-time WebSocket / Mercure push notifications between `/waiter` and `/kitchen` without page refresh.
- Multi-choice selection workflow inside composed menus on the waiter order pad.
- PDF export for supplier purchase orders and monthly margin accounting reports.

---

## How to Launch the Project

### With Docker Compose (Recommended)
```bash
docker compose up --build
```
Then open **http://localhost:8000** (see [INSTALL.md](INSTALL.md) for demo accounts and native setup instructions).

### Run Automated Tests
```bash
./vendor/bin/phpunit
```

## Documentation Index
- [INSTALL.md](INSTALL.md) — Step-by-step setup, demo credentials, and troubleshooting.
- [docs/MODELE.md](docs/MODELE.md) — Mermaid ER diagram and domain modeling choices.
- [docs/DECISIONS.md](docs/DECISIONS.md) — Chronological engineering log and justified answers to the 5 "Questions à trancher".

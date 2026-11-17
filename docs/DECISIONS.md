# Architectural & Domain Decisions Log (`docs/DECISIONS.md`)

## Chronological Journal

- **2026-11-15 (Morning) — Stack & Architecture**: Selected standard monolithic Symfony 7.4 with Twig server-side rendering and a focused service layer (`RestaurantService`, `UnitConverter`, `OpenFoodFactsService`). Kept UI minimal and fast for dining-room and kitchen tablets.
- **2026-11-15 (Afternoon) — Domain Schema & Base Preparations**: Modeled `Preparation` as a batch recipe yielding `yieldPortions` portions. A dish can combine direct `DishIngredient` items and fractional or whole `DishPreparation` portions.
- **2026-11-16 (Morning) — Stock Integrity & Unit Conversion**: Enforced strict unit dimension checks (`kg <-> g`, `L <-> mL`, `piece <-> piece`) and non-negative stock invariants inside `RestaurantService`.
- **2026-11-16 (Afternoon) — Role Hierarchy**: Configured `ROLE_MANAGER` to inherit `ROLE_WAITER` and `ROLE_KITCHEN` in `security.yaml` so the restaurant manager can inspect every operational screen in addition to `/dashboard` and `/backoffice`.
- **2026-11-16 (Evening) — Open Food Facts Resilience**: Designed `OpenFoodFactsService` with a 5-second timeout and non-destructive allergen merging so network outages or incomplete external product cards never wipe out chef-declared allergens.
- **2026-11-17 — Docker Automation**: Added `docker/entrypoint.sh` to automatically wait for MySQL 8 health, run Doctrine migrations, and seed demo fixtures on `docker compose up`.

---

## Answers to the 5 "Questions à trancher" (Subject 2)

### 1. Does the cost of an already-served dish change if the purchase price of an ingredient changes later?
- **Decision**: **No.**
- **Justification**: Financial accounting requires historical accuracy. When an `OrderItem` transitions to the `served` status in the kitchen, `RestaurantService::serveOrderItem()` computes the dish's recipe cost at that exact instant and stores it in `OrderItem.unitCostSnapshot` (and logs `unitCostAtMovement` on each `StockMovement`). When a supplier later delivers an ingredient at a higher or lower `unitCost`, the live margin table on the Manager Dashboard updates the *current* theoretical cost of future dishes, while realized margins on already-served orders remain locked to their historical `unitCostSnapshot`.

### 2. How are losses (breakage, expiration, kitchen error) recorded?
- **Decision**: **Via an explicit `loss` stock movement (`StockMovement.type = 'loss'`) with a mandatory reason.**
- **Justification**: Losses must be distinguished from customer consumption (`service_consumption`) and unexplained inventory shrinkage (`inventory_adjustment`). In `/backoffice`, the manager or chef records a loss by selecting the ingredient, the lost quantity + unit, and the cause (e.g., *Expired package*, *Dropped bottle*, *Burned batch*). `RestaurantService::recordLoss()` converts the quantity to the ingredient's stock unit, verifies stock does not drop below zero, decrements `currentStock`, and persists a `StockMovement` with `type = 'loss'` and the current `unitCostAtMovement` so waste cost can be audited.

### 3. Is a "starter + main + dessert" menu a dish like any other?
- **Decision**: **No — `Menu` is modeled as a distinct commercial bundle (`Menu` entity with a `ManyToMany` relation to `Dish`) rather than a `Dish`.**
- **Justification**: A `Dish` represents a single kitchen recipe with deterministic ingredients and a single kitchen preparation lifecycle (`pending -> preparing -> ready -> served`). A composed `Menu` (formula) is a commercial pricing grouping of multiple dishes (e.g., *Starter + Main + Dessert*) that are prepared and served at different paces in the kitchen. Modeling `Menu` separately preserves per-dish kitchen tracking and ingredient deduction while displaying bundled formula prices on the menu card.

### 4. Can an ingredient's stock become negative?
- **Decision**: **No — negative stock is strictly forbidden (`currentStock >= 0`).**
- **Justification**: Allowing negative stock hides unrecorded deliveries or recipe unit errors. Instead:
  1. Dishes whose required ingredients exceed `currentStock` automatically become **unavailable** on the menu and waiter ordering screen (`RestaurantService::isDishAvailable()`).
  2. Attempting to serve a dish or record a loss that exceeds available stock throws a `\DomainException` before any database change is flushed.
  3. If a physical delivery was forgotten in the system, the manager records the supplier purchase (`recordPurchase`) or performs a physical inventory count (`performPhysicalInventory`) first.

### 5. What should be done when allergen information from Open Food Facts is absent or inconsistent?
- **Decision**: **Combine local manual declarations with non-destructive Open Food Facts merging and safe fallback.**
- **Justification**: External crowd-sourced APIs like Open Food Facts can have missing `allergens_tags`, inconsistent language prefixes (`en:milk`, `fr:lait`), or temporary network timeouts. Furthermore, fresh raw produce (e.g., fresh fish or celery without a barcode) still carries allergens. Therefore:
  1. Every `Ingredient` stores a local JSON `allergens` array editable/seeded by the kitchen.
  2. When syncing via barcode (`OpenFoodFactsService::syncIngredientByBarcode()`), language prefixes (`en:`, `fr:`) are stripped and normalized, and discovered tags are **merged** (`array_unique(array_merge(...))`) with existing allergens rather than overwriting them with an empty array when a barcode card is incomplete.
  3. If the API is unreachable or the barcode is unknown, the service keeps the local allergens intact and alerts the manager via a flash message.

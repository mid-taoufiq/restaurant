# Data Model — Restaurant Management (`Le Bistro 60`)

## Entity-Relationship Diagram (Mermaid)

```mermaid
erDiagram
    SUPPLIER ||--o{ INGREDIENT : "supplies"
    INGREDIENT ||--o{ STOCK_MOVEMENT : "tracks"
    INGREDIENT ||--o{ PREPARATION_ITEM : "used in"
    PREPARATION ||--o{ PREPARATION_ITEM : "contains"
    DISH ||--o{ DISH_INGREDIENT : "requires direct"
    INGREDIENT ||--o{ DISH_INGREDIENT : "used in"
    DISH ||--o{ DISH_PREPARATION : "reuses base"
    PREPARATION ||--o{ DISH_PREPARATION : "used in"
    MENU }o--o{ DISH : "composes"
    CUSTOMER_ORDER ||--o{ ORDER_ITEM : "contains"
    DISH ||--o{ ORDER_ITEM : "ordered as"

    SUPPLIER {
        int id PK
        string name
        string email
        string phone
    }
    INGREDIENT {
        int id PK
        int supplier_id FK
        string name
        string unit "kg, g, L, mL, piece"
        float current_stock
        float min_stock
        float unit_cost
        string barcode "EAN-13 Open Food Facts"
        json allergens
    }
    PREPARATION {
        int id PK
        string name
        float yield_portions
    }
    PREPARATION_ITEM {
        int id PK
        int preparation_id FK
        int ingredient_id FK
        float quantity
        string unit
    }
    DISH {
        int id PK
        string name
        string category "starter, main, dessert, drink"
        float price
        bool is_active
    }
    DISH_INGREDIENT {
        int id PK
        int dish_id FK
        int ingredient_id FK
        float quantity
        string unit
    }
    DISH_PREPARATION {
        int id PK
        int dish_id FK
        int preparation_id FK
        float portions
    }
    MENU {
        int id PK
        string name
        float price
        string description
    }
    CUSTOMER_ORDER {
        int id PK
        int table_number
        string status "open, closed"
        string waiter_name
        datetime created_at
    }
    ORDER_ITEM {
        int id PK
        int customer_order_id FK
        int dish_id FK
        int quantity
        float unit_price
        float unit_cost_snapshot
        string status "pending, preparing, ready, served, cancelled"
    }
    STOCK_MOVEMENT {
        int id PK
        int ingredient_id FK
        float quantity_delta
        string type "purchase, service_consumption, loss, inventory_adjustment"
        string reason
        float unit_cost_at_movement
        datetime created_at
    }
    APP_USER {
        int id PK
        string email UK
        json roles "ROLE_MANAGER, ROLE_WAITER, ROLE_KITCHEN"
        string password
        string full_name
    }
```

## Explanation of Modeling Choices

1. **Separating Direct Ingredients (`DishIngredient`) and Base Preparations (`Preparation` / `DishPreparation`)**:
   - In a real kitchen, chefs prepare shared bases (e.g., *Classic Tomato Sauce*, *Béchamel*, *Pizza Dough*, *Rich Beef Stock*) in batches of $N$ portions (`yieldPortions`).
   - Modeling `Preparation` with its own `PreparationItem` recipe lines and linking `Dish` to `Preparation` via `DishPreparation` avoids duplicating recipe ingredients across multiple dishes while allowing exact cost and stock deduction per portion served.
2. **Explicit Unit Storage & Conversion (`UnitConverter`)**:
   - Each `Ingredient` stores its stock and unit cost in a canonical unit (`kg`, `g`, `L`, `mL`, or `piece`), while recipe lines (`DishIngredient`, `PreparationItem`) can specify convenient culinary units (e.g., `150 g` of Mozzarella on a dish when Mozzarella is stocked in `kg`).
   - `UnitConverter` converts between compatible units within the same physical dimension (`mass`, `volume`, `count`) and rejects invalid cross-dimension conversions.
3. **Immutable Audit Trail (`StockMovement`) + Historical Cost Snapshot (`OrderItem.unitCostSnapshot`)**:
   - Every change to `Ingredient.currentStock`—whether from supplier purchases (`purchase`), serving dishes (`service_consumption`), kitchen waste (`loss`), or physical counts (`inventory_adjustment`)—is recorded as an immutable `StockMovement` with `unitCostAtMovement`.
   - When an `OrderItem` transitions to `served`, `unitCostSnapshot` stores the exact recipe cost at that moment so future ingredient purchase price fluctuations never distort past margin reports.

# Demo Data Walkthrough

This package now includes demo records inside `database.sql` so you can understand the full workflow immediately after import.

## Demo Admin Accounts

```text
Super Admin: admin@bmj.com   / admin123
Manager:     manager@bmj.com / manager123
Viewer:      viewer@bmj.com  / viewer123
```

Use these to test permissions:

- **Super Admin** can access everything, including team roles and SEO settings.
- **Manager** can add/edit products and categories, and view orders.
- **Viewer** can only view records.

Delete or change these demo accounts before going live.

## Demo Customer Account

```text
Email:    customer@bmj.com
Password: customer123
```

Use this on the frontend login page to view customer order history and test reorder.

## Demo Categories

Included category structure:

```text
Industrial Tools
├── Bearing
│   ├── Ball Bearing
│   └── Roller Bearing
├── Abrasives
│   ├── Grinding Discs
│   └── Cutting Discs
Hand Tools
Safety Equipment
```

This shows the required 3-level category setup:

```text
Industrial Tools > Bearing > Ball Bearing
```

## Demo Products

12 sample products are included, such as:

- Deep Groove Ball Bearing 6204
- Deep Groove Ball Bearing 6205
- Taper Roller Bearing 30205
- Grinding Disc 4 Inch Metal
- Cutting Disc 14 Inch Steel
- Combination Spanner Set
- Nitrile Coated Safety Gloves

Demo SVG images are included in:

```text
uploads/products/
```

## Demo Orders

Two quote orders are included:

```text
BMJ-DEMO-1001 - Pending
BMJ-DEMO-1002 - Completed
```

Open:

```text
Admin > Orders
```

You can view customer details, items ordered, subtotal, 18% GST and grand total.

## Demo Stock Tracking

The demo orders automatically deduct stock in `database.sql`, so the dashboard and product list show how live stock tracking works.

Example:

- Bearing 6204 starts with stock 50
- Demo pending order uses 4 units
- Imported visible stock becomes 46

If a Super Admin changes an order to **Cancelled**, the system restores stock.

## Suggested Test Flow

1. Login as Super Admin.
2. Open dashboard and check metrics.
3. Go to Products and edit a demo product.
4. Go to Categories and view the 3-level category tree.
5. Go to Orders and open `BMJ-DEMO-1001`.
6. Change order status from Pending to Processing.
7. Login as customer and open My Account.
8. Use Reorder to add previous order items to cart.
9. Submit a new quote checkout from frontend.

## Before Going Live

Remove demo records or replace them with real data:

- Delete demo orders from Admin > Orders if no longer needed.
- Delete demo customer from database/customers table.
- Delete demo admin users or change passwords.
- Replace demo product images with real product images.
- Update all company contact details and SEO settings.

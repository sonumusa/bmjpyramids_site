# BMJ Pyramids LLP - Customization Guide

## Logo

The uploaded BMJ image has been converted and placed here:

```text
public/assets/images/logo.png
```

To replace it, overwrite that file with a transparent PNG logo.

## Colors

Primary colors are defined in:

```text
public/assets/css/style.css
admin/assets/css/admin.css
```

Main variables:

```css
--bmj-blue: #005b9f;
--bmj-cyan: #00a6e8;
--bmj-dark: #0f172a;
```

## Home Page Content

Edit:

```text
public/index.php
```

Sections included:

- Hero introduction
- Trust strip
- Featured categories
- Featured products
- Why choose BMJ Pyramids
- Testimonials placeholder

## About Page

Edit:

```text
public/about.php
```

Add real company history, certifications, registrations, partner brands and factory/client details.

## Contact Details

Best method:

```text
Admin > SEO Tools > Company Settings
```

Fallback constants are in `includes/config.php`.

## Product Images

Upload from:

```text
Admin > Products > Add/Edit Product
```

Images are saved in:

```text
uploads/products/
```

When GD WebP support is enabled, JPG/PNG images are converted to WebP automatically.

## Add 100 Initial Products

Recommended process:

1. Create all 3-level categories first.
2. Add products in batches from Admin.
3. Keep SKU unique and consistent.
4. Add meta title and meta description for important products.
5. Regenerate sitemap after product upload.

For bulk import later, a CSV importer can be added on top of the existing schema.

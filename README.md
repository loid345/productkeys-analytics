# Dart_ProductkeysAnalytics

**Version:** 0.1.0  
**Magento 2.x**  
**Author:** Dart Team  
**Dependency:** requires the `Dart_Productkeys` module

## 📌 Purpose

The `Dart_ProductkeysAnalytics` module extends the Magento 2 admin panel by adding **digital key analytics by SKU**. It works alongside `Dart_Productkeys`, providing statistics on how many keys are uploaded, sold, and still available.

## 🔧 Installation

1. Unpack the archive into:

```
app/code/Dart/ProductkeysAnalytics
```

2. Run the following commands:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## 🗂 Module structure

- `registration.php` — module registration
- `etc/module.xml` — version and dependency on `Dart_Productkeys`
- `composer.json` — autoload information and package identity
- `etc/adminhtml/`
  - `menu.xml` — adds the "Analytics" menu item
  - `routes.xml` — admin routes
  - `acl.xml` — access control
- `Controller/Adminhtml/Analytics/Index.php` — UI grid controller
- `view/adminhtml/ui_component/dart_productkeysanalytics_listing.xml` — grid UI component
- `Ui/DataProvider/AnalyticsDataProvider.php` — data provider and filtering
- `view/adminhtml/layout/productkeysanalytics_analytics_index.xml` — page layout binding

## 📊 How it works

**Data source:** `dart_productkeys` table created by `Dart_Productkeys`.

**Admin menu:**

`Menu → Dart → Product Keys → Analytics`

**Grid columns:**

- SKU
- Product Name
- Total Keys (total records per SKU)
- Sold (keys with `order_id`)
- Free (keys without `order_id`)

**Filtering:**

- By SKU or product name
- By order date (based on linked orders)

**Totals row:**

- Sums all visible rows for Total / Sold / Free

**Export:**

- CSV
- Excel XML

## 📥 Example usage

Upload 100 keys for SKU `GAME-001`. 45 are used in orders → Sold = 45, Free = 55.

The grid will show:

```
SKU       | Product Name  | Total Keys | Sold | Free
GAME-001  | Game License  | 100        | 45   | 55
```

## ✅ Requirements

- Magento 2.4.x
- PHP >= 7.3
- Installed `Dart_Productkeys` module

## 🛠 Access permissions

ACL resource added:

`Dart_ProductkeysAnalytics::analytics`

Ensure the admin role includes this permission.

## 📎 Export details

- Supports CSV and Excel XML
- Respects applied filters
- Last row contains totals for visible rows

## 🔐 Security and performance

- Order date filtering optimized for indexed tables
- Outputs only SKUs with keys

## 🧩 Compatibility

- Magento Open Source 2.4.x
- Tested on PHP 7.4 and 8.1

## 📧 Support

Questions or suggestions: open a GitHub issue or create a pull request.

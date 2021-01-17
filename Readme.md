# Sledgehammer Wordpress

## Adds Sledgehammer goodies

- DebugR intergration (includes the wpdb query log).
- Statusbar
- PDO database access.
- ORM access to wordpress models

### Devutils

- Export Post - An post_id independend export of an wp_posts record.
- Diff options - Compare diffences in the contents of the wp_options table.

## Installation

Install with composer, in the root of the wordpress project run:

```
composer require sledgehammer/wordpress
```

Edit `wp-config.php` and add at the **bottom** (after the require `wp-settings.php`):

```php
require_once(ABSPATH.'vendor/autoload.php');
Sledgehammer\Wordpress\Bridge::initialize();
require_once(ABSPATH.'vendor/sledgehammer/core/src/render_public_folders.php');

```

Disable web access to the vendor folder.  
(For Apache httpd: Add a `.httaccess` file into the vendor folder with `Deny from all`)

## Configuration

The bridge looks at constants defined in the wp-config to auto-configure sledgehammer.

```
WP_DEBUG: Enable the errorhandler & statusbar
SAVEQUERIES: Enables the sql query log
DB_*: Connect to the database, if needed
WP_HOME: Detect public folder
```

To measure initialization add the following line line at the **beginning** of the `wp-config.php`:

```php
define('Sledgehammer\STARTED', microtime(true));
```

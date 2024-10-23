# Version

<a href="https://packagist.org/packages/tuijncode/version"><img src="https://poser.pugx.org/tuijncode/version/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tuijncode/version"><img src="https://poser.pugx.org/tuijncode/version/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/tuijncode/version"><img src="https://poser.pugx.org/tuijncode/version/license.svg" alt="License"></a>

![Html](https://cdn.tuijncode.com/github/version.png)

With Version, you can retrieve various versions within your project, which is particularly useful if you have multiple projects and need to identify which ones require updates.

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/davidvandertuijn)

## Releases

| Release | PHP    |
|---------|--------|
| 1.0     | 7.2.0  |
| 2.0     | 8.1.0  |
=======

## Install

Install the package via Composer:

```sh
composer require tuijncode/version
```

Add your token to the .env file:

```
TUIJNCODE_VERSION_TOKEN="your-token"
```

Additionally, add the following components to the .env file if you want to check the database:

```
TUIJNCODE_VERSION_PDO_DSN="your-dsn"
TUIJNCODE_VERSION_PDO_USERNAME="your-username"
TUIJNCODE_VERSION_PDO_PASSWORD="your-password"
```

Use one of the following options for DSN:

| Database Type     | DSN Format                             |
|-------------------|----------------------------------------|
| MySQL/MariaDB     | `mysql:host=localhost;dbname=your_database` |
| PostgreSQL        | `pgsql:host=localhost;dbname=your_database` |
| SQLite            | `sqlite:/path/to/database.db`        |
| SQL Server        | `sqlsrv:Server=localhost;Database=your_database` |

Add the PHP file **version.php**:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Tuijncode\Version\RequestHandler;

$requestHandler = new RequestHandler();
$response = $requestHandler->handleRequest();
$response->send();
?>
```

## Usage

https://example.com/version?token=your-token

## Response (JSON)

```yaml
{
    "status": "OK",
    "versions":
        {
            "webserver":
                {
                    "name": "Apache",
                    "version": "Apache\/2.4.58 (Unix) mod_wsgi\/4.9.4 Python\/3.11 mod_fastcgi\/mod_fastcgi-SNAP-0910052141 OpenSSL\/1.1.1u"
                },
            "database":
                {
                    "name": "mysql",
                    "version": "8.0.35"
                },
            "php":
                {
                  "name": "cgi-fcgi",
                  "version": "8.2.20"
                }
        }
}
```

## Dashboard

Would you like to view all your projects in an online dashboard? We have successfully developed this solution and also offer installation support.
https://davidvandertuijn.nl/oplossingen/versies

![Html](http://cdn.davidvandertuijn.nl/solutions/versions/thumbnails/versions.800x600.png)

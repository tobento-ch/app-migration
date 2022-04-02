# App Migration

App migration support.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Migration Boot](#migration-boot)
        - [Install and Uninstall Migration](#install-and-uninstall-migration)
        - [Create Migration](#create-migration)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app migration project running this command.

```
composer require tobento/app-migration
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [App Skeleton](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [App](https://github.com/tobento-ch/app) to learn more about the app in general.

## Migration Boot

The migration boot does the following:

* definition of \Tobento\Service\Migration\MigratorInterface::class
* definition of \Tobento\Service\Migration\MigrationResultsInterface::class
* installs and loads migration config file
* adds install and uninstall app macros

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Migration\Boot\Migration::class);

// Run the app
$app->run();
```

### Install and Uninstall Migration

Once the [Migration Boot](#migration-boot) has been booted you may install migrations by the following ways:

```php
use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;

class AnyServiceBoot extends Boot
{
    public const BOOT = [
        // you may ensure the migration boot.
        Migration::class,
    ];
    
    public function boot(Migration $migration)
    {
        // Install migrations
        $migration->install(AnyMigration::class);
        
        // Uninstall migrations
        $migration->uninstall(AnyMigration::class);
        
        // Install migrations with app macro
        $this->app->install(AnyMigration::class);
        
        // Uninstall migrations with app macro
        $this->app->uninstall(AnyMigration::class);        
    }
}
```

### Create Migration

Check out the [Migration Service](https://github.com/tobento-ch/service-migration) to learn more about creating migration classes.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
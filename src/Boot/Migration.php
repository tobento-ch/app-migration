<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Migration\Boot;

use Tobento\App\Boot;
use Tobento\Service\Migration\Migrator;
use Tobento\Service\Migration\MigratorInterface;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\AutowiringMigrationFactory;
use Tobento\Service\Migration\MigrationJsonFileRepository;
use Tobento\Service\Migration\MigrationResults;
use Tobento\Service\Migration\MigrationResultsInterface;
use Tobento\Service\Migration\MigrationInstallException;
use Tobento\Service\Migration\MigrationUninstallException;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Config\PhpLoader;
use Tobento\Service\Config\ConfigLoadException;
use Tobento\Service\Responser\ResponserInterface;
use Psr\Container\ContainerInterface;

/**
 * Migration boot.
 */
class Migration extends Boot
{
    public const INFO = [
        'boot' => [
            'definition of '.MigratorInterface::class,
            'definition of '.MigrationResultsInterface::class,
            'installs and loads migration config file',
            'adds install and uninstall app macros',
        ],
    ];
    
    public const BOOT = [
        \Tobento\App\Boot\Config::class,
    ];
    
    /**
     * @var bool If migration is enabled.
     */
    protected bool $enabled = true;
    
    /**
     * @var bool If to show migration messages.
     */
    protected bool $showMigrationMessages = false;
    
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Migrator implementation.
        $this->app->set(MigratorInterface::class, function(ContainerInterface $container) {
            return new Migrator(
                new AutowiringMigrationFactory($container),
                new MigrationJsonFileRepository($this->app->dir('app').'migrations/'),
            );
        });
        
        // MigrationResults implementation.
        $this->app->set(MigrationResultsInterface::class, MigrationResults::class);
        
        // Load the migration configuration.
        $config = $this->app->get(ConfigInterface::class);
        
        try {
            $config->load('migration.php', 'migration');
        } catch (ConfigLoadException $e) {
            // install if config does not exist yet.
            $this->install(\Tobento\App\Migration\Migration\Migration::class);
            
            $config->load('migration.php', 'migration');
        }
        
        $this->enabled = $config->get('migration.enabled', false);
        $this->showMigrationMessages = $config->get('migration.show_messages', false);
        
        // Add macros.
        $this->app->addMacro('install', [$this, 'install']);
        $this->app->addMacro('uninstall', [$this, 'uninstall']);
        
        // If show messages is enabled, we add messages to the responser to display later on.
        if ($this->showMigrationMessages) {

            $this->app->on(ResponserInterface::class, function(ResponserInterface $responser) {

                foreach($this->app->get(MigrationResultsInterface::class)->all() as $result)
                {
                    $responser->messages()->add(
                        level: 'success',
                        message: 'Successfully installed: '.$result->migration()->description(),
                    );
                }                 
            });
        }        
    }
    
    /**
     * Installs the given migration.
     *
     * @param string|MigrationInterface $migration The migration class name or object.
     * @return void
     * @throws MigrationInstallException
     */
    public function install(string|MigrationInterface $migration): void
    {
        if ($this->enabled === false) {
            return;
        }
        
        $migrator = $this->app->get(MigratorInterface::class);
        
        $migrationClass = is_string($migration) ? $migration : $migration::class;
        
        if ($migrator->isInstalled($migrationClass)) {
            return;
        }
        
        // Lets just call install here and let
        // the app ThrowableHandler handle any migration exceptions if needed.
        $result = $migrator->install($migration);
        
        $this->app->get(MigrationResultsInterface::class)->add($result);
    }
    
    /**
     * Uninstalls the given migration.
     *
     * @param string|MigrationInterface $migration The migration class name or object.
     * @return void
     * @throws MigrationUninstallException
     */    
    public function uninstall(string|MigrationInterface $migration): void
    {
        if ($this->enabled === false) {
            return;
        }
        
        $migrator = $this->app->get(MigratorInterface::class);
        
        $migrationClass = is_string($migration) ? $migration : $migration::class;
        
        if (! $migrator->isInstalled($migrationClass)) {
            return;
        }
        
        // Lets just call uninstall here and let
        // the app ThrowableHandler handle any migration exceptions if needed.
        $result = $migrator->uninstall($migration);
        
        $this->app->get(MigrationResultsInterface::class)->add($result);
    }
}
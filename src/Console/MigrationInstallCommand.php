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

namespace Tobento\App\Migration\Console;

use Tobento\Service\Migration\MigratorInterface;
use Tobento\Service\Migration\MigrationFactoryInterface;
use Tobento\Service\Migration\MigrationInstallException;
use Tobento\Service\Migration\InvalidMigrationException;
use Tobento\Service\Migration\ActionFailedException;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class MigrationInstallCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        migration:install | Install migration(s) or actions(s)
        {--name= : Installs a migration by class name}
        {--all : Installs all migrations with its actions}
        {--id= : Install specific migration or action by ids}
        {--type= : Install specific migration actions by type}
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @param MigrationFactoryInterface $migrationFactory
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     */
    public function handle(
        InteractorInterface $io,
        MigratorInterface $migrator,
        MigrationFactoryInterface $migrationFactory,
    ): int {
        // php ap migration:install --name=Namespace\Migration
        if ($name = $io->option(name: 'name')) {
            return $this->handleName($name, $io, $migrator);
        }
        
        // php ap migration:install --all
        if ($io->option(name: 'all')) {
            return $this->handleAll($io, $migrator, $migrationFactory);
        }
        
        // php ap migration:install --id=12|23
        if ($id = $io->option(name: 'id')) {
            $ids = explode('|', $id);
            return $this->handleIds($ids, $io, $migrator, $migrationFactory);
        }
        
        // php ap migration:install --type=database|config
        if ($type = $io->option(name: 'type')) {
            $types = explode('|', $type);
            return $this->handleTypes($types, $io, $migrator, $migrationFactory);
        }
        
        return 0;
    }
    
    /**
     * Handle name.
     *
     * @param string $name
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @return int The exit status code
     */
    protected function handleName(
        string $name,
        InteractorInterface $io,
        MigratorInterface $migrator,
    ): int {
        try {
            $result = $migrator->install($name);
        } catch (MigrationInstallException|InvalidMigrationException $e) {
            $io->error($name.': '.$e->getMessage());
            return 1;
        }
        
        $migration = $result->migration();
        
        foreach($result->actions()->all() as $action) {
            $io->success($migration::class.': '.$action::class.': '.$action->description());

            if ($io->isVerbose('v')) {
                foreach($action->processedDataInfo() as $name => $value) {
                    $io->write($name.': '.$value);
                    $io->newLine();
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Handle all migrations.
     *
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @param MigrationFactoryInterface $migrationFactory
     * @return int The exit status code
     */
    protected function handleAll(
        InteractorInterface $io,
        MigratorInterface $migrator,
        MigrationFactoryInterface $migrationFactory,
    ): int {
        foreach($migrator->getInstalled() as $migration) {
            $migration = $migrationFactory->createMigration($migration);
            
            foreach($migration->install()->all() as $action) {
                try {
                    $action->process();
                    $io->success($migration::class.': '.$action::class.': '.$action->description());

                    if ($io->isVerbose('v')) {
                        foreach($action->processedDataInfo() as $name => $value) {
                            $io->write($name.': '.$value);
                            $io->newLine();
                        }
                    }
                } catch (ActionFailedException $e) {
                    $io->error($migration::class.': '.$action::class.': '.$e->getMessage());
                }
            }
        }
        
        return 0;
    }

    /**
     * Handle ids.
     *
     * @param array $ids
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @param MigrationFactoryInterface $migrationFactory
     * @return int The exit status code
     */
    protected function handleIds(
        array $ids,
        InteractorInterface $io,
        MigratorInterface $migrator,
        MigrationFactoryInterface $migrationFactory,
    ): int {
        $id = 0;

        foreach($migrator->getInstalled() as $migration) {
            $id++;
            $allActions = false;
            $migration = $migrationFactory->createMigration($migration);
            
            if (in_array($id, $ids)) {
                $allActions = true;
            }
            
            foreach($migration->install()->all() as $action) {
                $id++;
                if ($allActions || in_array($id, $ids)) {
                    try {
                        $action->process();
                        $io->success($migration::class.': '.$action::class.': '.$action->description());
                        
                        if ($io->isVerbose('v')) {
                            foreach($action->processedDataInfo() as $name => $value) {
                                $io->write($name.': '.$value);
                                $io->newLine();
                            }
                        }
                    } catch (ActionFailedException $e) {
                        $io->error($migration::class.': '.$action::class.': '.$e->getMessage());
                    }
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Handle action types.
     *
     * @param array $types
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @param MigrationFactoryInterface $migrationFactory
     * @return int The exit status code
     */
    protected function handleTypes(
        array $types,
        InteractorInterface $io,
        MigratorInterface $migrator,
        MigrationFactoryInterface $migrationFactory,
    ): int {
        foreach($migrator->getInstalled() as $migration) {
            $migration = $migrationFactory->createMigration($migration);
            
            foreach($migration->install()->all() as $action) {
                if (in_array($action->type(), $types)) {
                    try {
                        $action->process();
                        $io->success($migration::class.': '.$action::class.': '.$action->description());
                        
                        if ($io->isVerbose('v')) {
                            foreach($action->processedDataInfo() as $name => $value) {
                                $io->write($name.': '.$value);
                                $io->newLine();
                            }
                        }
                    } catch (ActionFailedException $e) {
                        $io->error($migration::class.': '.$action::class.': '.$e->getMessage());
                    }
                }
            }
        }
        
        return 0;
    }
}
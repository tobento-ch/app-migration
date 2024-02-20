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
use Tobento\Service\Migration\MigrationUninstallException;
use Tobento\Service\Migration\InvalidMigrationException;
use Tobento\Service\Migration\ActionFailedException;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class MigrationUninstallCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        migration:uninstall | Uninstall migration(s) or actions(s)
        {--name= : Uninstalls a migration by class name}
        {--all : Uninstalls all migrations with its actions}
        {--id= : Uninstall specific migration(s) by ids}
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
        // php ap migration:uninstall --name=Namespace\Migration
        if ($name = $io->option(name: 'name')) {
            return $this->handleName($name, $io, $migrator);
        }
        
        // php ap migration:uninstall --all
        if ($io->option(name: 'all')) {
            return $this->handleAll($io, $migrator, $migrationFactory);
        }
        
        // php ap migration:uninstall --id=12|23
        if ($id = $io->option(name: 'id')) {
            $ids = explode('|', $id);
            return $this->handleIds($ids, $io, $migrator, $migrationFactory);
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
        return $this->uninstall($name, $io, $migrator) === true ? 0 : 1;
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
        $failed = false;
        
        foreach($migrator->getInstalled() as $migration) {
            if (! $this->uninstall($migration, $io, $migrator)) {
                $failed = true;
            }
        }
        
        return $failed ? 1 : 0;
    }

    /**
     * Handle ids.
     *
     * @param array $ids
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @param MigrationFactoryInterface $migrationFactory
     * @return int The exit status code
     * @psalm-suppress UnusedVariable
     */
    protected function handleIds(
        array $ids,
        InteractorInterface $io,
        MigratorInterface $migrator,
        MigrationFactoryInterface $migrationFactory,
    ): int {
        // collect migrations by its id:
        $migrations = [];
        $id = 0;
        
        foreach($migrator->getInstalled() as $migration) {
            $id++;
            $migration = $migrationFactory->createMigration($migration);
            
            if (in_array($id, $ids)) {
                $migrations[] = $migration::class;
            }
            
            foreach($migration->install()->all() as $action) {
                $id++;
            }
        }
        
        // uninstall:
        $failed = false;
        
        foreach($migrations as $migration) {
            if (! $this->uninstall($migration, $io, $migrator)) {
                $failed = true;
            }
        }
        
        return $failed ? 1 : 0;
    }

    /**
     * Uninstall a migration.
     *
     * @param string $name
     * @param InteractorInterface $io
     * @param MigratorInterface $migrator
     * @return bool True on success, otherwise false.
     */
    protected function uninstall(
        string $name,
        InteractorInterface $io,
        MigratorInterface $migrator,
    ): bool {
        try {
            $result = $migrator->uninstall($name);
        } catch (MigrationUninstallException|InvalidMigrationException $e) {
            $io->error($name.': '.$e->getMessage());
            return false;
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
        
        return true;
    }
}
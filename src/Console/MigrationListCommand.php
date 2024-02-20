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
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class MigrationListCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        migration:list | List all installed migrations
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
        // php ap migration:list
        $rows = [];
        $id = 0;

        foreach($migrator->getInstalled() as $migration) {
            $id++;
            $migration = $migrationFactory->createMigration($migration);
            $rows[] = [$id, $migration::class, '', $migration->description()];

            foreach($migration->install()->all() as $action) {
                $id++;
                $rows[] = [$id, '- '.$action::class, $action->type(), $action->description()];
            }
        }
        
        $io->table(
            headers: ['ID', 'Migration / - Action', 'Type', 'Description'],
            rows: $rows,
        );
        
        return 0;
    }
}
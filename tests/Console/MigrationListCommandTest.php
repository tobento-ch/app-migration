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

namespace Tobento\App\Migration\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\App\Migration\Console\MigrationListCommand;
use Tobento\App\Migration\Test\Mock;
use Tobento\Service\Container\Container;
use Tobento\Service\Migration\MigratorInterface;
use Tobento\Service\Migration\Migrator;
use Tobento\Service\Migration\MigrationFactoryInterface;
use Tobento\Service\Migration\AutowiringMigrationFactory;
use Tobento\Service\Migration\MigrationJsonFileRepository;
use Tobento\Service\Filesystem\Dir;

class MigrationListCommandTest extends TestCase
{
    protected function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/');
    }
    
    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/../tmp/');
    }
    
    protected function getContainer(): Container
    {
        $container = new Container();
        $container->set(MigrationFactoryInterface::class, AutowiringMigrationFactory::class);
        $container->set(MigratorInterface::class, function(MigrationFactoryInterface $migrationFactory) {
            return new Migrator(
                $migrationFactory,
                new MigrationJsonFileRepository(__DIR__.'/../tmp/migrations/'),
            );
        });
        
        return $container;
    }
    
    public function testCommand()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrationFactory = $container->get(MigrationFactoryInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
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
        
        (new TestCommand(
            command: MigrationListCommand::class,
        ))
        ->expectsTable(
            headers: ['ID', 'Migration / - Action', 'Type', 'Description'],
            rows: $rows,
        )
        ->expectsExitCode(0)
        ->execute($container);
    }
}
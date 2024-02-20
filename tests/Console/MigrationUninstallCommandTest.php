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
use Tobento\App\Migration\Console\MigrationUninstallCommand;
use Tobento\App\Migration\Test\Mock;
use Tobento\Service\Container\Container;
use Tobento\Service\Migration\MigratorInterface;
use Tobento\Service\Migration\Migrator;
use Tobento\Service\Migration\MigrationFactoryInterface;
use Tobento\Service\Migration\AutowiringMigrationFactory;
use Tobento\Service\Migration\MigrationJsonFileRepository;
use Tobento\Service\Filesystem\Dir;

class MigrationUninstallCommandTest extends TestCase
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
    
    public function testWithNameOption()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        $this->assertSame(1, count($migrator->getInstalled()));
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--name' => 'Tobento\App\Migration\Test\Mock\FooMigration'],
        );
        
        $migration = new Mock\FooMigration();
        
        foreach($migration->uninstall() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
        
        $this->assertSame(0, count($migrator->getInstalled()));
    }
    
    public function testWithNameOptionIfNotInstalled()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        $this->assertSame(1, count($migrator->getInstalled()));
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--name' => 'Tobento\App\Migration\Test\Mock\BarMigration'],
        );
        
        $testCommand->expectsExitCode(0)->execute($container);
        
        $this->assertSame(1, count($migrator->getInstalled()));
    }
    
    public function testWithNameOptionWithInvalidMigration()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--name' => 'Tobento\App\Migration\Test\Mock\BazMigration'],
        );
        
        $testCommand->expectsExitCode(1)->execute($container);
    }
    
    public function testWithAllOption()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        $migrator->install(Mock\BarMigration::class);
        $this->assertSame(2, count($migrator->getInstalled()));
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--all' => null],
        );
        
        $migration = new Mock\FooMigration();
        foreach($migration->uninstall() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $migration = new Mock\BarMigration();
        foreach($migration->uninstall() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
        $this->assertSame(0, count($migrator->getInstalled()));
    }
    
    public function testWithIdOption()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        $migrator->install(Mock\BarMigration::class);
        $this->assertSame(2, count($migrator->getInstalled()));
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--id' => '1'],
        );
        
        $migration = new Mock\FooMigration();

        foreach($migration->uninstall() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
        $this->assertSame(1, count($migrator->getInstalled()));
    }
    
    public function testWithIdOptionMultiple()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        $migrator->install(Mock\BarMigration::class);
        $this->assertSame(2, count($migrator->getInstalled()));
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--id' => '1|4'],
        );
        
        $migration = new Mock\FooMigration();

        foreach($migration->uninstall() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
        $this->assertSame(0, count($migrator->getInstalled()));
    }
    
    public function testWithIdOptionMultipleWrongIdIsIgnored()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        $migrator->install(Mock\BarMigration::class);
        $this->assertSame(2, count($migrator->getInstalled()));
        
        $testCommand = new TestCommand(
            command: MigrationUninstallCommand::class,
            input: ['--id' => '1|2|45'],
        );
        
        $migration = new Mock\FooMigration();

        foreach($migration->uninstall() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
        $this->assertSame(1, count($migrator->getInstalled()));
    }
}
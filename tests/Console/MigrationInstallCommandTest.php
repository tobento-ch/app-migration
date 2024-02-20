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
use Tobento\App\Migration\Console\MigrationInstallCommand;
use Tobento\App\Migration\Test\Mock;
use Tobento\Service\Container\Container;
use Tobento\Service\Migration\MigratorInterface;
use Tobento\Service\Migration\Migrator;
use Tobento\Service\Migration\MigrationFactoryInterface;
use Tobento\Service\Migration\AutowiringMigrationFactory;
use Tobento\Service\Migration\MigrationJsonFileRepository;
use Tobento\Service\Filesystem\Dir;

class MigrationInstallCommandTest extends TestCase
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
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--name' => 'Tobento\App\Migration\Test\Mock\FooMigration'],
        );
        
        $migration = new Mock\FooMigration();
        
        foreach($migration->install() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
    }
    
    public function testWithNameOptionWithInvalidMigration()
    {
        $container = $this->getContainer();
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--name' => 'Tobento\App\Migration\Test\Mock\BazMigration'],
        );
        
        $testCommand->expectsExitCode(1)->execute($container);
    }
    
    public function testWithAllOption()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--all' => null],
        );
        
        $migration = new Mock\FooMigration();

        foreach($migration->install() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
    }
    
    public function testWithIdOption()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--id' => '1'],
        );
        
        $migration = new Mock\FooMigration();

        foreach($migration->install() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
    }
    
    public function testWithIdOptionOnlyAction()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--id' => '3'],
        );
        
        $migration = new Mock\FooMigration();
        $action = $migration->install()->all()[1];
        $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        $testCommand->expectsExitCode(0)->execute($container);
    }
    
    public function testWithIdOptionMultiple()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--id' => '2|3'],
        );
        
        $migration = new Mock\FooMigration();

        foreach($migration->install() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
    }
    
    public function testWithTypeOption()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--type' => 'config'],
        );
        
        $migration = new Mock\FooMigration();
        $action = $migration->install()->all()[0];
        $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        $testCommand->expectsExitCode(0)->execute($container);
    }
    
    public function testWithTypeOptionMultiple()
    {
        $container = $this->getContainer();
        $migrator = $container->get(MigratorInterface::class);
        $migrator->install(Mock\FooMigration::class);
        
        $testCommand = new TestCommand(
            command: MigrationInstallCommand::class,
            input: ['--type' => 'config|views'],
        );
        
        $migration = new Mock\FooMigration();
        
        foreach($migration->install() as $action) {
            $testCommand->expectsOutput($migration::class.': '.$action::class.': '.$action->description());
        }
        
        $testCommand->expectsExitCode(0)->execute($container);
    }
}
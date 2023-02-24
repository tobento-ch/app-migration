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

namespace Tobento\App\Migration\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppFactory;
use Tobento\Service\Migration\MigrationResultsInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\App\Migration\Test\Mock\FooMigration;
use Tobento\Service\Responser\Responser;
use Tobento\Service\Responser\ResponserInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
    
/**
 * MigrationTest
 */
class MigrationTest extends TestCase
{
    public function testInstallMigrationWithMacro()
    {
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/config/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app').'/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10);
    
        $app->boot(\Tobento\App\Migration\Boot\Migration::class);
        
        $app->booting();
        
        $app->install(FooMigration::class);
        
        $result = $app->get(MigrationResultsInterface::class)->all()[1];
        
        $action = $result->actions()->all()[0];
        
        $this->assertSame(
            'config-install',
            $action->description()
        );
        
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
    }
    
    public function testUninstallMigrationWithMacro()
    {
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/config/');        
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app').'/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10);
    
        $app->boot(\Tobento\App\Migration\Boot\Migration::class);
        
        $app->booting();
        
        $app->install(FooMigration::class);
        $app->uninstall(FooMigration::class);
        
        $result = $app->get(MigrationResultsInterface::class)->all()[2];
        
        $action = $result->actions()->all()[0];
        
        $this->assertSame(
            'config-uninstall',
            $action->description()
        );
        
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
    }
    
    public function testInstallMigrationWithBoot()
    {
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/config/');        
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app').'/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10);
    
        $app->boot(\Tobento\App\Migration\Test\Mock\InstallService::class);
        
        $app->booting();
        
        $result = $app->get(MigrationResultsInterface::class)->all()[1];
        
        $action = $result->actions()->all()[0];
        
        $this->assertSame(
            'config-install',
            $action->description()
        );
        
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
    }
    
    public function testUninstallMigrationWithBoot()
    {
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/config/');        
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app').'/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10);
        
        $app->boot(\Tobento\App\Migration\Test\Mock\InstallService::class);
        $app->boot(\Tobento\App\Migration\Test\Mock\UninstallService::class);
        
        $app->booting();
        
        $result = $app->get(MigrationResultsInterface::class)->all()[2];
        
        $action = $result->actions()->all()[0];
        
        $this->assertSame(
            'config-uninstall',
            $action->description()
        );
        
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
    }
    
    public function testInstallMigrationMessages()
    {
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/');
        $dir->create(__DIR__.'/../app/config/');        
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app').'/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10);

        $app->set(ResponserInterface::class, function() {
            
            $psr17Factory = new Psr17Factory();
            
            return new Responser(
                responseFactory: $psr17Factory,
                streamFactory: $psr17Factory,
            );
        });        
        
        $app->boot(\Tobento\App\Migration\Test\Mock\InstallService::class);
        
        $app->booting();
        
        $this->assertSame(
            'Successfully installed: Config file migration.',
            $app->get(ResponserInterface::class)->messages()->all()[0]->message()
        );
        
        $this->assertSame(
            'Successfully installed: Foo migration.',
            $app->get(ResponserInterface::class)->messages()->all()[1]->message()
        );        
        
        $dir = new Dir();
        $dir->delete(__DIR__.'/../app/');
    }    
}
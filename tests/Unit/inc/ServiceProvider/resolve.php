<?php

namespace RocketLauncherAutoresolver\Tests\Unit\inc\ServiceProvider;

use Mockery;
use Psr\Container\ContainerInterface;
use RocketLauncherAutoresolver\ServiceProvider;
use RocketLauncherAutoresolver\Tests\Unit\TestCase;

class Test_Resolve extends TestCase
{
    protected $provider;
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ServiceProvider();
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->provider->setContainer($this->container);
    }

    /**
     * @dataProvider configTestData
     */
    public function testShouldDoAsExpected($config, $expected) {
        foreach ($config['load'] as $load) {
            require_once $load;
        }
        foreach ($config['bindings'] as $binding) {
            $this->provider->bind($binding['id'], $binding['concrete']);
        }
        foreach ($expected['classes'] as $class) {
            $this->container->expects()->has($class)->andReturn(false);
        }
        foreach ($expected['parameters'] as $parameter => $value) {
            $this->container->expects()->has($parameter)->andReturn(true);
        }
        $this->provider->resolve($config['classes']);

        $this->assertSame($expected['classes'], $this->provider->declares());
    }
}
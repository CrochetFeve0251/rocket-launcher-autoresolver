<?php

namespace LaunchpadAutoresolver\Tests\Integration\inc\ServiceProvider;

use League\Container\Container;
use Mockery;
use Psr\Container\ContainerInterface;
use LaunchpadAutoresolver\ServiceProvider;
use LaunchpadAutoresolver\Tests\Unit\TestCase;

class Test_Resolve extends TestCase
{
    protected $provider;
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ServiceProvider();
        $this->container = new Container();
        $this->container->addServiceProvider($this->provider);
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

        foreach ($config['parameters'] as $parameter => $value) {
            $this->container->add($parameter, $value);
        }

        $this->provider->resolve($config['classes']);

        $this->assertSame($expected['classes'], $this->provider->declares());

        foreach ($config['classes'] as $class) {
            $this->container->get($class);
        }
    }
}
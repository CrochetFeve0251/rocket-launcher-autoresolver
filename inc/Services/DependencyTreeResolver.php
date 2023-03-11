<?php

namespace RocketLauncherAutoresolver\Services;

use League\Container\Definition\DefinitionInterface;
use ReflectionClass;
use ReflectionException;
use RocketLauncherAutoresolver\ServiceProvider;

class DependencyTreeResolver
{
    /**
     * @var ServiceProvider
     */
    protected $provider;

    /**
     * @param ServiceProvider $provider
     */
    public function __construct(ServiceProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param string[] $classes
     * @throws ReflectionException
     */
    public function resolve(array $classes) {
        foreach ($classes as $class) {
            $this->resolve_class($class);
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function resolve_class(string $class) {

        if($this->provider->getContainer()->has($class)) {
            return;
        }

        $reflector = new ReflectionClass($class);

        if( ! $reflector->isInstantiable())
        {
            throw new \Exception("[$class] is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if(is_null($constructor))
        {
            $this->provider->register_service($class);
        }

        $parameters = $constructor->getParameters();
        $this->register_dependencies($parameters);

        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if(is_null($dependency))
            {
                $name = $parameter->getName();
                if( $this->provider->getContainer()->has($name) ) {
                    $dependencies[] = [
                        'key' => $name
                    ];
                    continue;
                }

                $dependencies[] = [
                    'value' => $parameter->getDefaultValue(),
                ];
            }

            $dependencies[] = [
                'key' => $dependency->getName()
            ];
        }

        $this->provider->register_service($class, function (DefinitionInterface $definition) use ($dependencies) {

            $arguments = array_map(function ($dependency) {
                if(key_exists('value', $dependency)) {
                    return $dependency['value'];
                }
                return $this->provider->getContainer()->get($dependency['key']);
            }, $dependencies);

            $definition->addArguments($arguments);
        });
    }

    protected function register_dependencies(array $parameters) {
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if(is_null($dependency))
            {
                continue;
            }
            $this->resolve_class($dependency->name);
        }
    }
}

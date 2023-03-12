<?php

namespace RocketLauncherAutoresolver\Services;

use League\Container\Definition\DefinitionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use RocketLauncherAutoresolver\ServiceProvider;

class DependencyTreeResolver
{
    /**
     * Service provider to resolve.
     *
     * @var ServiceProvider
     */
    protected $provider;

    /**
     * Instantiate the class.
     *
     * @param ServiceProvider $provider Service provider to resolve.
     */
    public function __construct(ServiceProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Resolve classes.
     *
     * @param string[] $classes root class to resolve.
     *
     * @throws ReflectionException
     */
    public function resolve(array $classes) {
        foreach ($classes as $class) {
            $this->resolve_class($class);
        }
    }

    /**
     * Resolve a class.
     *
     * @param string $class class to resolve.
     *
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
            return;
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
                continue;
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

    /**
     * Register dependencies from a class.
     * @param ReflectionParameter[] $parameters parameters from the class.
     *
     * @return void
     * @throws ReflectionException
     */
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

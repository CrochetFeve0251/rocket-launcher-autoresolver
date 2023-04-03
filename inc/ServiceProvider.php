<?php
namespace RocketLauncherAutoresolver;

use League\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use RocketLauncherCore\Activation\HasActivatorServiceProviderInterface;
use RocketLauncherCore\Container\AbstractServiceProvider;
use RocketLauncherCore\Deactivation\HasDeactivatorServiceProviderInterface;
use ReflectionClass;
use ReflectionParameter;

class ServiceProvider extends AbstractServiceProvider
{
    /**
     * Interface mapping.
     *
     * @var array
     */
    protected $interface_mapping = [];

    /**
     * Define classes.
     *
     * @return void
     * @throws ReflectionException
     */
    protected function define()
    {
        $this->resolve($this->get_root_classes());
    }

    /**
     * Get root classes that needs to be loaded.
     *
     * @return string[]
     */
    protected function get_root_classes(): array {

        $roots = array_merge($this->get_init_subscribers(), $this->get_admin_subscribers(), $this->get_common_subscribers(), $this->get_front_subscribers(), $this->get_class_to_instantiate(), $this->get_class_to_expose());

        if($this instanceof HasActivatorServiceProviderInterface) {
            $roots = array_merge($roots, $this->get_activators());
        }

        if($this instanceof HasDeactivatorServiceProviderInterface) {
            $roots = array_merge($roots, $this->get_deactivators());
        }

        return $roots;
    }

    /**
     * Get class that needs to be exposed.
     *
     * @return string[]
     */
    public function get_class_to_expose(): array {
        return [];
    }

    /**
     * Get class that needs to be instantiated.
     *
     * @return string[]
     */
    public function get_class_to_instantiate(): array {
        return [];
    }

    /**
     * Register classes.
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register()
    {
        parent::register();

        foreach ($this->get_class_to_instantiate() as $class) {
            $this->getContainer()->get($class);
        }
    }

    /**
     * Bind a class to a concrete one.
     *
     * @param string $id class to bind.
     * @param string $class concrete class.
     * @param callable(DefinitionInterface $definition): void |null $initialize logic to initialize.
     * @param array $when Effective only on certain parent classes.
     * @return void
     */
    public function bind(string $id, string $class, callable $initialize = null, array $when = []) {
        $this->interface_mapping[$id] = [
            'class' => $class,
            'method' => $initialize,
            'when' => $when,
        ];
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

        if($this->getContainer()->has($class)) {
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
            $this->register_service($class);
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
                if( $this->getContainer()->has($name) ) {
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

        $this->register_service($class, function (DefinitionInterface $definition) use ($dependencies) {

            $arguments = array_map(function ($dependency) {
                if(key_exists('value', $dependency)) {
                    return $dependency['value'];
                }
                return $this->getContainer()->get($dependency['key']);
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

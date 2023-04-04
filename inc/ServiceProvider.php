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
     * @param array $when Effective only on certain parent classes.
     * @return void
     */
    public function bind(string $id, string $class, array $when = []) {
        $this->interface_mapping[] = [
            'id'   =>  $id,
            'class' => $class,
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
     * @param string $concrete concrete class.
     *
     * @throws ReflectionException
     */
    protected function resolve_class(string $class, string $concrete = '') {
        if($this->getContainer()->has($class)) {
            return;
        }

        $instantiate_class = '' === $concrete ? $class : $concrete;

        $reflector = new ReflectionClass($instantiate_class);

        if( ! $reflector->isInstantiable())
        {
            $maps = array_filter($this->interface_mapping, function ($map) use ($class) {
                if(count($map['when']) > 0) {
                    return false;
                }

                if($class === $map['id']) {
                    return true;
                }

                return false;
            });

            if(count($maps) === 0) {
                throw new ReflectionException("[$class] is not instantiable");
            }

            $map = array_pop($maps);

            $reflector = new ReflectionClass($map['class']);

            $instantiate_class = $map['class'];

            if( ! $reflector->isInstantiable() ) {
                throw new ReflectionException("[$class] is not instantiable");
            }
        }

        $constructor = $reflector->getConstructor();

        if(is_null($constructor))
        {
            $this->register_service($class, null, $concrete);
            return;
        }

        $parameters = $constructor->getParameters();
        $this->register_dependencies($parameters, $instantiate_class);

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

                if (! $parameter->isDefaultValueAvailable()) {
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
        }, $instantiate_class);
    }

    /**
     * Register dependencies from a class.
     * @param ReflectionParameter[] $parameters parameters from the class.
     * @param string $parent Parent class.
     * @return void
     * @throws ReflectionException
     */
    protected function register_dependencies(array $parameters, string $parent) {
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if(is_null($dependency))
            {
                continue;
            }

            $concrete = $this->maybe_apply_mapping($dependency->name, $parent);
            $this->resolve_class($dependency->name, $concrete);
        }
    }

    /**
     * Apply binding if necessary.
     *
     * @param string $class Current class.
     * @param string $parent Parent class.
     *
     * @return string
     */
    protected function maybe_apply_mapping(string $class, string $parent) {
        $maps = array_filter($this->interface_mapping, function ($map) use ($class, $parent) {
            if(count($map['when']) > 0 && ! in_array($parent, $map['when'])) {
                return false;
            }

            if($class === $map['id']) {
                return true;
            }

            return false;
        });

        if(count($maps) === 0) {
            return $class;
        }

        $map = array_pop($maps);

        return $map['class'];
    }
}

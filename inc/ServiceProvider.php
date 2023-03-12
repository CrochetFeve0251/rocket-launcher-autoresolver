<?php
namespace RocketLauncherAutoresolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use RocketLauncherAutoresolver\Services\DependencyTreeResolver;
use RocketLauncherCore\Activation\HasActivatorServiceProviderInterface;
use RocketLauncherCore\Container\AbstractServiceProvider;
use RocketLauncherCore\Deactivation\HasDeactivatorServiceProviderInterface;

class ServiceProvider extends AbstractServiceProvider
{

    /**
     * Define classes.
     *
     * @return void
     * @throws ReflectionException
     */
    protected function define()
    {
        $resolver = new DependencyTreeResolver($this);
        $resolver->resolve($this->get_root_classes());
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
}

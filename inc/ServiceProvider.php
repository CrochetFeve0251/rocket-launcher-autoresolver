<?php
namespace RocketLauncherAutoresolver;

use RocketLauncherAutoresolver\Services\DependencyTreeResolver;
use RocketLauncherCore\Activation\HasActivatorServiceProviderInterface;
use RocketLauncherCore\Container\AbstractServiceProvider;
use RocketLauncherCore\Deactivation\HasDeactivatorServiceProviderInterface;

class ServiceProvider extends AbstractServiceProvider
{

    protected function define()
    {
        $resolver = new DependencyTreeResolver($this);
        $resolver->resolve($this->get_root_classes());
    }

    protected function get_root_classes() {

        $roots = array_merge($this->get_init_subscribers(), $this->get_admin_subscribers(), $this->get_common_subscribers(), $this->get_front_subscribers());

        if($this instanceof HasActivatorServiceProviderInterface) {
            $roots = array_merge($roots, $this->get_activators());
        }

        if($this instanceof HasDeactivatorServiceProviderInterface) {
            $roots = array_merge($roots, $this->get_deactivators());
        }

        return $roots;
    }
}

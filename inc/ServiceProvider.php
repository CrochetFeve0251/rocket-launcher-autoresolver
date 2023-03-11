<?php
namespace RocketLauncherAutoresolver;

use RocketLauncherAutoresolver\Services\DependencyTreeResolver;
use RocketLauncherCore\Container\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{

    protected function define()
    {
        $resolver = new DependencyTreeResolver($this);
        $resolver->resolve($this->get_root_classes());
    }

    protected function get_root_classes() {
        return array_merge($this->get_init_subscribers(), $this->get_admin_subscribers(), $this->get_common_subscribers(), $this->get_front_subscribers());
    }
}

<?php

declare(strict_types=1);

namespace App;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class App
{
    private $container;

    /**
     * @throws Exception
     */
    public function boot(): ContainerBuilder
    {
        if (!$this->container) {
            $this->container = new ContainerBuilder();
            (new YamlFileLoader($this->container, new FileLocator(__DIR__)))->load(
                __DIR__ . '/../config/services.yaml'
            );
        }

        return $this->container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public static function env($key, $if_not_exist = false)
    {
        return $_ENV[$key] ?? $if_not_exist;
    }
}
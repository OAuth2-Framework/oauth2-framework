<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle\DependencyInjection;

use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OAuth2FrameworkWebFingerExtension extends Extension
{
    public function __construct(
        private readonly string $alias
    ) {
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(IdentifierResolver::class)->addTag('webfinger_identifier_resolver');
        $container->setAlias('webfinger.resource_repository', $config['resource_repository']);
        $container->setParameter('webfinger.path', $config['path']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/'));
        $loader->load('services.php');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($this->alias);
    }
}

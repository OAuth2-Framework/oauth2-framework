<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Core;

use OAuth2Framework\Component\Core\Message\MessageExtension;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Core\Compiler\OAuth2MessageExtensionCompilerClass;
use OAuth2Framework\ServerBundle\Component\Core\Compiler\OAuth2MessageFactoryCompilerClass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ServicesSource implements Component
{
    public function name(): string
    {
        return 'route_loader';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter('oauth2_server.server_uri', $configs['server_uri']);
        if ($configs['http_client'] !== null) {
            $container->setAlias('oauth2_server.http_client', $configs['http_client']);
        }

        $container->registerForAutoconfiguration(MessageExtension::class)->addTag('oauth2_message_extension');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config/core'));
        $loader->load('services.php');
        $loader->load('message.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->scalarNode('server_uri')
            ->info('The URI of this server. Required by several components (e.g. when JWT are issued/received)')
            ->defaultNull()
            ->end()
            ->scalarNode('http_client')
            ->info('HTTP Client. Used by some client rules.')
            ->defaultNull()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new OAuth2MessageExtensionCompilerClass());
        $container->addCompilerPass(new OAuth2MessageFactoryCompilerClass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}

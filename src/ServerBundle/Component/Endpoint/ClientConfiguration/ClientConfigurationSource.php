<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientConfiguration;

use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\ClientConfiguration\Compiler\ClientConfigurationEndpointRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientConfigurationSource implements Component
{
    public function name(): string
    {
        return 'client_configuration';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (! class_exists(ClientConfigurationEndpoint::class)) {
            return;
        }
        $config = $configs['endpoint']['client_configuration'];
        $container->setParameter('oauth2_server.endpoint.client_configuration.enabled', $config['enabled']);
        if (! $config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_configuration.realm', $config['realm']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.host', $config['host']);

        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../../Resources/config/endpoint/client_configuration'
        ));
        $loader->load('client_configuration.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (! class_exists(ClientConfigurationEndpoint::class)) {
            return;
        }
        $node->children()
            ->arrayNode($this->name())
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['realm'] === null;
            })
            ->thenInvalid('The option "realm" must be set.')
            ->end()
            ->canBeEnabled()
            ->children()
            ->scalarNode('realm')
            ->isRequired()
            ->end()
            ->scalarNode('path')
            ->defaultValue('/client/configure/{client_id}')
            ->end()
            ->scalarNode('host')
            ->info('If set, the route will be limited to that host')
            ->defaultValue('')
            ->treatFalseLike('')
            ->treatNullLike('')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        if (! class_exists(ClientConfigurationEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new ClientConfigurationEndpointRouteCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}

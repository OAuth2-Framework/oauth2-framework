<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection;

use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\Compiler\TokenIntrospectionRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\Compiler\TokenTypeHintCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenIntrospectionEndpointSource implements Component
{
    public function name(): string
    {
        return 'token_introspection';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (! class_exists(TokenIntrospectionEndpoint::class)) {
            return;
        }
        $config = $configs['endpoint']['token_introspection'];
        $container->setParameter('oauth2_server.endpoint.token_introspection.enabled', $config['enabled']);
        if (! $config['enabled']) {
            return;
        }
        $container->registerForAutoconfiguration(TokenTypeHint::class)->addTag('oauth2_server_introspection_type_hint');
        $container->setParameter('oauth2_server.endpoint.token_introspection.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.token_introspection.host', $config['host']);

        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../../Resources/config/endpoint/token_introspection'
        ));
        $loader->load('introspection.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (! class_exists(TokenIntrospectionEndpoint::class)) {
            return;
        }
        $name = $this->name();
        $rootNode->validate()
            ->ifTrue(static function ($config) use ($name): bool {
                return $config['endpoint'][$name]['enabled'] === true && (! isset($config['resource_server']['repository']) || $config['resource_server']['repository'] === null);
            })
            ->thenInvalid('The resource server repository must be set when the introspection endpoint is enabled')
            ->end()
        ;

        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->children()
            ->scalarNode('path')
            ->info('The token introspection endpoint path')
            ->defaultValue('/token/introspection')
            ->end()
            ->scalarNode('host')
            ->info('If set, the route will be limited to that host')
            ->defaultValue('')
            ->treatNullLike('')
            ->treatFalseLike('')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        if (! class_exists(TokenIntrospectionEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new TokenTypeHintCompilerPass());
        $container->addCompilerPass(new TokenIntrospectionRouteCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}

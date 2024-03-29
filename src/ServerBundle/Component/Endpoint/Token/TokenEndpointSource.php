<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Token;

use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Token\Compiler\GrantTypeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Token\Compiler\TokenEndpointExtensionCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Token\Compiler\TokenRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenEndpointSource implements Component
{
    public function name(): string
    {
        return 'token';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (! class_exists(TokenEndpoint::class)) {
            return;
        }
        $config = $configs['endpoint']['token'];
        $container->setParameter('oauth2_server.endpoint.token.enabled', $config['enabled']);
        if (! $config['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(GrantType::class)->addTag('oauth2_server_grant_type');
        $container->registerForAutoconfiguration(TokenEndpointExtension::class)->addTag(
            'oauth2_server_token_endpoint_extension'
        );
        $container->setParameter('oauth2_server.endpoint.token.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.token.host', $config['host']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config/endpoint/token'));
        $loader->load('token.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (! class_exists(TokenEndpoint::class)) {
            return;
        }
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->children()
            ->scalarNode('path')
            ->info('The token endpoint path')
            ->defaultValue('/token/get')
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
        if (! class_exists(TokenEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new GrantTypeCompilerPass());
        $container->addCompilerPass(new TokenRouteCompilerPass());
        $container->addCompilerPass(new TokenEndpointExtensionCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}

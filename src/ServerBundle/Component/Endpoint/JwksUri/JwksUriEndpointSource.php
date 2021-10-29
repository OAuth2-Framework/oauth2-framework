<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\JwksUri;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JwksUriEndpointSource implements Component
{
    public function name(): string
    {
        return 'jwks_uri';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter(
            'oauth2_server.endpoint.jwks_uri.enabled',
            $configs['endpoint']['jwks_uri']['enabled']
        );
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->children()
            ->scalarNode('path')
            ->info('The path of the key set (e.g. "/openid_connect/certs").')
            ->isRequired()
            ->end()
            ->scalarNode('key_set')
            ->info('The public key set to share with third party applications.')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new JwksUriEndpointRouteCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $configs): array
    {
        $config = $configs['endpoint']['jwks_uri'];
        if (! $config['enabled']) {
            return [];
        }
        ConfigurationHelper::addKeyset($container, 'oauth2_server.endpoint.jwks_uri', 'jwkset', [
            'value' => $config['key_set'],
        ]);
        ConfigurationHelper::addKeyUri($container, 'oauth2_server.endpoint.jwks_uri', [
            'id' => 'jose.key_set.oauth2_server.endpoint.jwks_uri',
            'path' => $config['path'],
        ]);

        return [];
    }
}

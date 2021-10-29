<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler\SignedMetadataCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SignatureSource implements Component
{
    public function name(): string
    {
        return 'signature';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['metadata']['signature'];
        $container->setParameter('oauth2_server.endpoint.metadata.signature.enabled', $config['enabled']);
        if (! $config['enabled']) {
            return;
        }

        $container->setParameter('oauth2_server.endpoint.metadata.signature.algorithm', $config['algorithm']);
        $container->setParameter('oauth2_server.endpoint.metadata.signature.key', $config['key']);
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode('signature')
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['algorithm'] === null;
            })
            ->thenInvalid('The signature algorithm must be set.')
            ->end()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['key'] === null;
            })
            ->thenInvalid('The signature key must be set.')
            ->end()
            ->children()
            ->scalarNode('algorithm')
            ->info('Signature algorithm used to sign the metadata.')
            ->end()
            ->scalarNode('key')
            ->info('Signature key.')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SignedMetadataCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $configs): array
    {
        $config = $configs['endpoint']['metadata']['signature'];
        if ($config['enabled']) {
            ConfigurationHelper::addJWSBuilder(
                $container,
                'oauth2_server.endpoint.metadata.signature',
                [$config['algorithm']],
                false
            );
            ConfigurationHelper::addKey($container, 'oauth2_server.endpoint.metadata.signature', 'jwk', [
                'value' => $config['key'],
            ]);
        }

        return [];
    }
}

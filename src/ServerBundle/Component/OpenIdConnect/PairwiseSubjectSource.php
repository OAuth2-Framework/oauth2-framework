<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler\UserInfoPairwiseSubjectCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PairwiseSubjectSource implements Component
{
    public function name(): string
    {
        return 'pairwise_subject';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['openid_connect']['pairwise_subject'];
        if (! $config['enabled']) {
            return;
        }

        $container->setAlias('oauth2_server.openid_connect.pairwise.service', $config['service']);
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && $config['service'] === null;
            })
            ->thenInvalid('The pairwise subject service must be set.')
            ->end()
            ->children()
            ->scalarNode('service')
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new UserInfoPairwiseSubjectCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}

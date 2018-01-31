<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class SignedMetadataEndpointSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.algorithm', $config['algorithm']);
        //$container->setAlias($path.'.key_set', 'jose.key_set.signed_metadata_endpoint.key_set.signature');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'signature';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {

        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['algorithm']);
                })
                ->thenInvalid('The parameter "algorithm" must be set.')
            ->end()
            ->children()
                ->scalarNode('algorithm')
                    ->info('Signature algorithm used to sign the metadata.')
                ->end()
            ->end();
    }

    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        ConfigurationHelper::addJWSBuilder($container, 'metadata_signature', [$sourceConfig['algorithm']], false);

        Assertion::keyExists($bundleConfig['key_set'], 'signature', 'The signature key set must be enabled.');
        //ConfigurationHelper::addKeyset($container, 'signed_metadata_endpoint.key_set.signature', 'jwkset', ['value' => $bundleConfig['key_set']['signature']]);
    }
}

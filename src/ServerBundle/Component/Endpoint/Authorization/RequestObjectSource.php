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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequestObjectSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents = [];

    /**
     * RequestObjectSource constructor.
     */
    public function __construct()
    {
        $this->subComponents = [
            new RequestObjectReferenceSource(),
            new RequestObjectEncryptionSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'request_object';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['endpoint']['authorization']['response_mode'];
        $container->setParameter('oauth2_server.endpoint.authorization.response_mode.allow_response_mode_parameter', $config['allow_response_mode_parameter']);

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
        ;

        $childNode->children()
            ->arrayNode('signature_algorithms')
                ->info('Supported signature algorithms.')
                ->useAttributeAsKey('name')
                ->scalarPrototype()->end()
                ->treatNullLike([])
            ->end()
        ->end();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        /*
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        if (true === $sourceConfig['enabled']) {
            $claim_checkers = ['exp', 'iat', 'nbf', 'authorization_endpoint_aud']; // FIXME
        $header_checkers = ['crit']; // FIXME
        ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['signature_algorithms'], [], ['jws_compact'], false);
        ConfigurationHelper::addClaimChecker($container, $this->name(), $claim_checkers, false);
        if (true === $sourceConfig['encryption']['enabled']) {
            ConfigurationHelper::addJWELoader($container, $this->name(), $sourceConfig['encryption']['key_encryption_algorithms'], $sourceConfig['encryption']['content_encryption_algorithms'], ['DEF'], [], ['jwe_compact'], false);
        }
         */

        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

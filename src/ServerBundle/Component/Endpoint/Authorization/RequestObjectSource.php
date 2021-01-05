<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\RequestObjectCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequestObjectSource implements Component
{
    /**
     * @var Component[]
     */
    private array $subComponents = [];

    public function __construct()
    {
        $this->subComponents = [
            new RequestObjectReferenceSource(),
            new RequestObjectEncryptionSource(),
        ];
    }

    public function name(): string
    {
        return 'request_object';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['authorization']['request_object'];
        $container->setParameter('oauth2_server.endpoint.authorization.request_object.enabled', $config['enabled']);

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
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
            ->end()
        ;

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        $sourceConfig = $config['endpoint']['authorization']['request_object'];
        if (true === $sourceConfig['enabled']) {
            $claim_checkers = ['exp', 'iat', 'nbf'/*'authorization_endpoint_aud'*/]; // FIXME
            $header_checkers = []; // FIXME
            ConfigurationHelper::addJWSVerifier($container, 'oauth2_server.endpoint.authorization.request_object', $sourceConfig['signature_algorithms'], false);
            ConfigurationHelper::addHeaderChecker($container, 'oauth2_server.endpoint.authorization.request_object', $header_checkers, false);
            ConfigurationHelper::addClaimChecker($container, 'oauth2_server.endpoint.authorization.request_object', $claim_checkers, false);
        }

        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RequestObjectCompilerPass());

        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

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

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\RequestObjectReferenceCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequestObjectReferenceSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'reference';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['endpoint']['authorization']['request_object']['reference'];
        $container->setParameter('oauth2_server.endpoint.authorization.request_object.reference.enabled', $config['enabled']);
        $container->setParameter('oauth2_server.endpoint.authorization.request_object.reference.uris_registration_required', $config['uris_registration_required']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->children()
                    ->booleanNode('uris_registration_required')
                        ->info('If true, request object reference Uris must be registered to be used (highly recommended).')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RequestObjectReferenceCompilerPass());
    }
}

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

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ResponseModeSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents = [];

    public function __construct()
    {
        $this->subComponents = [
            new FormPostResponseModeSource(),
        ];
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['endpoint']['authorization']['response_mode'];
        $container->setParameter('oauth2_server.endpoint.authorization.response_mode.allow_response_mode_parameter', $config['allow_response_mode_parameter']);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/authorization'));
        $loader->load('response_mode.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function name(): string
    {
        return 'response_mode';
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
            ->treatFalseLike([])
            ->treatNullLike([])
        ;

        $childNode->children()
            ->booleanNode('allow_response_mode_parameter')
            ->defaultFalse()
            ->end()
            ->end()
        ;

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
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
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

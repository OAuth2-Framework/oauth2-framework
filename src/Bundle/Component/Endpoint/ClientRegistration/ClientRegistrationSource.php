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

namespace OAuth2Framework\Bundle\Component\Endpoint\ClientRegistration;

use OAuth2Framework\Bundle\Component\Component;
use OAuth2Framework\Bundle\Component\ComponentWithCompilerPasses;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientRegistrationSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents = [];

    /**
     * EndpointSource constructor.
     */
    public function __construct()
    {
        $this->subComponents = [
            new InitialAccessTokenSource(),
            new SoftwareStatementSource(),
        ];
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'client_registration';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['client_registration']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_registration.path', $configs['endpoint']['client_registration']['path']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/client_registration'));
        $loader->load('client_registration.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled();

        $childNode->children()
            ->scalarNode('path')
                ->defaultValue('/client/management')
            ->end()
        ->end();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode);
        }
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        foreach ($this->subComponents as $component) {
            $component->build($container);
        };
    }
}

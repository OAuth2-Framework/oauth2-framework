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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration;

use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration\Compiler\ClientRegistrationEndpointRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        if (!class_exists(ClientRegistrationEndpoint::class)) {
            return;
        }
        $config = $configs['endpoint']['client_registration'];
        $container->setParameter('oauth2_server.endpoint.client_registration.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_registration.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.client_registration.host', $config['host']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/client_registration'));
        $loader->load('client_registration.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!class_exists(ClientRegistrationEndpoint::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled();

        $childNode->children()
            ->scalarNode('path')
                ->defaultValue('/client/management')
                ->isRequired()
            ->end()
            ->scalarNode('host')
                ->info('If set, the route will be limited to that host')
                ->defaultValue('')
                ->treatFalseLike('')
                ->treatNullLike('')
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
        if (!class_exists(ClientRegistrationEndpoint::class)) {
            return [];
        }
        if (!$config['endpoint']['client_registration']['enabled']) {
            return [];
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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        if (!class_exists(ClientRegistrationEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new ClientRegistrationEndpointRouteCompilerPass());
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

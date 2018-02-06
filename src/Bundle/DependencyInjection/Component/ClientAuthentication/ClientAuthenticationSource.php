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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\ClientAuthentication;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use OAuth2Framework\Component\TokenEndpoint\AuthenticationMethod\AuthenticationMethod;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientAuthenticationSource implements Component
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
            new NoneSource(),
            new ClientSecretBasicSource(),
            new ClientSecretPostSource(),
            new ClientAssertionJwtSource(),
        ];
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'client_authentication';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(AuthenticationMethod::class)->addTag('oauth2_server_client_authentication');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config/client_authentication'));
        $loader->load('client_authentication.php');

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
            ->addDefaultsIfNotSet();

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
}

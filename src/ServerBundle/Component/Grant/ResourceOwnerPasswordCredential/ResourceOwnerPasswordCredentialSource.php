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

namespace OAuth2Framework\ServerBundle\Component\Grant\ResourceOwnerPasswordCredential;

use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialManager;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ResourceOwnerPasswordCredentialSource implements Component
{
    public function name(): string
    {
        return 'resource_owner_password_credential';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!class_exists(ResourceOwnerPasswordCredentialsGrantType::class) || !$configs['grant']['resource_owner_password_credential']['enabled']) {
            return;
        }
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('resource_owner_password_credential.php');

        $container->setAlias(ResourceOwnerPasswordCredentialManager::class, $configs['grant']['resource_owner_password_credential']['password_credential_manager']);
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (!class_exists(ResourceOwnerPasswordCredentialsGrantType::class)) {
            return;
        }
        $node->children()
            ->arrayNode('resource_owner_password_credential')
            ->canBeEnabled()
            ->children()
            ->scalarNode('password_credential_manager')
            ->info('The password credential manager.')
            ->isRequired()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}

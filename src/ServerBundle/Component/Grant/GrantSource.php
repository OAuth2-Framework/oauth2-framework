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

namespace OAuth2Framework\ServerBundle\Component\Grant;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Grant\AuthorizationCode\AuthorizationCodeSource;
use OAuth2Framework\ServerBundle\Component\Grant\ClientCredentials\ClientCredentialsSource;
use OAuth2Framework\ServerBundle\Component\Grant\Implicit\ImplicitSource;
use OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\JwtBearerSource;
use OAuth2Framework\ServerBundle\Component\Grant\None\NoneSource;
use OAuth2Framework\ServerBundle\Component\Grant\RefreshToken\RefreshTokenSource;
use OAuth2Framework\ServerBundle\Component\Grant\ResourceOwnerPasswordCredential\ResourceOwnerPasswordCredentialSource;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class GrantSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents;

    public function __construct()
    {
        $this->subComponents = [
            new AuthorizationCodeSource(),
            new ClientCredentialsSource(),
            new ImplicitSource(),
            new RefreshTokenSource(),
            new ResourceOwnerPasswordCredentialSource(),
            new JwtBearerSource(),
            new NoneSource(),
        ];
    }

    public function name(): string
    {
        return 'grant';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/grant'));
        $loader->load('grant.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = \array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container)
    {
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

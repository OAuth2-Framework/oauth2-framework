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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Grant;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\AuthorizationCode\AuthorizationCodeSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\ClientCredentials\ClientCredentialsSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\Implicit\ImplicitSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\JwtBearer\JwtBearerSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\None\NoneSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\RefreshToken\RefreshTokenSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\ResourceOwnerPasswordCredential\ResourceOwnerPasswordCredentialSource;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class GrantSource implements Component
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
            //new JwtBearerSource(),
            //new NoneSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'grant';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(GrantType::class)->addTag('oauth2_server_grant_type');
        $container->registerForAutoconfiguration(ResponseType::class)->addTag('oauth2_server_response_type');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('grant.php');

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

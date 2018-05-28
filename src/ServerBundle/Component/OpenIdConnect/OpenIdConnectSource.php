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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use OAuth2Framework\Component\OpenIdConnect\IdToken;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ClaimSource\ClaimSource;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupport;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class OpenIdConnectSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents;

    public function __construct()
    {
        $this->subComponents = [
            new PairwiseSubjectSource(),
            new IdTokenSource(),
            new UserinfoEndpointSource(),
            new ResponseTypeSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'openid_connect';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!class_exists(IdToken::class) || !$configs['openid_connect']['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(ClaimSource::class)->addTag('oauth2_server_claim_source');
        $container->registerForAutoconfiguration(UserInfoScopeSupport::class)->addTag('oauth2_server_userinfo_scope_support');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/openid_connect'));
        $loader->load('openid_connect.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!class_exists(IdToken::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        if (!class_exists(IdToken::class)) {
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
        if (!class_exists(IdToken::class)) {
            return;
        }
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use OAuth2Framework\Component\OpenIdConnect\IdToken;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Claim;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimSource;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupport;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler\JkuSupportForIdTokenBuilderCompilerPass;
use OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler\OpenIdConnectExtensionEncryptionCompilerPass;
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

    public function name(): string
    {
        return 'openid_connect';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!\class_exists(IdToken::class) || !$configs['openid_connect']['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(Claim::class)->addTag('oauth2_server_claim');
        $container->registerForAutoconfiguration(ClaimSource::class)->addTag('oauth2_server_claim_source');
        $container->registerForAutoconfiguration(UserInfoScopeSupport::class)->addTag('oauth2_server_userinfo_scope_support');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/openid_connect'));
        $loader->load('openid_connect.php');
        $loader->load('userinfo_scope_support.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (!\class_exists(IdToken::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        if (!\class_exists(IdToken::class)) {
            return [];
        }
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = \array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container): void
    {
        if (!\class_exists(IdToken::class)) {
            return;
        }
        $container->addCompilerPass(new OpenIdConnectExtensionEncryptionCompilerPass());
        $container->addCompilerPass(new JkuSupportForIdTokenBuilderCompilerPass());

        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}

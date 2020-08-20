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

namespace OAuth2Framework\ServerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use OAuth2Framework\Component\AuthorizationCodeGrant\AbstractAuthorizationCode;
use OAuth2Framework\Component\ClientRegistrationEndpoint\AbstractInitialAccessToken;
use OAuth2Framework\Component\RefreshTokenGrant\AbstractRefreshToken;
use OAuth2Framework\ServerBundle\DependencyInjection\OAuth2FrameworkExtension;
use function Safe\realpath;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuth2FrameworkServerBundle extends Bundle
{
    /**
     * @var Component\Component[]
     */
    private $components = [];

    public function __construct()
    {
        foreach ($this->getComponents() as $component) {
            $this->components[$component->name()] = $component;
        }
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new OAuth2FrameworkExtension('oauth2_server', $this->components);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        foreach ($this->components as $component) {
            $component->build($container);
        }
        $this->loadDoctrineSchemas($container);
    }

    private function loadDoctrineSchemas(ContainerBuilder $container): void
    {
        if (!class_exists(DoctrineOrmMappingsPass::class)) {
            return;
        }
        $map = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping/AccessToken') => 'OAuth2Framework\Component\Core\AccessToken',
            realpath(__DIR__.'/Resources/config/doctrine-mapping/Client') => 'OAuth2Framework\Component\Core\Client',
        ];
        if (class_exists(AbstractAuthorizationCode::class)) {
            $map[realpath(__DIR__.'/Resources/config/doctrine-mapping/AuthorizationCodeGrant')] = 'OAuth2Framework\Component\AuthorizationCodeGrant';
        }
        if (class_exists(AbstractInitialAccessToken::class)) {
            $map[realpath(__DIR__.'/Resources/config/doctrine-mapping/ClientRegistrationEndpoint')] = 'OAuth2Framework\Component\ClientRegistrationEndpoint';
        }
        if (class_exists(AbstractRefreshToken::class)) {
            $map[realpath(__DIR__.'/Resources/config/doctrine-mapping/RefreshTokenGrant')] = 'OAuth2Framework\Component\RefreshTokenGrant';
        }
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver($map));
    }

    /**
     * @return Component\Component[]
     */
    private function getComponents(): array
    {
        return [
            new Component\Core\TrustedIssuerSource(),
            new Component\Core\ClientSource(),
            new Component\Core\AccessTokenSource(),
            new Component\Core\UserAccountSource(),
            new Component\Core\ServicesSource(),
            new Component\Core\ResourceServerSource(),
            new Component\ClientRule\ClientRuleSource(),
            new Component\ClientAuthentication\ClientAuthenticationSource(),

            new Component\Scope\ScopeSource(),
            new Component\TokenType\TokenTypeSource(),
            new Component\Endpoint\EndpointSource(),
            new Component\Grant\GrantSource(),
            new Component\OpenIdConnect\OpenIdConnectSource(),

            /*
            new Component\HttpSource(),
            new Component\KeySet(),*/
        ];
    }
}

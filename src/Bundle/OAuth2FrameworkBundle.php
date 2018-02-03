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

namespace OAuth2Framework\Bundle;

use OAuth2Framework\Bundle\DependencyInjection\Component;
use OAuth2Framework\Bundle\DependencyInjection\OAuth2FrameworkExtension;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OAuth2FrameworkBundle extends Bundle
{
    /**
     * @var Component\ComponentWithCompilerPasses[]
     */
    private $components = [];

    /**
     * JoseFrameworkBundle constructor.
     */
    public function __construct()
    {
        foreach ($this->getComponents() as $component) {
            $this->components[$component->name()] = $component;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();
        //$this->container->get('twig.loader')->addPath(__DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views', 'OAuth2FrameworkBundle');
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new OAuth2FrameworkExtension('oauth2_server', $this->components);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        foreach ($this->components as $component) {
            if ($component instanceof Component\ComponentWithCompilerPasses) {
                $compilerPasses = $component->getCompilerPasses();
                foreach ($compilerPasses as $compilerPass) {
                    $container->addCompilerPass($compilerPass);
                }
            }
        }

        /* @var SecurityExtension $extension */
        //$extension = $container->getExtension('security');
        //$extension->addSecurityListenerFactory(new OAuth2SecurityFactory());
    }

    /**
     * @return Component\ComponentWithCompilerPasses[]
     */
    private function getComponents(): array
    {
        return [
            new Component\Core\ClientSource(),
            new Component\Core\AccessTokenSource(),
            new Component\Core\UserAccountSource(),
            new Component\Core\ServicesSource(),
            new Component\Core\ResourceServerRepositorySource(),

            new Component\Scope\ScopeSource(),
            new Component\TokenType\TokenTypeSource(),
            new Component\Endpoint\EndpointSource(),
            new Component\Grant\GrantSource(),

            /*new Component\FirewallSource(),
            new Component\GrantSource(),
            new Component\EndpointSource(),
            new Component\ScopeSource(),
            new Component\OpenIdConnectSource(),
            new Component\HttpSource(),
            new Component\KeySet(),*/
            /*
            new Compiler\ClientRuleCompilerPass(),
            new Compiler\ScopePolicyCompilerPass(),
            new Compiler\ResponseFactoryCompilerPass(),
            new Compiler\TokenEndpointAuthMethodCompilerPass(),
            new Compiler\TokenIntrospectionEndpointAuthMethodCompilerPass(),
            new Compiler\GrantTypeCompilerPass(),
            new Compiler\TokenRouteCompilerPass(),
            new Compiler\TokenTypeCompilerPass(),
            new Compiler\PKCEMethodCompilerPass(),
            new Compiler\TokenIntrospectionRouteCompilerPass(),
            new Compiler\TokenRevocationRouteCompilerPass(),
            new Compiler\TokenTypeHintCompilerPass(),
            new Compiler\IssuerDiscoveryCompilerPass(),
            new Compiler\ResponseModeCompilerPass(),
            new Compiler\AccessTokenHandlerCompilerPass(),
            new Compiler\TokenEndpointExtensionCompilerPass(),
            new Compiler\AuthorizationEndpointRouteCompilerPass(),
            new Compiler\UserInfoScopeSupportCompilerPass(),
            new Compiler\UserinfoRouteCompilerPass(),
            new Compiler\UserinfoEndpointSignatureCompilerPass(),
            new Compiler\UserinfoEndpointEncryptionCompilerPass(),
            new Compiler\UserInfoPairwiseSubjectCompilerPass(),
            new Compiler\ClaimSourceCompilerPass(),
            new Compiler\UserAccountDiscoveryCompilerPass(),
            new Compiler\ParameterCheckerCompilerPass(),
            new Compiler\ResponseTypeCompilerPass(),
            new Compiler\BeforeConsentScreenCompilerPass(),
            new Compiler\AfterConsentScreenCompilerPass(),
            new Compiler\InitialAccessTokenCompilerPass(),
            new Compiler\ClientAssertionJWTEncryptionSupportConfigurationCompilerPass(),
            new Compiler\SessionManagementRouteCompilerPass(),
            new Compiler\ClientConfigurationEndpointRouteCompilerPass(),
            new Compiler\ClientRegistrationEndpointRouteCompilerPass(),
            new Compiler\MetadataRouteCompilerPass(),
            new Compiler\SignedMetadataCompilerPass(),
            new Compiler\IdTokenMetadataCompilerPass(),
            new Compiler\ClientJwtAssertionMetadataCompilerPass(),
            new Compiler\JwksUriEndpointRouteCompilerPass(),
            new Compiler\CommonMetadataCompilerPass(),
            new Compiler\CustomMetadataCompilerPass(),
            new Compiler\ScopeMetadataCompilerPass(),
            new Compiler\AuthorizationRequestMetadataCompilerPass(),
            new Compiler\RequestObjectCompilerPass(),
            new Compiler\SecurityAnnotationCheckerCompilerPass(),
        */];
    }
}

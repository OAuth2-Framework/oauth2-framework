<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server;

use OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;
use OAuth2Framework\Bundle\Server\DependencyInjection\OAuth2FrameworkServerExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OAuth2FrameworkServerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();
        $this->container->get('twig.loader')->addPath(__DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views', 'OAuth2FrameworkServerBundle');
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension($alias = 'oauth2_server')
    {
        return new OAuth2FrameworkServerExtension($alias);
    }

    /**
     * Lists the required bundles.
     *
     * @return string[]
     */
    protected function getRequiredBundles()
    {
        return ['SpomkyLabsJoseBundle', 'SensioFrameworkExtraBundle', 'SimpleBusCommandBusBundle', 'SimpleBusEventBusBundle'];
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $this->checkRequiredBundles($container);
        foreach ($this->getCompilerPasses() as $pass) {
            $container->addCompilerPass($pass);
        }
    }

    /**
     * Checks if the required bundles are enabled.
     *
     * @param ContainerBuilder $container
     *
     * @throws \LogicException
     */
    private function checkRequiredBundles(ContainerBuilder $container)
    {
        $requiredBundles = $this->getRequiredBundles();
        if (empty($requiredBundles)) {
            return;
        }
        $enabledBundles = $container->getParameter('kernel.bundles');
        $disabledBundles = array_diff($requiredBundles, array_keys($enabledBundles));

        if (!empty($disabledBundles)) {
            throw new \LogicException(sprintf('%s requires the following bundle(s): %s', $this->getName(), implode(', ', $disabledBundles)));
        }
    }

    /**
     * @return CompilerPassInterface[]
     */
    private function getCompilerPasses(): array
    {
        return [
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
        ];
    }
}

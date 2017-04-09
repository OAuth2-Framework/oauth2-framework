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

namespace OAuth2Framework\Bundle\Server\Service;

use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationRequestLoader;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Server\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use Symfony\Component\Routing\RouterInterface;
use OAuth2Framework\Component\Server\Endpoint\Metadata\Metadata;

final class MetadataBuilder
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * MetadataBuilder constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
        $this->metadata = new Metadata();
    }

    /**
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @param string $name
     * @param string $routeName
     * @param array  $routeParameters
     */
    public function setRoute(string $name, string $routeName, array $routeParameters = [])
    {
        $path = $this->router->generate($routeName, $routeParameters, RouterInterface::ABSOLUTE_URL);
        $this->metadata->set($name, $path);


        /*
        $this->metadata->set('service_documentation', 'https://my.server.com/documentation');
        $this->metadata->set('op_policy_uri', 'https://my.server.com/policy.html');
        $this->metadata->set('op_tos_uri', 'https://my.server.com/tos.html');
        */
    }

    /**
     * @param PKCEMethodManager $PKCEMethodManager
     */
    public function setCodeChallengeMethodsSupported(PKCEMethodManager $PKCEMethodManager)
    {
        $this->metadata->set('code_challenge_methods_supported', $PKCEMethodManager->names());
    }

    /**
     * @param GrantTypeManager $grantTypeManager
     */
    public function setGrantTypeManager(GrantTypeManager $grantTypeManager)
    {
        $this->metadata->set('grant_types_supported', $grantTypeManager->getSupportedGrantTypes());
    }

    /**
     * @param ResponseTypeManager $responseTypeManager
     */
    public function setResponseTypeManager(ResponseTypeManager $responseTypeManager)
    {
        $this->metadata->set('response_types_supported', $responseTypeManager->all());
    }

    /**
     * @param ResponseModeManager $responseModeManager
     */
    public function setResponseModeManager(ResponseModeManager $responseModeManager)
    {
        $this->metadata->set('response_modes_supported', $responseModeManager->all());
    }

    /**
     * @param TokenEndpointAuthMethodManager $tokenEndpointAuthMethodManager
     */
    public function setTokenEndpointAuthMethodManager(TokenEndpointAuthMethodManager $tokenEndpointAuthMethodManager)
    {
        $this->metadata->set('token_endpoint_auth_methods_supported', $tokenEndpointAuthMethodManager->all());
    }

    /**
     * @param ScopeRepositoryInterface $scopeRepository
     */
    public function setScopeRepository(ScopeRepositoryInterface $scopeRepository)
    {
        $this->metadata->set('scopes_supported', $scopeRepository->getSupportedScopes());
    }

    /**
     * @param UserInfo $userInfo
     */
    public function setUserinfo(UserInfo $userInfo)
    {
        $this->metadata->set('subject_types_supported', $userInfo->isPairwiseSubjectIdentifierSupported() ? ['public', 'pairwise'] : ['public']);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addKeyValuePair(string $name, $value)
    {
        $this->metadata->set($name, $value);
        /*
        $this->metadata->set('acr_values_supported', []);
        $this->metadata->set('request_object_signing_alg_values_supported', $this->getJWTLoader()->getSupportedSignatureAlgorithms());
        $this->metadata->set('request_object_encryption_alg_values_supported', $this->getJWTLoader()->getSupportedKeyEncryptionAlgorithms());
        $this->metadata->set('request_object_encryption_enc_values_supported', $this->getJWTLoader()->getSupportedContentEncryptionAlgorithms());
        $this->metadata->set('display_values_supported', ['page']);
        $this->metadata->set('claim_types_supported', false);
        $this->metadata->set('ui_locales_supported', ['en_US', 'fr_FR']);
        $this->metadata->set('claims_parameter_supported', false);
        */
    }

    /**
     * @param AuthorizationRequestLoader $authorizationRequestLoader
     */
    public function setAuthorizationRequestLoader(AuthorizationRequestLoader $authorizationRequestLoader)
    {
        $this->metadata->set('request_parameter_supported', $authorizationRequestLoader->isRequestObjectSupportEnabled());
        $this->metadata->set('request_uri_parameter_supported', $authorizationRequestLoader->isRequestObjectReferenceSupportEnabled());
        $this->metadata->set('require_request_uri_registration', $authorizationRequestLoader->isRequestUriRegistrationRequired());
    }
}

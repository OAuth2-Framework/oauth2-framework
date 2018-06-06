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

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeManager;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use Symfony\Component\Routing\RouterInterface;

class MetadataBuilder
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
    public function addRoute(string $name, string $routeName, array $routeParameters = [])
    {
        $path = $this->router->generate($routeName, $routeParameters, RouterInterface::ABSOLUTE_URL);
        $this->metadata->set($name, $path);
    }

    /**
     * @param PKCEMethodManager $PKCEMethodManager
     */
    public function setCodeChallengeMethodsSupported(PKCEMethodManager $PKCEMethodManager)
    {
        $this->metadata->set('code_challenge_methods_supported', $PKCEMethodManager->names());
    }

    /**
     * @param ClientAssertionJwt $clientAssertionJwt
     */
    public function setClientAssertionJwt(ClientAssertionJwt $clientAssertionJwt)
    {
        $this->metadata->set('token_endpoint_auth_signing_alg_values_supported', $clientAssertionJwt->getSupportedSignatureAlgorithms());
        $this->metadata->set('token_endpoint_auth_encryption_alg_values_supported', $clientAssertionJwt->getSupportedKeyEncryptionAlgorithms());
        $this->metadata->set('token_endpoint_auth_encryption_enc_values_supported', $clientAssertionJwt->getSupportedContentEncryptionAlgorithms());
    }

    /**
     * @param GrantTypeManager $grantTypeManager
     */
    public function setGrantTypeManager(GrantTypeManager $grantTypeManager)
    {
        $this->metadata->set('grant_types_supported', $grantTypeManager->list());
    }

    /**
     * @param ResponseTypeManager $responseTypeManager
     */
    public function setResponseTypeManager(ResponseTypeManager $responseTypeManager)
    {
        $this->metadata->set('response_types_supported', $responseTypeManager->list());
    }

    /**
     * @param ResponseModeManager $responseModeManager
     */
    public function setResponseModeManager(ResponseModeManager $responseModeManager)
    {
        $this->metadata->set('response_modes_supported', $responseModeManager->list());
    }

    /**
     * @param AuthenticationMethodManager $tokenEndpointAuthMethodManager
     */
    public function setTokenEndpointAuthMethodManager(AuthenticationMethodManager $tokenEndpointAuthMethodManager)
    {
        $this->metadata->set('token_endpoint_auth_methods_supported', $tokenEndpointAuthMethodManager->list());
    }

    /**
     * @param ScopeRepository $scopeRepository
     */
    public function setScopeRepository(ScopeRepository $scopeRepository)
    {
        $this->metadata->set('scopes_supported', $scopeRepository->all());
    }

    /**
     * @param UserInfo $userInfo
     */
    public function setUserinfo(UserInfo $userInfo)
    {
        $this->metadata->set('subject_types_supported', $userInfo->isPairwiseSubjectIdentifierSupported() ? ['public', 'pairwise'] : ['public']);
        $this->metadata->set('claims_supported', $userInfo->getSupportedClaims());
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addKeyValuePair(string $name, $value)
    {
        $this->metadata->set($name, $value);
    }

    /**
     * @param AuthorizationRequestLoader $authorizationRequestLoader
     */
    public function setAuthorizationRequestLoader(AuthorizationRequestLoader $authorizationRequestLoader)
    {
        $requestObjectSupported = $authorizationRequestLoader->isRequestObjectSupportEnabled();
        $this->metadata->set('request_parameter_supported', $requestObjectSupported);
        if ($requestObjectSupported) {
            $this->metadata->set('request_uri_parameter_supported', $authorizationRequestLoader->isRequestObjectReferenceSupportEnabled());
            $this->metadata->set('require_request_uri_registration', $authorizationRequestLoader->isRequestUriRegistrationRequired());
            $this->metadata->set('request_object_signing_alg_values_supported', $authorizationRequestLoader->getSupportedSignatureAlgorithms());
            $this->metadata->set('request_object_encryption_alg_values_supported', $authorizationRequestLoader->getSupportedKeyEncryptionAlgorithms());
            $this->metadata->set('request_object_encryption_enc_values_supported', $authorizationRequestLoader->getSupportedContentEncryptionAlgorithms());
        }
    }
}

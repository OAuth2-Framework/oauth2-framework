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

use OAuth2Framework\Component\Server\OpenIdConnect\Metadata as BaseMetadata;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\ClientAssertionJwt;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Metadata extends BaseMetadata
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Metadata constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param AuthorizationFactoryInterface $authorization_factory
     */
    public function setAuthorizationFactory(AuthorizationFactoryInterface $authorization_factory)
    {
        //$authorization_factory->isResponseModeParameterSupported()
    }

    /**
     * @param PKCEMethodManagerInterface $pkce_method_manager
     */
    public function setCodeChallengeMethodsSupported(PKCEMethodManagerInterface $pkce_method_manager)
    {
        $this->set('code_challenge_methods_supported', $pkce_method_manager->getPKCEMethodNames());
    }

    /**
     * @param ClientAssertionJwt $client_assertion_jwt
     */
    public function setTokenEndpointAuthAssertionJwt(ClientAssertionJwt $client_assertion_jwt)
    {
        $methods = [
            'getSupportedSignatureAlgorithms'         => 'token_endpoint_auth_signing_alg_values_supported',
            'getSupportedKeyEncryptionAlgorithms'     => 'token_endpoint_auth_encryption_alg_values_supported',
            'getSupportedContentEncryptionAlgorithms' => 'token_endpoint_auth_encryption_enc_values_supported',
        ];

        foreach ($methods as $method => $key) {
            $value = $client_assertion_jwt->$method();
            if (!empty($value)) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * @param TokenEndpointAuthMethodManagerInterface $token_endpoint_auth_method_manager
     */
    public function setTokenEndpointAuthMethodManager(TokenEndpointAuthMethodManagerInterface $token_endpoint_auth_method_manager)
    {
        $methods = [
            'getSupportedTokenEndpointAuthMethods' => 'token_endpoint_auth_methods_supported',
        ];

        foreach ($methods as $method => $key) {
            $value = $token_endpoint_auth_method_manager->$method();
            if (!empty($value)) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * @param IdTokenManagerInterface $id_token_manager
     */
    public function setIdTokenManager(IdTokenManagerInterface $id_token_manager)
    {
        $methods = [
            'getSupportedSignatureAlgorithms'         => 'id_token_signing_alg_values_supported',
            'getSupportedKeyEncryptionAlgorithms'     => 'id_token_encryption_alg_values_supported',
            'getSupportedContentEncryptionAlgorithms' => 'id_token_encryption_enc_values_supported',
        ];

        foreach ($methods as $method => $key) {
            $value = $id_token_manager->$method();
            if (!empty($value)) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * @param string $name
     * @param string $route_name
     * @param array  $route_parameters
     */
    public function setRoute($name, $route_name, array $route_parameters = [])
    {
        $route = $this->router->generate($route_name, $route_parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        $this->set($name, $route);
    }

    /**
     * @param UserInfoEndpointInterface $userinfo_endpoint
     * @param UserInfoInterface         $userinfo
     */
    public function setUserinfoEndpoint(UserInfoEndpointInterface $userinfo_endpoint, UserInfoInterface $userinfo)
    {
        $methods = [
            'getSupportedSignatureAlgorithms'         => 'userinfo_signing_alg_values_supported',
            'getSupportedKeyEncryptionAlgorithms'     => 'userinfo_encryption_alg_values_supported',
            'getSupportedContentEncryptionAlgorithms' => 'userinfo_encryption_enc_values_supported',
        ];

        foreach ($methods as $method => $key) {
            $value = $userinfo_endpoint->$method();
            if (!empty($value)) {
                $this->set($key, $value);
            }
        }

        $subject_types_supported = ['public'];
        if ($userinfo->isPairwiseSubjectIdentifierSupported()) {
            $subject_types_supported[] = 'pairwise';
        }
        $this->set('subject_types_supported', $subject_types_supported);
    }

    /**
     * @param GrantTypeManagerInterface $grant_type_manager
     */
    public function setGrantTypeManager(GrantTypeManagerInterface $grant_type_manager)
    {
        $this->set('grant_types_supported', $grant_type_manager->getSupportedGrantTypes());
    }

    /**
     * @param AuthorizationRequestLoaderInterface $authorization_request_loader
     */
    public function setAuthorizationRequestLoader(AuthorizationRequestLoaderInterface $authorization_request_loader)
    {
        $this->set('request_parameter_supported', $authorization_request_loader->isRequestObjectSupportEnabled());
        if (true === $authorization_request_loader->isRequestObjectSupportEnabled()) {
            $this->set('request_uri_parameter_supported', $authorization_request_loader->isRequestObjectReferenceSupportEnabled());
            if (true === $authorization_request_loader->isRequestObjectReferenceSupportEnabled()) {
                $this->set('require_request_uri_registration', $authorization_request_loader->isRequestUriRegistrationRequired());
            }
            $methods = [
                'getSupportedSignatureAlgorithms'         => 'request_object_signing_alg_values_supported',
                'getSupportedKeyEncryptionAlgorithms'     => 'request_object_encryption_alg_values_supported',
                'getSupportedContentEncryptionAlgorithms' => 'request_object_encryption_enc_values_supported',
            ];

            foreach ($methods as $method => $key) {
                $value = $authorization_request_loader->$method();
                if (!empty($value)) {
                    $this->set($key, $value);
                }
            }
        }
    }

    /**
     * @param ResponseTypeManagerInterface $response_type_manager
     */
    public function setResponseTypeManager(ResponseTypeManagerInterface $response_type_manager)
    {
        $this->set('response_types_supported', $response_type_manager->getSupportedResponseTypes());
    }

    /**
     * @param ResponseModeManagerInterface $response_mode_manager
     */
    public function setResponseModeManager(ResponseModeManagerInterface $response_mode_manager)
    {
        $this->set('response_modes_supported', $response_mode_manager->getSupportedResponseModes());
    }
}

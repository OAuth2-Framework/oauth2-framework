<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimManager;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use Symfony\Component\Routing\RouterInterface;

class MetadataBuilder
{
    private Metadata $metadata;

    public function __construct(
        private RouterInterface $router
    ) {
        $this->metadata = new Metadata();
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function addRoute(string $name, string $routeName, array $routeParameters = []): self
    {
        $path = $this->router->generate($routeName, $routeParameters, RouterInterface::ABSOLUTE_URL);
        $this->metadata->set($name, $path);

        return $this;
    }

    public function setCodeChallengeMethodsSupported(PKCEMethodManager $PKCEMethodManager): self
    {
        $this->metadata->set('code_challenge_methods_supported', $PKCEMethodManager->names());

        return $this;
    }

    public function setClientAssertionJwt(ClientAssertionJwt $clientAssertionJwt): self
    {
        $this->metadata->set(
            'token_endpoint_auth_signing_alg_values_supported',
            $clientAssertionJwt->getSupportedSignatureAlgorithms()
        );
        $this->metadata->set(
            'token_endpoint_auth_encryption_alg_values_supported',
            $clientAssertionJwt->getSupportedKeyEncryptionAlgorithms()
        );
        $this->metadata->set(
            'token_endpoint_auth_encryption_enc_values_supported',
            $clientAssertionJwt->getSupportedContentEncryptionAlgorithms()
        );

        return $this;
    }

    public function setGrantTypeManager(GrantTypeManager $grantTypeManager): self
    {
        $this->metadata->set('grant_types_supported', $grantTypeManager->list());

        return $this;
    }

    public function setResponseTypeManager(ResponseTypeManager $responseTypeManager): self
    {
        $this->metadata->set('response_types_supported', $responseTypeManager->list());

        return $this;
    }

    public function setResponseModeManager(ResponseModeManager $responseModeManager): self
    {
        $this->metadata->set('response_modes_supported', $responseModeManager->list());

        return $this;
    }

    public function setTokenEndpointAuthMethodManager(AuthenticationMethodManager $tokenEndpointAuthMethodManager): self
    {
        $this->metadata->set('token_endpoint_auth_methods_supported', $tokenEndpointAuthMethodManager->list());

        return $this;
    }

    public function setScopeRepository(ScopeRepository $scopeRepository): self
    {
        $this->metadata->set('scopes_supported', $scopeRepository->all());

        return $this;
    }

    public function setUserinfo(UserInfo $userInfo): self
    {
        $this->metadata->set(
            'subject_types_supported',
            $userInfo->isPairwiseSubjectIdentifierSupported() ? ['public', 'pairwise'] : ['public']
        );

        return $this;
    }

    public function setClaimsSupported(ClaimManager $claimManager): self
    {
        $this->metadata->set('claims_supported', $claimManager->list());

        return $this;
    }

    public function addKeyValuePair(string $name, mixed $value): self
    {
        $this->metadata->set($name, $value);

        return $this;
    }

    public function setAuthorizationRequestLoader(AuthorizationRequestLoader $authorizationRequestLoader): self
    {
        $requestObjectSupported = $authorizationRequestLoader->isRequestObjectSupportEnabled();
        $this->metadata->set('request_parameter_supported', $requestObjectSupported);
        if ($requestObjectSupported) {
            $this->metadata->set(
                'request_uri_parameter_supported',
                $authorizationRequestLoader->isRequestObjectReferenceSupportEnabled()
            );
            $this->metadata->set(
                'require_request_uri_registration',
                $authorizationRequestLoader->isRequestUriRegistrationRequired()
            );
            $this->metadata->set(
                'request_object_signing_alg_values_supported',
                $authorizationRequestLoader->getSupportedSignatureAlgorithms()
            );
            $this->metadata->set(
                'request_object_encryption_alg_values_supported',
                $authorizationRequestLoader->getSupportedKeyEncryptionAlgorithms()
            );
            $this->metadata->set(
                'request_object_encryption_enc_values_supported',
                $authorizationRequestLoader->getSupportedContentEncryptionAlgorithms()
            );
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Consent;

class Consent
{
    private ?string $grantedScope = null;

    private ?string $grantedClaims = null;

    public function __construct(
        private string $clientId,
        private string $userAccountId,
        private string $requestedScope,
        private string $requestedClaims
    ) {
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getUserAccountId(): string
    {
        return $this->userAccountId;
    }

    public function getRequestedScope(): string
    {
        return $this->requestedScope;
    }

    public function getRequestedClaims(): string
    {
        return $this->requestedClaims;
    }

    public function getGrantedScope(): string
    {
        return $this->grantedScope;
    }

    public function setGrantedScope(string $grantedScope): void
    {
        $this->grantedScope = $grantedScope;
    }

    public function getGrantedClaims(): string
    {
        return $this->grantedClaims;
    }

    public function setGrantedClaims(string $grantedClaims): void
    {
        $this->grantedClaims = $grantedClaims;
    }
}

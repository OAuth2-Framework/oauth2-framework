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

namespace OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use function Safe\sprintf;

class AuthorizationRequest
{
    public const CONSENT_NOT_GIVEN = 'consent_not_given';
    public const CONSENT_ALLOW = 'consent_allow';
    public const CONSENT_DENY = 'consent_deny';
    /**
     * @var string
     */
    private $authorized = self::CONSENT_NOT_GIVEN;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserAccount|null
     */
    private $userAccount;

    /**
     * @var DataBag
     */
    private $metadata;

    /**
     * @var TokenType|null
     */
    private $tokenType;

    /**
     * @var ResponseType]
     */
    private $responseType;

    /**
     * @var ResponseMode|null
     */
    private $responseMode;

    /**
     * @var array
     */
    private $queryParameters = [];

    /**
     * @var string|null
     */
    private $redirectUri;

    /**
     * @var array
     */
    private $consentScreenOptions = [];

    /**
     * @var array
     */
    private $responseParameters = [];

    /**
     * @var array
     */
    private $responseHeaders = [];

    /**
     * @var ResourceServer|null
     */
    private $resourceServer;

    /**
     * @var array
     */
    private $attributes = [];

    public function __construct(Client $client, array $queryParameters)
    {
        $this->client = $client;
        $this->queryParameters = $queryParameters;
        $this->metadata = new DataBag([]);
    }

    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    public function hasQueryParam(string $param): bool
    {
        return \array_key_exists($param, $this->queryParameters);
    }

    /**
     * @return mixed
     */
    public function getQueryParam(string $param)
    {
        Assertion::true($this->hasQueryParam($param), sprintf('Invalid parameter "%s".', $param));

        return $this->queryParameters[$param];
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $param): bool
    {
        return \array_key_exists($param, $this->attributes);
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $param)
    {
        Assertion::true($this->hasQueryParam($param), sprintf('Invalid attribute "%s".', $param));

        return $this->attributes[$param];
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setTokenType(TokenType $tokenType): void
    {
        $this->tokenType = $tokenType;
    }

    public function getTokenType(): ?TokenType
    {
        return $this->tokenType;
    }

    public function setResponseType(ResponseType $responseType): void
    {
        $this->responseType = $responseType;
    }

    public function getResponseType(): ResponseType
    {
        return $this->responseType;
    }

    public function setResponseMode(ResponseMode $responseMode): void
    {
        $this->responseMode = $responseMode;
    }

    public function hasResponseMode(): bool
    {
        return null === $this->responseMode;
    }

    public function getResponseMode(): ResponseMode
    {
        Assertion::notNull($this->responseMode, 'No response mode');

        return $this->responseMode;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function hasRedirectUri(): bool
    {
        return null !== $this->redirectUri;
    }

    public function getRedirectUri(): string
    {
        Assertion::notNull($this->redirectUri, 'internal_server_error');

        return $this->redirectUri;
    }

    public function setUserAccount(UserAccount $userAccount): void
    {
        $this->userAccount = $userAccount;
    }

    public function hasUserAccount(): bool
    {
        return null !== $this->userAccount;
    }

    public function getUserAccount(): UserAccount
    {
        Assertion::notNull($this->userAccount, 'internal_server_error');

        return $this->userAccount;
    }

    /**
     * @param mixed $value
     */
    public function setResponseParameter(string $responseParameter, $value): void
    {
        $this->responseParameters[$responseParameter] = $value;
    }

    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }

    /**
     * @return mixed
     */
    public function getResponseParameter(string $param)
    {
        Assertion::true($this->hasResponseParameter($param), sprintf('Invalid response parameter "%s".', $param));

        return $this->getResponseParameters()[$param];
    }

    public function hasResponseParameter(string $param): bool
    {
        return \array_key_exists($param, $this->getResponseParameters());
    }

    /**
     * @param mixed $value
     */
    public function setResponseHeader(string $responseHeader, $value): void
    {
        $this->responseHeaders[$responseHeader] = $value;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @return string[]
     */
    public function getPrompt(): array
    {
        if (!$this->hasQueryParam('prompt')) {
            return [];
        }

        return \explode(' ', $this->getQueryParam('prompt'));
    }

    public function hasUiLocales(): bool
    {
        return $this->hasQueryParam('ui_locales');
    }

    /**
     * @return string[]
     */
    public function getUiLocales(): array
    {
        return $this->hasQueryParam('ui_locales') ? \explode(' ', $this->getQueryParam('ui_locales')) : [];
    }

    public function hasPrompt(string $prompt): bool
    {
        return \in_array($prompt, $this->getPrompt(), true);
    }

    public function isAuthorized(): bool
    {
        return self::CONSENT_ALLOW === $this->authorized;
    }

    public function hasConsentBeenGiven(): bool
    {
        return self::CONSENT_NOT_GIVEN !== $this->authorized;
    }

    public function allow(): void
    {
        $this->authorized = self::CONSENT_ALLOW;
    }

    public function deny(): void
    {
        $this->authorized = self::CONSENT_DENY;
    }

    public function getResourceServer(): ?ResourceServer
    {
        return $this->resourceServer;
    }

    public function setResourceServer(ResourceServer $resourceServer): void
    {
        $this->resourceServer = $resourceServer;
    }

    /**
     * @param mixed $value
     */
    public function setConsentScreenOption(string $option, $value): void
    {
        $this->consentScreenOptions[$option] = $value;
    }

    public function hasScope(): bool
    {
        return $this->hasQueryParam('scope');
    }

    public function getScope(): string
    {
        return $this->getQueryParam('scope');
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }
}

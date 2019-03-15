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

namespace OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class AuthorizationRequest
{
    /**
     * @var bool
     */
    private $authorized;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserAccount|null
     */
    private $userAccount = null;

    /**
     * @var DataBag
     */
    private $metadata;

    /**
     * @var TokenType|null
     */
    private $tokenType = null;

    /**
     * @var ResponseType]
     */
    private $responseType = null;

    /**
     * @var ResponseMode|null
     */
    private $responseMode = null;

    /**
     * @var array
     */
    private $queryParameters = [];

    /**
     * @var string|null
     */
    private $redirectUri = null;

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

    public function getQueryParam(string $param)
    {
        if (!$this->hasQueryParam($param)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Invalid parameter "%s".', $param));
        }

        return $this->queryParameters[$param];
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

    public function getResponseMode(): ?ResponseMode
    {
        return $this->responseMode;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setUserAccount(UserAccount $userAccount): void
    {
        $this->userAccount = $userAccount;
    }

    public function getUserAccount(): ?UserAccount
    {
        return $this->userAccount;
    }

    public function setResponseParameter(string $responseParameter, $value): void
    {
        $this->responseParameters[$responseParameter] = $value;
    }

    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }

    public function getResponseParameter(string $param)
    {
        if (!$this->hasResponseParameter($param)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Invalid response parameter "%s".', $param));
        }

        return $this->getResponseParameters()[$param];
    }

    public function hasResponseParameter(string $param): bool
    {
        return \array_key_exists($param, $this->getResponseParameters());
    }

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
        return $this->authorized;
    }

    public function allow(): void
    {
        $this->authorized = true;
    }

    public function deny(): void
    {
        $this->authorized = false;
    }

    public function getResourceServer(): ?ResourceServer
    {
        return $this->resourceServer;
    }

    public function setResourceServer(ResourceServer $resourceServer): void
    {
        $this->resourceServer = $resourceServer;
    }

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

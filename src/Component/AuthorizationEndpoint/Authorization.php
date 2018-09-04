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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class Authorization
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
     * @var null|bool
     */
    private $userAccountFullyAuthenticated = null;

    /**
     * @var array
     */
    private $data = [];

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
     * @var null|ResourceServer
     */
    private $resourceServer = null;

    /**
     * Authorization constructor.
     */
    private function __construct(Client $client, array $queryParameters)
    {
        $this->client = $client;
        $this->queryParameters = $queryParameters;
        $this->metadata = new DataBag([]);
    }

    /**
     * @return Authorization
     */
    public static function create(Client $client, array $queryParameters): self
    {
        return new self($client, $queryParameters);
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
            throw new \InvalidArgumentException(\sprintf('Invalid parameter "%s".', $param));
        }

        return $this->queryParameters[$param];
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return Authorization
     */
    public function withTokenType(TokenType $tokenType): self
    {
        $this->tokenType = $tokenType;

        return $this;
    }

    public function getTokenType(): ?TokenType
    {
        return $this->tokenType;
    }

    /**
     * @return Authorization
     */
    public function withResponseType(ResponseType $responseType): self
    {
        $this->responseType = $responseType;

        return $this;
    }

    public function getResponseType(): ResponseType
    {
        return $this->responseType;
    }

    /**
     * @return Authorization
     */
    public function withResponseMode(ResponseMode $responseMode): self
    {
        $this->responseMode = $responseMode;

        return $this;
    }

    public function getResponseMode(): ?ResponseMode
    {
        return $this->responseMode;
    }

    /**
     * @return Authorization
     */
    public function withRedirectUri(string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;
        $this->metadata->with('redirect_uri', $redirectUri);

        return $this;
    }

    public function getClaims(): ?array
    {
        if ($this->metadata->has('claims')) {
            return \json_decode($this->metadata->get('claims'), true);
        }

        return null;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    /**
     * @return Authorization
     */
    public function withUserAccount(UserAccount $userAccount, bool $isFullyAuthenticated): self
    {
        $this->userAccount = $userAccount;
        $this->userAccountFullyAuthenticated = $isFullyAuthenticated;

        return $this;
    }

    public function getUserAccount(): ?UserAccount
    {
        return $this->userAccount;
    }

    /**
     * @return Authorization
     */
    public function withResponseParameter(string $responseParameter, $value): self
    {
        $this->responseParameters[$responseParameter] = $value;

        return $this;
    }

    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }

    public function getResponseParameter(string $param)
    {
        if (!$this->hasResponseParameter($param)) {
            throw new \InvalidArgumentException(\sprintf('Invalid response parameter "%s".', $param));
        }

        return $this->getResponseParameters()[$param];
    }

    public function hasResponseParameter(string $param): bool
    {
        return \array_key_exists($param, $this->getResponseParameters());
    }

    /**
     * @return Authorization
     */
    public function withResponseHeader(string $responseHeader, $value): self
    {
        $this->responseHeaders[$responseHeader] = $value;

        return $this;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function isUserAccountFullyAuthenticated(): ?bool
    {
        return $this->userAccountFullyAuthenticated;
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

    /**
     * @return Authorization
     */
    public function allow(): self
    {
        $this->authorized = true;

        return $this;
    }

    /**
     * @return Authorization
     */
    public function deny(): self
    {
        $this->authorized = false;

        return $this;
    }

    public function hasData(string $key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    public function getData(string $key)
    {
        if (!$this->hasData($key)) {
            throw new \InvalidArgumentException(\sprintf('Invalid data "%s".', $key));
        }

        return $this->data[$key];
    }

    /**
     * @return Authorization
     */
    public function withData(string $key, $data): self
    {
        $this->data[$key] = $data;

        return $this;
    }

    public function getResourceServer(): ?ResourceServer
    {
        return $this->resourceServer;
    }

    /**
     * @return Authorization
     */
    public function withResourceServer(ResourceServer $resourceServer): self
    {
        $this->resourceServer = $resourceServer;

        return $this;
    }

    /**
     * @return Authorization
     */
    public function withConsentScreenOption(string $option, $value): self
    {
        $this->consentScreenOptions[$option] = $value;

        return $this;
    }

    /**
     * @return Authorization
     */
    public function withoutConsentScreenOption(string $option): self
    {
        if (!\array_key_exists($option, $this->consentScreenOptions)) {
            return $this;
        }

        unset($this->consentScreenOptions[$option]);

        return $this;
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

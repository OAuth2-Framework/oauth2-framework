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
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\TokenType\TokenType;

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
     *
     * @param Client $client
     * @param array  $queryParameters
     */
    private function __construct(Client $client, array $queryParameters)
    {
        $this->client = $client;
        $this->queryParameters = $queryParameters;
        $this->metadata = DataBag::create([]);
    }

    /**
     * @param Client $client
     * @param array  $queryParameters
     *
     * @return Authorization
     */
    public static function create(Client $client, array $queryParameters): self
    {
        return new self($client, $queryParameters);
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    /**
     * @param string $param
     *
     * @return bool
     */
    public function hasQueryParam(string $param): bool
    {
        return array_key_exists($param, $this->queryParameters);
    }

    /**
     * @param string $param
     *
     * @return mixed
     */
    public function getQueryParam(string $param)
    {
        if (!$this->hasQueryParam($param)) {
            throw new \InvalidArgumentException(sprintf('Invalid parameter "%s".', $param));
        }

        return $this->queryParameters[$param];
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param TokenType $tokenType
     *
     * @return Authorization
     */
    public function withTokenType(TokenType $tokenType): self
    {
        $this->tokenType = $tokenType;

        return $this;
    }

    /**
     * @return null|TokenType
     */
    public function getTokenType(): ? TokenType
    {
        return $this->tokenType;
    }

    /**
     * @param ResponseType $responseType
     *
     * @return Authorization
     */
    public function withResponseType(ResponseType $responseType): self
    {
        $this->responseType = $responseType;

        return $this;
    }

    /**
     * @return ResponseType
     */
    public function getResponseType(): ResponseType
    {
        return $this->responseType;
    }

    /**
     * @param ResponseMode $responseMode
     *
     * @return Authorization
     */
    public function withResponseMode(ResponseMode $responseMode): self
    {
        $this->responseMode = $responseMode;

        return $this;
    }

    /**
     * @return null|ResponseMode
     */
    public function getResponseMode(): ? ResponseMode
    {
        return $this->responseMode;
    }

    /**
     * @param string $redirectUri
     *
     * @return Authorization
     */
    public function withRedirectUri(string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;
        $this->metadata->with('redirect_uri', $redirectUri);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getRedirectUri(): ? string
    {
        return $this->redirectUri;
    }

    /**
     * @param UserAccount $userAccount
     * @param bool        $isFullyAuthenticated
     *
     * @return Authorization
     */
    public function withUserAccount(UserAccount $userAccount, bool $isFullyAuthenticated): self
    {
        $this->userAccount = $userAccount;
        $this->userAccountFullyAuthenticated = $isFullyAuthenticated;

        return $this;
    }

    /**
     * @return null|UserAccount
     */
    public function getUserAccount(): ? UserAccount
    {
        return $this->userAccount;
    }

    /**
     * @param string $responseParameter
     * @param mixed  $value
     *
     * @return Authorization
     */
    public function withResponseParameter(string $responseParameter, $value): self
    {
        $this->responseParameters[$responseParameter] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }

    /**
     * @param string $param
     *
     * @return mixed
     */
    public function getResponseParameter(string $param)
    {
        if (!$this->hasResponseParameter($param)) {
            throw new \InvalidArgumentException(sprintf('Invalid response parameter "%s".', $param));
        }

        return $this->getResponseParameters()[$param];
    }

    /**
     * @param string $param
     *
     * @return bool
     */
    public function hasResponseParameter(string $param): bool
    {
        return array_key_exists($param, $this->getResponseParameters());
    }

    /**
     * @param string $responseHeader
     * @param mixed  $value
     *
     * @return Authorization
     */
    public function withResponseHeader(string $responseHeader, $value): self
    {
        $this->responseHeaders[$responseHeader] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @return bool|null
     */
    public function isUserAccountFullyAuthenticated(): ? bool
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

        return explode(' ', $this->getQueryParam('prompt'));
    }

    /**
     * @return bool
     */
    public function hasUiLocales(): bool
    {
        return $this->hasQueryParam('ui_locales');
    }

    /**
     * @return string[]
     */
    public function getUiLocales(): array
    {
        return $this->hasQueryParam('ui_locales') ? explode(' ', $this->getQueryParam('ui_locales')) : [];
    }

    /**
     * @param string $prompt
     *
     * @return bool
     */
    public function hasPrompt(string $prompt): bool
    {
        return in_array($prompt, $this->getPrompt());
    }

    /**
     * @return bool
     */
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

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasData(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getData(string $key)
    {
        if (!$this->hasData($key)) {
            throw new \InvalidArgumentException(sprintf('Invalid data "%s".', $key));
        }

        return $this->data[$key];
    }

    /**
     * @param string $key
     * @param mixed  $data
     *
     * @return Authorization
     */
    public function withData(string $key, $data): self
    {
        $this->data[$key] = $data;

        return $this;
    }

    /**
     * @return null|ResourceServer
     */
    public function getResourceServer(): ? ResourceServer
    {
        return $this->resourceServer;
    }

    /**
     * @param ResourceServer $resourceServer
     *
     * @return Authorization
     */
    public function withResourceServer(ResourceServer $resourceServer): self
    {
        $this->resourceServer = $resourceServer;

        return $this;
    }

    /**
     * @param string $option
     * @param mixed  $value
     *
     * @return Authorization
     */
    public function withConsentScreenOption(string $option, $value): self
    {
        $this->consentScreenOptions[$option] = $value;

        return $this;
    }

    /**
     * @param string $option
     *
     * @return Authorization
     */
    public function withoutConsentScreenOption(string $option): self
    {
        if (!array_key_exists($option, $this->consentScreenOptions)) {
            return $this;
        }

        unset($this->consentScreenOptions[$option]);

        return $this;
    }

    public function hasScope(): bool
    {
        return $this->hasQueryParam('scope');
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->getQueryParam('scope');
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }
}

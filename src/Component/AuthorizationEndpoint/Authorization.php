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
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\TokenType\TokenType;

class Authorization
{
    /**
     * @var bool|null
     */
    private $authorized = null;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserAccount|null
     */
    private $userAccount = null;

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
        $clone = clone $this;
        $clone->tokenType = $tokenType;

        return $clone;
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
        $clone = clone $this;
        $clone->responseType = $responseType;

        return $clone;
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
        $clone = clone $this;
        $clone->responseMode = $responseMode;

        return $clone;
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
        $clone = clone $this;
        $clone->redirectUri = $redirectUri;

        return $clone;
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
        $clone = clone $this;
        $clone->userAccount = $userAccount;
        $clone->userAccountFullyAuthenticated = $isFullyAuthenticated;

        return $clone;
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
        $clone = clone $this;
        $clone->responseParameters[$responseParameter] = $value;

        return $clone;
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
        $clone = clone $this;
        $clone->responseHeaders[$responseHeader] = $value;

        return $clone;
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
     * @return bool|null
     */
    public function isAuthorized(): ? bool
    {
        return $this->authorized;
    }

    /**
     * @return Authorization
     */
    public function allow(): self
    {
        $clone = clone $this;
        $clone->authorized = true;

        return $clone;
    }

    /**
     * @return Authorization
     */
    public function deny(): self
    {
        $clone = clone $this;
        $clone->authorized = false;

        return $clone;
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
        $clone = clone $this;
        $clone->data[$key] = $data;

        return $clone;
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
        $clone = clone $this;
        $clone->resourceServer = $resourceServer;

        return $clone;
    }

    /**
     * @param string $option
     * @param mixed  $value
     *
     * @return Authorization
     */
    public function withConsentScreenOption(string $option, $value): self
    {
        $clone = clone $this;
        $clone->consentScreenOptions[$option] = $value;

        return $clone;
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

        $clone = clone $this;
        unset($clone->consentScreenOptions[$option]);

        return $clone;
    }
}

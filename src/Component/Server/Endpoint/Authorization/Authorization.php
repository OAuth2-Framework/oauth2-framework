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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;
use OAuth2Framework\Component\Server\ResponseMode\ResponseModeInterface;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeInterface;
use OAuth2Framework\Component\Server\TokenType\TokenTypeInterface;

final class Authorization
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
     * @var UserAccountInterface|null
     */
    private $userAccount = null;

    /**
     * @var null|bool
     */
    private $userAccountFullyAuthenticated = null;

    /**
     * @var string[]
     */
    private $scopes = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var TokenTypeInterface|null
     */
    private $tokenType = null;

    /**
     * @var ResponseTypeInterface[]
     */
    private $responseTypes = [];

    /**
     * @var ResponseModeInterface|null
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
     * @var null|ResourceServerInterface
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
    public static function create(Client $client, array $queryParameters): Authorization
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
        Assertion::true($this->hasQueryParam($param), sprintf('Invalid parameter \'%s\'', $param));

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
     * @param TokenTypeInterface $tokenType
     *
     * @return Authorization
     */
    public function withTokenType(TokenTypeInterface $tokenType): Authorization
    {
        $clone = clone $this;
        $clone->tokenType = $tokenType;

        return $clone;
    }

    /**
     * @return null|TokenTypeInterface
     */
    public function getTokenType(): ? TokenTypeInterface
    {
        return $this->tokenType;
    }

    /**
     * @param ResponseTypeInterface[] $responseTypes
     *
     * @return Authorization
     */
    public function withResponseTypes(array $responseTypes): Authorization
    {
        Assertion::allIsInstanceOf($responseTypes, ResponseTypeInterface::class);
        $clone = clone $this;
        $clone->responseTypes = $responseTypes;

        return $clone;
    }

    /**
     * @return ResponseTypeInterface[]
     */
    public function getResponseTypes(): array
    {
        return $this->responseTypes;
    }

    /**
     * @param ResponseModeInterface $responseMode
     *
     * @return Authorization
     */
    public function withResponseMode(ResponseModeInterface $responseMode): Authorization
    {
        $clone = clone $this;
        $clone->responseMode = $responseMode;

        return $clone;
    }

    /**
     * @return null|ResponseModeInterface
     */
    public function getResponseMode(): ? ResponseModeInterface
    {
        return $this->responseMode;
    }

    /**
     * @param string $redirectUri
     *
     * @return Authorization
     */
    public function withRedirectUri(string $redirectUri): Authorization
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
     * @param UserAccountInterface $userAccount
     * @param bool                 $isFullyAuthenticated
     *
     * @return Authorization
     */
    public function withUserAccount(UserAccountInterface $userAccount, bool $isFullyAuthenticated): Authorization
    {
        $clone = clone $this;
        $clone->userAccount = $userAccount;
        $clone->userAccountFullyAuthenticated = $isFullyAuthenticated;

        return $clone;
    }

    /**
     * @return null|UserAccountInterface
     */
    public function getUserAccount(): ? UserAccountInterface
    {
        return $this->userAccount;
    }

    /**
     * @param string $responseParameter
     * @param mixed  $value
     *
     * @return Authorization
     */
    public function withResponseParameter(string $responseParameter, $value): Authorization
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
        Assertion::true($this->hasResponseParameter($param), sprintf('Invalid response parameter \'%s\'', $param));

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
    public function withResponseHeader(string $responseHeader, $value): Authorization
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
     * @param array $scope
     *
     * @return Authorization
     */
    public function withScopes(array $scope): Authorization
    {
        $clone = clone $this;
        $clone->scopes = $scope;

        return $clone;
    }

    /**
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param string $scope
     *
     * @return bool
     */
    public function hasScope(string $scope): bool
    {
        return null !== $this->scopes && in_array($scope, $this->scopes);
    }

    /**
     * @param string $scope
     *
     * @return Authorization
     */
    public function withoutScope(string $scope): Authorization
    {
        if (!$this->hasScope($scope)) {
            return $this;
        }
        $clone = clone $this;
        unset($clone->scopes[array_search($scope, $clone->scopes)]);

        return $clone;
    }

    /**
     * @param string $scope
     *
     * @return Authorization
     */
    public function addScope(string $scope): Authorization
    {
        if ($this->hasScope($scope)) {
            return $this;
        }
        $clone = clone $this;
        $clone->scopes[] = $scope;

        return $clone;
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
    public function allow(): Authorization
    {
        $clone = clone $this;
        $clone->authorized = true;

        return $clone;
    }

    /**
     * @return Authorization
     */
    public function deny(): Authorization
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
        Assertion::true($this->hasData($key), sprintf('Invalid data \'%s\'', $key));

        return $this->data[$key];
    }

    /**
     * @param string $key
     * @param mixed  $data
     *
     * @return Authorization
     */
    public function withData(string $key, $data): Authorization
    {
        $clone = clone $this;
        $clone->data[$key] = $data;

        return $clone;
    }

    /**
     * @return null|ResourceServerInterface
     */
    public function getResourceServer(): ? ResourceServerInterface
    {
        return $this->resourceServer;
    }

    /**
     * @param ResourceServerInterface $resourceServer
     *
     * @return Authorization
     */
    public function withResourceServer(ResourceServerInterface $resourceServer): Authorization
    {
        $clone = clone $this;
        $clone->resourceServer = $resourceServer;

        return $clone;
    }

    /**
     * @return bool
     */
    public function hasOfflineAccess(): bool
    {
        // The scope offline_access is not requested
        if (!in_array('offline_access', $this->getScopes())) {
            return false;
        }

        // The scope offline_access is requested but prompt is not consent
        // The scope offline_access is ignored
        if (!$this->hasQueryParam('prompt') || false === mb_strpos('consent', $this->getQueryParam('prompt'), 0, '8bit')) {
            return false;
        }

        return true;
    }

    /**
     * @param string $option
     * @param mixed  $value
     *
     * @return Authorization
     */
    public function withConsentScreenOption(string $option, $value): Authorization
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
    public function withoutConsentScreenOption(string $option): Authorization
    {
        if (!array_key_exists($option, $this->consentScreenOptions)) {
            return $this;
        }

        $clone = clone $this;
        unset($clone->consentScreenOptions[$option]);

        return $clone;
    }
}

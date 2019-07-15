<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
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
     * @var null|UserAccount
     */
    private $userAccount;

    /**
     * @var DataBag
     */
    private $metadata;

    /**
     * @var ResponseType]
     */
    private $responseType;

    /**
     * @var null|ResponseMode
     */
    private $responseMode;

    /**
     * @var array
     */
    private $queryParameters = [];

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
        Assertion::true($this->hasQueryParam($param), sprintf('The parameter "%s" is missing.', $param));

        return $this->queryParameters[$param];
    }

    public function getClient(): Client
    {
        return $this->client;
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

    public function getRedirectUri(): string
    {
        return $this->getQueryParam('redirect_uri');
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

        return explode(' ', $this->getQueryParam('prompt'));
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
        return $this->hasQueryParam('ui_locales') ? explode(' ', $this->getQueryParam('ui_locales')) : [];
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

    public function hasAttribute(string $key): bool
    {
        return \array_key_exists($key, $this->attributes);
    }

    /**
     * @param null|mixed$value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @return null|mixed $value
     */
    public function getAttribute(string $key)
    {
        Assertion::true($this->hasAttribute($key), sprintf('The attribute with key "%s" does not exist', $key));

        return $this->attributes[$key];
    }
}

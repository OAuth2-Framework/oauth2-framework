<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest;

use function array_key_exists;
use Assert\Assertion;
use function in_array;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class AuthorizationRequest
{
    public const CONSENT_NOT_GIVEN = 'consent_not_given';

    public const CONSENT_ALLOW = 'consent_allow';

    public const CONSENT_DENY = 'consent_deny';

    private string $authorized = self::CONSENT_NOT_GIVEN;

    private ?UserAccount $userAccount = null;

    private DataBag $metadata;

    private ?ResponseMode $responseMode = null;

    private array $queryParameters;

    private array $responseParameters = [];

    private array $responseHeaders = [];

    private ?ResourceServer $resourceServer = null;

    private array $attributes = [];

    public function __construct(
        private Client $client,
        array $queryParameters
    ) {
        $this->queryParameters = $queryParameters;
        $this->metadata = new DataBag([]);
    }

    public static function create(Client $client, array $queryParameters): static
    {
        return new self($client, $queryParameters);
    }

    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    public function hasQueryParam(string $param): bool
    {
        return array_key_exists($param, $this->queryParameters);
    }

    public function getQueryParam(string $param): mixed
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
        return $this->responseMode === null;
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

    public function setUserAccount(UserAccount $userAccount): static
    {
        $this->userAccount = $userAccount;

        return $this;
    }

    public function hasUserAccount(): bool
    {
        return $this->userAccount !== null;
    }

    public function getUserAccount(): UserAccount
    {
        Assertion::notNull($this->userAccount, 'internal_server_error');

        return $this->userAccount;
    }

    public function setResponseParameter(string $responseParameter, mixed $value): static
    {
        $this->responseParameters[$responseParameter] = $value;

        return $this;
    }

    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }

    public function getResponseParameter(string $param): mixed
    {
        Assertion::true($this->hasResponseParameter($param), sprintf('Invalid response parameter "%s".', $param));

        return $this->getResponseParameters()[$param];
    }

    public function hasResponseParameter(string $param): bool
    {
        return array_key_exists($param, $this->getResponseParameters());
    }

    public function setResponseHeader(string $responseHeader, mixed $value): static
    {
        $this->responseHeaders[$responseHeader] = $value;

        return $this;
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
        if (! $this->hasQueryParam('prompt')) {
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
        return in_array($prompt, $this->getPrompt(), true);
    }

    public function isAuthorized(): bool
    {
        return $this->authorized === self::CONSENT_ALLOW;
    }

    public function hasConsentBeenGiven(): bool
    {
        return $this->authorized !== self::CONSENT_NOT_GIVEN;
    }

    public function allow(): static
    {
        $this->authorized = self::CONSENT_ALLOW;

        return $this;
    }

    public function deny(): static
    {
        $this->authorized = self::CONSENT_DENY;

        return $this;
    }

    public function getResourceServer(): ?ResourceServer
    {
        return $this->resourceServer;
    }

    public function setResourceServer(ResourceServer $resourceServer): static
    {
        $this->resourceServer = $resourceServer;

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

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        Assertion::true($this->hasAttribute($key), sprintf('The attribute with key "%s" does not exist', $key));

        return $this->attributes[$key];
    }
}

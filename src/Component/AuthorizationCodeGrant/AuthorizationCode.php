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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCode extends Token
{
    /**
     * @var array
     */
    private $queryParameters;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var bool
     */
    private $used;

    public function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        parent::__construct($authorizationCodeId, $clientId, $userAccountId, $parameter, $metadata, $expiresAt, $resourceServerId);
        $this->queryParameters = $queryParameters;
        $this->redirectUri = $redirectUri;
        $this->used = false;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
    }

    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    public function getQueryParam(string $key)
    {
        if (!$this->hasQueryParam($key)) {
            throw new \RuntimeException(\Safe\sprintf('Query parameter with key "%s" does not exist.', $key));
        }

        return $this->queryParameters[$key];
    }

    public function hasQueryParam(string $key): bool
    {
        return \array_key_exists($key, $this->getQueryParams());
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->getTokenId()->getValue(),
        ];
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'auth_code_id' => $this->getTokenId()->getValue(),
            'query_parameters' => (object) $this->getQueryParameters(),
            'redirect_uri' => $this->getRedirectUri(),
            'is_used' => $this->isUsed(),
        ];

        return $data;
    }
}

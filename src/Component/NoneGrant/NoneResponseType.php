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

namespace OAuth2Framework\Component\NoneGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;

/**
 * This response type has been introduced by OpenID Connect
 * It stores the authorization to allow the access token issuance later on.
 * It returns nothing and only stores the authorization.
 *
 * At this time, this response type is not complete, because it always redirect the client.
 * But if no redirect URI is specified, no redirection should occurred as per OpenID Connect specification.
 *
 * @see http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#none
 */
class NoneResponseType implements ResponseType
{
    /**
     * @var AuthorizationStorage
     */
    private $authorizationStorage;

    /**
     * NoneResponseType constructor.
     *
     * @param AuthorizationStorage $authorizationStorage
     */
    public function __construct(AuthorizationStorage $authorizationStorage)
    {
        $this->authorizationStorage = $authorizationStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function associatedGrantTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'none';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_QUERY;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization): Authorization
    {
        $this->authorizationStorage->save($authorization);

        return $authorization;
    }
}

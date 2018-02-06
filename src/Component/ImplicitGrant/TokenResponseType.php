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

namespace OAuth2Framework\Component\ImplicitGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class TokenResponseType implements ResponseType
{
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * TokenResponseType constructor.
     *
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function associatedGrantTypes(): array
    {
        return ['implicit'];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization): Authorization
    {
        $accessToken = $this->accessTokenRepository->create(
            $authorization->getUserAccount()->getPublicId(),
            $authorization->getClient()->getPublicId(),
            DataBag::create($authorization->getTokenType()->getAdditionalInformation()),
            DataBag::create(['redirect_uri' => $authorization->getRedirectUri()]),
            null
        );
        $this->accessTokenRepository->save($accessToken);

        foreach ($accessToken->getResponseData() as $k => $v) {
            $authorization = $authorization->withResponseParameter($k, $v);
        }

        return $authorization;
    }
}

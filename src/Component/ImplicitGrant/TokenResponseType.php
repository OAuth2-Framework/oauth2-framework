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
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class TokenResponseType implements ResponseType
{
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var AccessTokenIdGenerator
     */
    private $accessTokenIdGenerator;

    /**
     * @var int
     */
    private $accessTokenLifetime;

    /**
     * TokenResponseType constructor.
     *
     * @param AccessTokenRepository  $accessTokenRepository
     * @param AccessTokenIdGenerator $accessTokenIdGenerator
     * @param int                    $accessTokenLifetime
     */
    public function __construct(AccessTokenRepository $accessTokenRepository, AccessTokenIdGenerator $accessTokenIdGenerator, int $accessTokenLifetime)
    {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->accessTokenIdGenerator = $accessTokenIdGenerator;
        $this->accessTokenLifetime = $accessTokenLifetime;
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
        $accessTokenId = $this->accessTokenIdGenerator->create(
            $authorization->getUserAccount()->getPublicId(),
            $authorization->getClient()->getPublicId(),
            DataBag::create($authorization->getTokenType()->getAdditionalInformation()),
            DataBag::create(['redirect_uri' => $authorization->getRedirectUri()]),
            null
        );
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            $authorization->getUserAccount()->getPublicId(),
            $authorization->getClient()->getPublicId(),
            DataBag::create($authorization->getTokenType()->getAdditionalInformation()),
            DataBag::create(['redirect_uri' => $authorization->getRedirectUri()]),
            new \DateTimeImmutable(sprintf('now +%d seconds', $this->accessTokenLifetime)),
            null
        );
        $this->accessTokenRepository->save($accessToken);

        foreach ($accessToken->getResponseData() as $k => $v) {
            $authorization = $authorization->withResponseParameter($k, $v);
        }

        return $authorization;
    }
}

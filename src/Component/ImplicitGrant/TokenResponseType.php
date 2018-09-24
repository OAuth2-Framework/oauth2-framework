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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class TokenResponseType implements ResponseType
{
    /**
     * @var AccessTokenIdGenerator
     */
    private $accessTokenIdGenerator;

    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var int
     */
    private $accessTokenLifetime;

    /**
     * TokenResponseType constructor.
     */
    public function __construct(AccessTokenIdGenerator $accessTokenIdGenerator, AccessTokenRepository $accessTokenRepository, int $accessTokenLifetime)
    {
        $this->accessTokenIdGenerator = $accessTokenIdGenerator;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->accessTokenLifetime = $accessTokenLifetime;
    }

    public function associatedGrantTypes(): array
    {
        return ['implicit'];
    }

    public function name(): string
    {
        return 'token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): AuthorizationRequest
    {
        return $authorization;
    }

    public function process(AuthorizationRequest $authorization): AuthorizationRequest
    {
        $additionalInformation = $authorization->getTokenType()->getAdditionalInformation();
        $accessTokenId = $this->accessTokenIdGenerator->createAccessTokenId(
            $authorization->getUserAccount()->getUserAccountId(),
            $authorization->getClient()->getClientId(),
            new DataBag($authorization->getTokenType()->getAdditionalInformation()),
            $authorization->getMetadata(),
            null
        );
        $accessToken = new AccessToken(
            $accessTokenId,
            $authorization->getClient()->getClientId(),
            $authorization->getUserAccount()->getUserAccountId(),
            new \DateTimeImmutable(\sprintf('now +%d seconds', $this->accessTokenLifetime)),
            new DataBag($additionalInformation),
            $authorization->getMetadata(),
            null
        );
        $this->accessTokenRepository->save($accessToken);

        foreach ($accessToken->getResponseData() as $k => $v) {
            $authorization->setResponseParameter($k, $v);
        }

        return $authorization;
    }
}

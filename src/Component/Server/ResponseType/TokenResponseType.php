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

namespace OAuth2Framework\Component\Server\ResponseType;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;

final class TokenResponseType implements ResponseTypeInterface
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * TokenResponseType constructor.
     *
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedGrantTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseType(): string
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
    public function process(Authorization $authorization, callable $next): Authorization
    {
        $accessToken = $this->accessTokenRepository->create(
            $authorization->getUserAccount()->getPublicId(),
            $authorization->getClient()->getPublicId(),
            DataBag::createFromArray($authorization->getTokenType()->getInformation()),
            DataBag::createFromArray(['redirect_uri' => $authorization->getRedirectUri()]),
            $authorization->getScopes(),
            null,
            null,
            null
        );
        $this->accessTokenRepository->save($accessToken);

        foreach ($accessToken->getResponseData() as $k => $v) {
            $authorization = $authorization->withResponseParameter($k, $v);
        }

        return $next($authorization);
    }
}

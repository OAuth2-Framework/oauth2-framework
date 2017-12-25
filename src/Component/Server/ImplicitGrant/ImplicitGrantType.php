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

namespace OAuth2Framework\Component\Server\ImplicitGrant;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class ImplicitGrantType implements ResponseType, GrantType
{
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
     *
     * @param AccessTokenRepository $accessTokenRepository
     * @param int                   $accessTokenLifetime
     */
    public function __construct(AccessTokenRepository $accessTokenRepository, int $accessTokenLifetime)
    {
        $this->accessTokenLifetime = $accessTokenLifetime;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedGrantTypes(): array
    {
        return ['implicit'];
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
            DataBag::create($authorization->getTokenType()->getInformation()),
            DataBag::create(['redirect_uri' => $authorization->getRedirectUri()]),
            $authorization->getScopes(),
            (new \DateTimeImmutable())->setTimestamp(time() + $this->accessTokenLifetime),
            null
        );
        $this->accessTokenRepository->save($accessToken);

        foreach ($accessToken->getResponseData() as $k => $v) {
            $authorization = $authorization->withResponseParameter($k, $v);
        }

        return $next($authorization);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedResponseTypes(): array
    {
        return ['token'];
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'implicit';
    }

    /**
     * {@inheritdoc}
     */
    public function checkTokenRequest(ServerRequestInterface $request)
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }
}

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

namespace OAuth2Framework\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenGrantType implements GrantType
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'refresh_token';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['refresh_token'];

        $diff = \array_diff($requiredParameters, \array_keys($parameters));
        if (!empty($diff)) {
            throw OAuth2Error::invalidRequest(\Safe\sprintf('Missing grant type parameter(s): %s.', \implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $refreshToken = $parameters['refresh_token'];
        $token = $this->refreshTokenRepository->find(new RefreshTokenId($refreshToken));

        if (null === $token) {
            throw OAuth2Error::invalidGrant('The parameter "refresh_token" is invalid.');
        }

        $client = $request->getAttribute('client');
        $this->checkRefreshToken($token, $client);

        $grantTypeData->setResourceOwnerId($token->getResourceOwnerId());
        foreach ($token->getMetadata() as $k => $v) {
            $grantTypeData->getMetadata()->set($k, $v);
        }
        foreach ($token->getParameter() as $k => $v) {
            $grantTypeData->getParameter()->set($k, $v);
        }
    }

    private function checkRefreshToken(RefreshToken $token, Client $client): void
    {
        if (true === $token->isRevoked() || $client->getPublicId()->getValue() !== $token->getClientId()->getValue()) {
            throw OAuth2Error::invalidGrant('The parameter "refresh_token" is invalid.');
        }

        if ($token->hasExpired()) {
            throw OAuth2Error::invalidGrant('The refresh token expired.');
        }
    }
}

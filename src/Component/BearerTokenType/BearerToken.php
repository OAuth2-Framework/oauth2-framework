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

namespace OAuth2Framework\Component\BearerTokenType;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use Psr\Http\Message\ServerRequestInterface;
use function Safe\sprintf;

final class BearerToken implements TokenType
{
    /**
     * @var TokenFinder[]
     */
    private $tokenFinders = [];

    /**
     * @var string
     */
    private $realm;

    public function __construct(string $realm)
    {
        $this->realm = $realm;
    }

    public function addTokenFinder(TokenFinder $tokenFinder): void
    {
        $this->tokenFinders[] = $tokenFinder;
    }

    public function name(): string
    {
        return 'Bearer';
    }

    public function getScheme(): string
    {
        return sprintf('%s realm="%s"', $this->name(), $this->realm);
    }

    public function getAdditionalInformation(): array
    {
        return [];
    }

    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        foreach ($this->tokenFinders as $finder) {
            $token = $finder->find($request, $additionalCredentialValues);
            if (null !== $token) {
                return $token;
            }
        }

        return null;
    }

    public function isRequestValid(AccessToken $token, ServerRequestInterface $request, array $additionalCredentialValues): bool
    {
        if (!$token->getParameter()->has('token_type')) {
            return false;
        }

        return $token->getParameter()->get('token_type') === $this->name();
    }
}

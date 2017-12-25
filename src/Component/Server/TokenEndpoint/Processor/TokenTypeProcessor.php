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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Processor;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\TokenType\TokenType;
use Psr\Http\Message\ServerRequestInterface;

final class TokenTypeProcessor
{
    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantType              $grantType
     * @param callable               $next
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    public function __invoke(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        /**
         * @var TokenType
         */
        $tokenType = $request->getAttribute('token_type');
        if (!$grantTypeData->getClient()->isTokenTypeAllowed($tokenType->name())) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2Exception::ERROR_INVALID_REQUEST,
                    'error_description' => sprintf('The token type "%s" is not allowed for the client.', $tokenType->name()),
                ]
            );
        }

        $info = $tokenType->getInformation();
        foreach ($info as $k => $v) {
            $grantTypeData = $grantTypeData->withParameter($k, $v);
        }

        return $next($request, $grantTypeData, $grantType);
    }
}

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

namespace OAuth2Framework\Component\Server\Endpoint\Token\Processor;

use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\GrantType\GrantTypeInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TokenTypeProcessor
{
    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantTypeInterface     $grantType
     * @param callable               $next
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    public function __invoke(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantTypeInterface $grantType, callable $next): GrantTypeData
    {
        /**
         * @var TokenTypeInterface
         */
        $tokenType = $request->getAttribute('token_type');
        if (!$grantTypeData->getClient()->isTokenTypeAllowed($tokenType->name())) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => sprintf('The token type \'%s\' is not allowed for the client.', $tokenType->name()),
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

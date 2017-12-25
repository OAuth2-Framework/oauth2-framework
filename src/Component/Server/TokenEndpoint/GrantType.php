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

namespace OAuth2Framework\Component\Server\TokenEndpoint;

use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

interface GrantType
{
    /**
     * This function returns the list of associated response types.
     *
     * @return string[]
     */
    public function getAssociatedResponseTypes(): array;

    /**
     * This function returns the supported grant type.
     *
     * @return string The grant type
     */
    public function getGrantType(): string;

    /**
     * This function checks the request.
     *
     * @param ServerRequestInterface $request The request
     *
     * @throws OAuth2Exception
     */
    public function checkTokenRequest(ServerRequestInterface $request);

    /**
     * This function checks the request and returns information to issue an access token.
     *
     * @param ServerRequestInterface $request           The request
     * @param GrantTypeData          $grantTypeData
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData;

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData;
}

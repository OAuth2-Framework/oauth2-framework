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

namespace OAuth2Framework\Component\TokenEndpoint;

use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ServerRequestInterface;

interface GrantType
{
    /**
     * This function returns the list of associated response types.
     *
     * @return string[]
     */
    public function associatedResponseTypes(): array;

    /**
     * This function returns the supported grant type.
     *
     * @return string The grant type
     */
    public function name(): string;

    /**
     * This function checks the request.
     *
     * @param ServerRequestInterface $request The request
     *
     * @throws OAuth2Message
     */
    public function checkRequest(ServerRequestInterface $request);

    /**
     * This function checks the request and returns information to issue an access token.
     *
     * @param ServerRequestInterface $request The request
     *
     * @throws OAuth2Message
     */
    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData;

    /**
     * @throws OAuth2Message
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData;
}

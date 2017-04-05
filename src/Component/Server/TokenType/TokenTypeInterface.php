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

namespace OAuth2Framework\Component\Server\TokenType;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use Psr\Http\Message\ServerRequestInterface;

interface TokenTypeInterface
{
    /**
     * This function prepares token type information to be added to the token returned to the client.
     * It must adds 'token_type' value and should add additional information (e.g. key materials in MAC context).
     * A possible result:
     *  {
     *      "token_type":"mac", //Added by this method
     *      "kid":"22BIjxU93h/IgwEb4zCRu5WF37s=", //Added by this method
     *      "mac_key":"adijq39jdlaska9asud", //Added by this method
     *      "mac_algorithm":"hmac-sha-256" //Added by this method
     *  }.
     *
     * Another possible result:
     *  {
     *      "token_type":"Bearer", //Added by this method
     *      "custom_data":"baz", //Added by this method or by access token
     *  }.
     *
     * @return array
     */
    public function getInformation(): array;

    /**
     * The name of the token type (e.g. Bearer, MAC).
     *
     * @return string
     */
    public function name(): string;

    /**
     * The scheme of the token type.
     * This information is sent on authentication reponses (HTTP code 401).
     *
     * @return string
     */
    public function getScheme(): string;

    /**
     * This method tries to find a token in the request.
     * If needed, additional credentials values can be set.
     *
     * @param ServerRequestInterface $request
     * @param array                  $additionalCredentialValues
     *
     * @return string|null
     */
    public function findToken(ServerRequestInterface $request, array &$additionalCredentialValues);

    /**
     * This methods verifies the request is valid with the specified access token.
     *
     * @param AccessToken            $accessToken
     * @param ServerRequestInterface $request
     * @param array                  $additionalCredentialValues
     *
     * @return bool
     */
    public function isTokenRequestValid(AccessToken $accessToken, ServerRequestInterface $request, array $additionalCredentialValues): bool;
}

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

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantType
{
    private $resourceOwnerPasswordCredentialManager;

    public function __construct(ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager)
    {
        $this->resourceOwnerPasswordCredentialManager = $resourceOwnerPasswordCredentialManager;
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'password';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['username', 'password'];

        $diff = \array_diff($requiredParameters, \array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, \Safe\sprintf('Missing grant type parameter(s): %s.', \implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $username = $parameters['username'];
        $password = $parameters['password'];

        $resourceOwnerId = $this->resourceOwnerPasswordCredentialManager->findResourceOwnerIdWithUsernameAndPassword($username, $password);
        if (!$resourceOwnerId) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_GRANT, 'Invalid username and password combination.');
        }

        $grantTypeData->setResourceOwnerId($resourceOwnerId);
    }
}

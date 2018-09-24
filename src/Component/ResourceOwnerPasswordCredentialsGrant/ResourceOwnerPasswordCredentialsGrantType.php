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

use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\UserAccount\UserAccountManager;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantType
{
    private $userAccountManager;
    private $userAccountRepository;

    public function __construct(UserAccountManager $userAccountManager, UserAccountRepository $userAccountRepository)
    {
        $this->userAccountManager = $userAccountManager;
        $this->userAccountRepository = $userAccountRepository;
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
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, \sprintf('Missing grant type parameter(s): %s.', \implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        // Nothing to do
        return $grantTypeData;
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $username = $parameters['username'];
        $password = $parameters['password'];

        $userAccount = $this->userAccountRepository->findOneByUsername($username);
        if (null === $userAccount || !$this->userAccountManager->isPasswordCredentialValid($userAccount, $password)) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'Invalid username and password combination.');
        }

        $grantTypeData->setResourceOwnerId($userAccount->getUserAccountId());

        return $grantTypeData;
    }
}

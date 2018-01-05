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

namespace OAuth2Framework\Component\Server\ResourceOwnerPasswordCredentialsGrant;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountManager;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantType
{
    /**
     * @var UserAccountManager
     */
    private $userAccountManager;

    /**
     * @var UserAccountRepository
     */
    private $userAccountRepository;

    /**
     * ResourceOwnerPasswordCredentialsGrantType constructor.
     *
     * @param UserAccountManager    $userAccountManager
     * @param UserAccountRepository $userAccountRepository
     */
    public function __construct(UserAccountManager $userAccountManager, UserAccountRepository $userAccountRepository)
    {
        $this->userAccountManager = $userAccountManager;
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedResponseTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function checkTokenRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['username', 'password'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        // Nothing to do
        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $parsedBody = $request->getParsedBody() ?? [];
        $username = $parsedBody['username'];
        $password = $parsedBody['password'];

        $userAccount = $this->userAccountRepository->findOneByUsername($username);
        if (null === $userAccount || !$this->userAccountManager->isPasswordCredentialValid($userAccount, $password)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'Invalid username and password combination.');
        }

        $grantTypeData = $grantTypeData->withResourceOwnerId($userAccount->getPublicId());

        return $grantTypeData;
    }
}

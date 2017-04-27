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

namespace OAuth2Framework\Component\Server\GrantType;

use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountManagerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantTypeInterface
{
    /**
     * @var bool
     */
    private $refreshTokenIssuanceAllowed;

    /**
     * @var bool
     */
    private $refreshTokenIssuanceForPublicClientsAllowed;

    /**
     * @var UserAccountManagerInterface
     */
    private $userAccountManager;

    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * ResourceOwnerPasswordCredentialsGrantType constructor.
     *
     * @param UserAccountManagerInterface    $userAccountManager
     * @param UserAccountRepositoryInterface $userAccountRepository
     * @param bool                           $refreshTokenIssuanceAllowed
     * @param bool                           $refreshTokenIssuanceForPublicClientsAllowed
     */
    public function __construct(UserAccountManagerInterface $userAccountManager, UserAccountRepositoryInterface $userAccountRepository, bool $refreshTokenIssuanceAllowed, bool $refreshTokenIssuanceForPublicClientsAllowed)
    {
        $this->userAccountManager = $userAccountManager;
        $this->userAccountRepository = $userAccountRepository;
        $this->refreshTokenIssuanceAllowed = $refreshTokenIssuanceAllowed;
        $this->refreshTokenIssuanceForPublicClientsAllowed = $refreshTokenIssuanceForPublicClientsAllowed;
    }

    /**
     * @return bool
     */
    public function isRefreshTokenIssuanceAllowed(): bool
    {
        return $this->refreshTokenIssuanceAllowed;
    }

    /**
     * @return bool
     */
    public function isRefreshTokenIssuanceForPublicClientsAllowed(): bool
    {
        return $this->refreshTokenIssuanceForPublicClientsAllowed;
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

    public function checkTokenRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['username', 'password'];

        foreach ($requiredParameters as $requiredParameter) {
            if (!array_key_exists($requiredParameter, $parameters)) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => sprintf('The parameter \'%s\' is missing.', $requiredParameter)]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        // Nothing to do
        return $grantTypeResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        $parsedBody = $request->getParsedBody() ?? [];
        $username = $parsedBody['username'];
        $password = $parsedBody['password'];

        $userAccount = $this->userAccountRepository->findOneByUsername($username);
        if (null === $userAccount || !$this->userAccountManager->isPasswordCredentialValid($userAccount, $password)) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT,
                    'error_description' => 'Invalid username and password combination.',
                ]
            );
        }

        $grantTypeResponse = $grantTypeResponse->withResourceOwnerId($userAccount->getPublicId());
        if ($this->issueRefreshToken($grantTypeResponse->getClient())) {
            $grantTypeResponse = $grantTypeResponse->withRefreshToken();
        } else {
            $grantTypeResponse = $grantTypeResponse->withRefreshToken();
        }

        return $grantTypeResponse;
    }

    /**
     * @param Client $client
     *
     * @return bool
     */
    private function issueRefreshToken(Client $client): bool
    {
        if (!$this->isRefreshTokenIssuanceAllowed()) {
            return false;
        }

        if (true === $client->isPublic()) {
            return $this->isRefreshTokenIssuanceForPublicClientsAllowed();
        }

        return true;
    }
}

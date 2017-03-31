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

namespace OAuth2Framework\Bundle\Server\Security\Authentication\Provider;

use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuth2Provider implements AuthenticationProviderInterface
{
    /**
     * @var UserCheckerInterface
     */
    private $userChecker;

    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * OAuth2Provider constructor.
     *
     * @param UserCheckerInterface           $userChecker
     * @param TokenTypeManager               $tokenTypeManager
     * @param ClientRepositoryInterface      $clientRepository
     * @param UserAccountRepositoryInterface $userAccountRepository
     */
    public function __construct(UserCheckerInterface $userChecker, TokenTypeManager $tokenTypeManager, ClientRepositoryInterface $clientRepository, UserAccountRepositoryInterface $userAccountRepository)
    {
        $this->userChecker = $userChecker;
        $this->tokenTypeManager = $tokenTypeManager;
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        /**
         * @var \OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token
         */
        $accessToken = $token->getAccessToken();

        if (true === $accessToken->hasExpired()) {
            throw new BadCredentialsException('The Access Token has expired.');
        }

        try {
            $resourceOwner = $this->getResourceOwner($accessToken);
            $this->checkResourceOwner($resourceOwner);
            $token->setResourceOwner($resourceOwner);
            $client = $this->getClient($accessToken);
            $token->setClient($client);
            $token->setAuthenticated(true);

            return $token;
        } catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuth2Token;
    }

    /**
     * @param ResourceOwnerInterface $resourceOwner
     */
    private function checkResourceOwner(ResourceOwnerInterface $resourceOwner)
    {
        if ($resourceOwner instanceof UserInterface) {
            try {
                $this->userChecker->checkPostAuth($resourceOwner);
            } catch (AccountStatusException $e) {
                throw new BadCredentialsException($e->getMessage());
            }
        }
    }

    /**
     * @param AccessToken $accessToken
     *
     * @return Client
     */
    private function getClient(AccessToken $accessToken)
    {
        $client = $this->clientRepository->find($accessToken->getClientId());
        if (null === $client) {
            throw new BadCredentialsException('Unknown client');
        }

        return $client;
    }

    /**
     * @param AccessToken $accessToken
     *
     * @return ResourceOwnerInterface
     */
    private function getResourceOwner(AccessToken $accessToken): ResourceOwnerInterface
    {
        $resourceOwner = $accessToken->getResourceOwnerId();
        if ($resourceOwner instanceof UserAccountId) {
            return $this->userAccountRepository->findUserAccount($resourceOwner);
        } elseif ($resourceOwner instanceof ClientId) {
            return $this->clientRepository->find($resourceOwner);
        } else {
            throw new BadCredentialsException('Unknown resource owner');
        }
    }
}

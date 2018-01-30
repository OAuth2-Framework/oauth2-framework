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

namespace OAuth2Framework\Bundle\Service;

use OAuth2Framework\Component\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Endpoint\Authorization\Exception\RedirectToLoginPageException;
use OAuth2Framework\Component\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryInterface;
use OAuth2Framework\Component\Model\UserAccount\UserAccountInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SymfonyUserDiscovery implements UserAccountDiscoveryInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * SymfonyUserDiscovery constructor.
     *
     * @param TokenStorageInterface         $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     * @param callable               $next
     *
     * @return Authorization
     */
    public function find(ServerRequestInterface $request, Authorization $authorization, callable $next): Authorization
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            $userAccount = $token->getUser();
            if ($userAccount instanceof UserAccountInterface) {
                $this->checkUserAccount($userAccount, $authorization);
                $isFullyAuthenticated = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY');
                if (true === $authorization->isUserAccountFullyAuthenticated()) {
                    $isFullyAuthenticated = true;
                }
                $authorization = $authorization->withUserAccount($userAccount, $isFullyAuthenticated);
            }
        }

        return $next($request, $authorization);
    }

    /**
     * @param UserAccountInterface $userAccount
     * @param Authorization        $authorization
     *
     * @throws RedirectToLoginPageException
     */
    private function checkUserAccount(UserAccountInterface $userAccount, Authorization $authorization)
    {
        if (null !== $authorization->getUserAccount() && $userAccount->getPublicId()->getValue() !== $authorization->getUserAccount()->getPublicId()->getValue()) {
            throw new RedirectToLoginPageException($authorization);
        }
    }
}

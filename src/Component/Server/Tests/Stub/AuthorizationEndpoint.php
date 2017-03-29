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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use Assert\Assertion;
use Interop\Http\Factory\ResponseFactoryInterface;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen\AfterConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationEndpoint as Base;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationFactory;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen\BeforeConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\ProcessAuthorizationException;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthorizationEndpoint extends Base
{
    /**
     * @var null|bool
     */
    private $isAuthorized = null;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * AuthorizationEndpoint constructor.
     *
     * @param ResponseFactoryInterface    $responseFactory
     * @param AuthorizationFactory        $authorizationFactory
     * @param UserAccountDiscoveryManager $userAccountDiscoveryManager
     * @param BeforeConsentScreenManager  $beforeConsentScreenManager
     * @param AfterConsentScreenManager   $afterConsentScreenManager
     */
    public function __construct(ResponseFactoryInterface $responseFactory, AuthorizationFactory $authorizationFactory, UserAccountDiscoveryManager $userAccountDiscoveryManager, BeforeConsentScreenManager $beforeConsentScreenManager, AfterConsentScreenManager $afterConsentScreenManager)
    {
        $this->responseFactory = $responseFactory;
        parent::__construct($authorizationFactory, $userAccountDiscoveryManager, $beforeConsentScreenManager, $afterConsentScreenManager);
    }

    /**
     * @param bool|null $isAuthorized
     */
    public function setAuthorized($isAuthorized)
    {
        Assertion::nullOrBoolean($isAuthorized);
        $this->isAuthorized = $isAuthorized;
    }

    /**
     * {@inheritdoc}
     */
    protected function redirectToLoginPage(Authorization $authorization, ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(302);
        $response->getBody()->write('You are redirected to the login page');

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function processConsentScreen(ServerRequestInterface $request, Authorization $authorization): ResponseInterface
    {
        if (is_bool($this->isAuthorized)) {
            if (true === $this->isAuthorized) {
                $authorization = $authorization->allow();
            } else {
                $authorization = $authorization->deny();
            }
            throw new ProcessAuthorizationException($authorization);
        }

        $response = $this->responseFactory->createResponse();

        $message = in_array('fr', $authorization->getUiLocales()) ? 'Vous êtes sur la page de consentement' : 'You are on the consent screen';
        $response->getBody()->write($message);

        return $response;
    }
}

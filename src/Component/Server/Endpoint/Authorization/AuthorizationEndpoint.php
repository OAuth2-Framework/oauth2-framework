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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen\AfterConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen\BeforeConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryManager;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AuthorizationEndpoint implements MiddlewareInterface
{
    /**
     * @var UserAccountDiscoveryManager
     */
    private $userAccountDiscoveryManager;

    /**
     * @var BeforeConsentScreenManager
     */
    private $beforeConsentScreenManager;

    /**
     * @var AfterConsentScreenManager
     */
    private $afterConsentScreenManager;

    /**
     * @var AuthorizationFactory
     */
    private $authorizationFactory;

    /**
     * AuthorizationEndpoint constructor.
     *
     * @param AuthorizationFactory        $authorizationFactory
     * @param UserAccountDiscoveryManager $userAccountDiscoveryManager
     * @param BeforeConsentScreenManager  $beforeConsentScreenManager
     * @param AfterConsentScreenManager   $afterConsentScreenManager
     */
    public function __construct(AuthorizationFactory $authorizationFactory, UserAccountDiscoveryManager $userAccountDiscoveryManager, BeforeConsentScreenManager $beforeConsentScreenManager, AfterConsentScreenManager $afterConsentScreenManager)
    {
        $this->authorizationFactory = $authorizationFactory;
        $this->userAccountDiscoveryManager = $userAccountDiscoveryManager;
        $this->beforeConsentScreenManager = $beforeConsentScreenManager;
        $this->afterConsentScreenManager = $afterConsentScreenManager;
    }

    /**
     * @param Authorization          $authorization
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    abstract protected function redirectToLoginPage(Authorization $authorization, ServerRequestInterface $request): ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return ResponseInterface
     */
    abstract protected function processConsentScreen(ServerRequestInterface $request, Authorization $authorization): ResponseInterface;

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            $authorization = $this->authorizationFactory->createAuthorizationFromRequest($request);
            $authorization = $this->userAccountDiscoveryManager->find($request, $authorization);

            if (null === $authorization->getUserAccount()) {
                return $this->redirectToLoginPage($authorization, $request);
            }

            $authorization = $this->beforeConsentScreenManager->process($request, $authorization);

            return $this->processConsentScreen($request, $authorization);
        } catch (OAuth2Exception $e) {
            $data = $e->getData();
            if (array_key_exists('authorization', $data)) {
                $authorization = $data['authorization'];
                unset($data['authorization']);
                $redirectUri = $authorization->getRedirectUri();
                $responseMode = $authorization->getResponseMode();
                if (null !== $redirectUri && null !== $responseMode) {
                    $data['redirect_uri'] = $redirectUri;
                    $data['response_mode'] = $responseMode;
                    throw new OAuth2Exception(302, $data, $e);
                }
            }
            throw $e;
        } catch (Exception\ProcessAuthorizationException $e) {
            $authorization = $e->getAuthorization();
            $authorization = $this->afterConsentScreenManager->process($request, $authorization);
            if ($authorization->isAuthorized() === false) {
                $this->throwRedirectionException($authorization, OAuth2ResponseFactoryManager::ERROR_ACCESS_DENIED, 'The resource owner denied access to your client.');
            }

            $responseTypeProcessor = ResponseTypeProcessor::create($authorization);

            try {
                $authorization = $responseTypeProcessor->process();
            } catch (OAuth2Exception $e) {
                $this->throwRedirectionException($authorization, $e->getData()['error'], $e->getData()['error_description']);
            }

            return $this->buildResponse($authorization);
        } catch (Exception\CreateRedirectionException $e) {
            $this->throwRedirectionException($e->getAuthorization(), $e->getMessage(), $e->getDescription());
        } catch (Exception\ShowConsentScreenException $e) {
            return $this->processConsentScreen($request, $e->getAuthorization());
        } catch (Exception\RedirectToLoginPageException $e) {
            return $this->redirectToLoginPage($e->getAuthorization(), $request);
        }
    }

    /**
     * @param Authorization $authorization
     *
     * @throws OAuth2Exception
     *
     * @return ResponseInterface
     */
    private function buildResponse(Authorization $authorization): ResponseInterface
    {
        if (null === $authorization->getResponseMode() || null === $authorization->getRedirectUri()) {
            throw new OAuth2Exception(400, ['error' => 'EEE', 'error_description' => 'FFF']);
        }

        $response = $authorization->getResponseMode()->buildResponse(
            $authorization->getRedirectUri(),
            $authorization->getResponseParameters()
        );
        foreach ($authorization->getResponseHeaders() as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    /**
     * @param Authorization $authorization
     * @param string        $error
     * @param string        $error_description
     *
     * @throws OAuth2Exception
     */
    private function throwRedirectionException(Authorization $authorization, string $error, string $error_description)
    {
        $params = [
            'error' => $error,
            'error_description' => $error_description,
        ];
        $params += $authorization->getResponseParameters();
        if (null === $authorization->getResponseMode() || null === $authorization->getRedirectUri()) {
            throw new OAuth2Exception(400, $params);
        }
        $params += [
            'response_mode' => $authorization->getResponseMode(),
            'redirect_uri' => $authorization->getRedirectUri(),
        ];

        throw new OAuth2Exception(302, $params);
    }
}

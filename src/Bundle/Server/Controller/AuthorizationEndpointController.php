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

namespace OAuth2Framework\Bundle\Server\Controller;

use Interop\Http\Factory\ResponseFactoryInterface;
use OAuth2Framework\Bundle\Server\Form\FormFactory;
use OAuth2Framework\Bundle\Server\Form\Handler\AuthorizationFormHandler;
use OAuth2Framework\Bundle\Server\Form\Model\AuthorizationModel;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen\AfterConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationEndpoint;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationFactory;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen\BeforeConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\ProcessAuthorizationException;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class AuthorizationEndpointController extends AuthorizationEndpoint
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $loginRoute;

    /**
     * @var array
     */
    private $loginRouteParams;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var AuthorizationFormHandler
     */
    private $formHandler;

    /**
     * @var string
     */
    private $template;

    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * AuthorizationEndpointController constructor.
     *
     * @param EngineInterface             $templateEngine
     * @param string                      $template
     * @param FormFactory                 $formFactory
     * @param AuthorizationFormHandler    $formHandler
     * @param TranslatorInterface         $translator
     * @param RouterInterface             $router
     * @param string                      $loginRoute
     * @param array                       $loginRouteParams
     * @param ResponseFactoryInterface    $responseFactory
     * @param SessionInterface            $session
     * @param AuthorizationFactory        $authorizationFactory
     * @param UserAccountDiscoveryManager $userAccountDiscoveryManager
     * @param BeforeConsentScreenManager  $beforeConsentScreenManager
     * @param AfterConsentScreenManager   $afterConsentScreenManager
     */
    public function __construct(EngineInterface $templateEngine, string $template, FormFactory $formFactory, AuthorizationFormHandler $formHandler, TranslatorInterface $translator, RouterInterface $router, string $loginRoute, array $loginRouteParams, ResponseFactoryInterface $responseFactory, SessionInterface $session, AuthorizationFactory $authorizationFactory, UserAccountDiscoveryManager $userAccountDiscoveryManager, BeforeConsentScreenManager $beforeConsentScreenManager, AfterConsentScreenManager $afterConsentScreenManager)
    {
        parent::__construct($authorizationFactory, $userAccountDiscoveryManager, $beforeConsentScreenManager, $afterConsentScreenManager);

        $this->session = $session;
        $this->responseFactory = $responseFactory;
        $this->router = $router;
        $this->loginRoute = $loginRoute;
        $this->loginRouteParams = $loginRouteParams;
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->formHandler = $formHandler;
        $this->template = $template;
        $this->templateEngine = $templateEngine;

        //$this->allowScopeSelection = $allowScopeSelection;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface $response
     */
    /*public function authorizationAction(ServerRequestInterface $request)
    {
        if ($this->session->has('oauth2_authorization_request_data')) {
            $this->session->remove('oauth2_authorization_request_data');
        }
        $response = new Response();
        $this->authorize($request, $response);

        return $response;
    }*/

    /**
     * {@inheritdoc}
     */
    protected function redirectToLoginPage(Authorization $authorization, ServerRequestInterface $request): ResponseInterface
    {
        $session_data = [
            'uri' => $request->getUri()->__toString(),
            'ui_locale' => $this->getUiLocale($authorization),
        ];
        foreach (['display', 'id_token_hint', 'login_hint', 'acr_values'] as $key) {
            $session_data[$key] = $authorization->hasQueryParam($key) ? $authorization->getQueryParam($key) : null;
        }

        $this->session->set('oauth2_authorization_request_data', $session_data);
        $response = $this->responseFactory->createResponse(302);
        $response = $response->withHeader('Location', $this->router->generate($this->loginRoute, $this->loginRouteParams, UrlGeneratorInterface::ABSOLUTE_URL));

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @throws ProcessAuthorizationException
     *
     * @return ResponseInterface
     */
    protected function processConsentScreen(ServerRequestInterface $request, Authorization $authorization): ResponseInterface
    {
        //$options = $this->processConsentScreenOptions($authorization);
        $ui_locale = $this->getUiLocale($authorization);
        $options = array_merge(
            //$options,
            [
                'locale' => $ui_locale,
                'scopes' => $authorization->getScopes(),
                //'allowScopeSelection' => $this->allowScopeSelection,
            ]
        );
        $authorization_model = new AuthorizationModel();
        $authorization_model->setScopes($authorization->getScopes());
        $form = $this->formFactory->createForm($options, $authorization_model);

        if ('POST' === $request->getMethod()) {
            $authorization = $this->formHandler->handle($form, $request, $authorization, $authorization_model);

            if (is_bool($authorization->isAuthorized())) {
                throw new ProcessAuthorizationException($authorization);
                /*return [
                    'save_authorization' => $authorization_model->isSaveConfiguration(),
                ];*/
            }
        }

        return $this->prepareResponse($authorization, $form, $ui_locale);
    }

    /**
     * @param Authorization $authorization
     * @param FormInterface $form
     * @param string|null   $ui_locale
     *
     * @return ResponseInterface
     */
    private function prepareResponse(Authorization $authorization, FormInterface $form, string $ui_locale = null): ResponseInterface
    {
        $content = $this->templateEngine->render(
            $this->template,
            [
                'form' => $form->createView(),
                'authorization' => $authorization,
                'ui_locale' => $ui_locale,
                //'is_pre_configured_authorization_enabled' => true,
            ]
        );

        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @param Authorization $authorization
     *
     * @return null|string
     */
    private function getUiLocale(Authorization $authorization)
    {
        if (!method_exists($this->translator, 'getCatalogue') || !$authorization->hasQueryParam('ui_locales')) {
            return null;
        }

        foreach ($authorization->getUiLocales() as $locale) {
            $catalogue = $this->translator->getCatalogue($locale);
            if (in_array('OAuth2FrameworkServer', $catalogue->getDomains())) {
                return $locale;
            }
        }
    }
}

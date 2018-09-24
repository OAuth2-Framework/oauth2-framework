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

namespace OAuth2Framework\ServerBundle\Controller;

use Http\Message\MessageFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\ProcessAuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountDiscovery;
use OAuth2Framework\ServerBundle\Form\FormFactory;
use OAuth2Framework\ServerBundle\Form\Handler\AuthorizationFormHandler;
use OAuth2Framework\ServerBundle\Form\Model\AuthorizationModel;
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
     */
    public function __construct(EngineInterface $templateEngine, string $template, FormFactory $formFactory, AuthorizationFormHandler $formHandler, TranslatorInterface $translator, RouterInterface $router, string $loginRoute, array $loginRouteParams, MessageFactory $messageFactory, SessionInterface $session, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, UserAccountCheckerManager $userAccountCheckerManager, ExtensionManager $consentScreenExtensionManager)
    {
        parent::__construct($messageFactory, $authorizationRequestLoader, $parameterCheckerManager, $userAccountDiscovery, $userAccountCheckerManager, $consentScreenExtensionManager);

        $this->session = $session;
        $this->messageFactory = $messageFactory;
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

    protected function redirectToLoginPage(ServerRequestInterface $request, AuthorizationRequest $authorization): ResponseInterface
    {
        $session_data = [
            'uri' => $request->getUri()->__toString(),
        ];
        foreach (['display', 'id_token_hint', 'login_hint', 'acr_values'] as $key) {
            $session_data[$key] = $authorization->hasQueryParam($key) ? $authorization->getQueryParam($key) : null;
        }

        if ($locale = $this->getUiLocale($authorization)) {
            $this->session->set('_locale', $locale);
        }
        $this->session->set('oauth2_authorization_request_data', $session_data);
        $response = $this->messageFactory->createResponse(302);
        $response = $response->withHeader('Location', $this->router->generate($this->loginRoute, $this->loginRouteParams, UrlGeneratorInterface::ABSOLUTE_URL));

        return $response;
    }

    protected function processConsentScreen(ServerRequestInterface $request, AuthorizationRequest $authorization): ResponseInterface
    {
        //FIXME: $options = $this->processConsentScreenOptions($authorization);
        if ($locale = $this->getUiLocale($authorization)) {
            $this->session->set('_locale', $locale);
        }
        $options = \array_merge(
            //FIXME: $options,
            [
                //'scopes' => $authorization->getScopes(),
                //FIXME: 'allowScopeSelection' => $this->allowScopeSelection,
            ]
        );
        $authorization_model = new AuthorizationModel();
        //$authorization_model->setScopes($authorization->getScopes());
        $form = $this->formFactory->createForm($options, $authorization_model);
        $this->session->remove('oauth2_authorization_request_data');

        if ('POST' === $request->getMethod()) {
            $authorization = $this->formHandler->handle($form, $request, $authorization, $authorization_model);

            if (\is_bool($authorization->isAuthorized())) {
                throw new ProcessAuthorizationException($authorization);
                //FIXME
                /*return [
                    'save_authorization' => $authorization_model->isSaveConfiguration(),
                ];*/
            }
        }

        return $this->prepareResponse($authorization, $form);
    }

    private function prepareResponse(AuthorizationRequest $authorization, FormInterface $form): ResponseInterface
    {
        $content = $this->templateEngine->render(
            $this->template,
            [
                'form' => $form->createView(),
                'authorization' => $authorization,
                //FIXME: 'is_pre_configured_authorization_enabled' => true,
            ]
        );

        $response = $this->messageFactory->createResponse(200);
        $response->getBody()->write($content);

        return $response;
    }

    private function getUiLocale(AuthorizationRequest $authorization): ?string
    {
        if (!\method_exists($this->translator, 'getCatalogue') || !$authorization->hasQueryParam('ui_locales')) {
            return null;
        }

        foreach ($authorization->getUiLocales() as $locale) {
            $catalogue = $this->translator->getCatalogue($locale);
            if (\in_array('OAuth2FrameworkServer', $catalogue->getDomains(), true)) {
                return $locale;
            }
        }

        return null;
    }
}

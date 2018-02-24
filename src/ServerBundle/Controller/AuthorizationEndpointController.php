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
use OAuth2Framework\ServerBundle\Form\FormFactory;
use OAuth2Framework\ServerBundle\Form\Handler\AuthorizationFormHandler;
use OAuth2Framework\ServerBundle\Form\Model\AuthorizationModel;
use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentScreen\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\ProcessAuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccountDiscovery\UserAccountDiscoveryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AuthorizationEndpointController extends AuthorizationEndpoint
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

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
     * @param MessageFactory              $messageFactory
     * @param SessionInterface            $session
     * @param AuthorizationFactory        $authorizationFactory
     * @param UserAccountDiscoveryManager $userAccountDiscoveryManager
     * @param ExtensionManager            $consentScreenExtensionManager
     */
    public function __construct(EngineInterface $templateEngine, string $template, FormFactory $formFactory, AuthorizationFormHandler $formHandler, TranslatorInterface $translator, RouterInterface $router, string $loginRoute, array $loginRouteParams, MessageFactory $messageFactory, SessionInterface $session, AuthorizationFactory $authorizationFactory, UserAccountDiscoveryManager $userAccountDiscoveryManager, ExtensionManager $consentScreenExtensionManager)
    {
        parent::__construct($authorizationFactory, $userAccountDiscoveryManager, $consentScreenExtensionManager);

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
        $response = $this->messageFactory->createResponse(302);
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
        //FIXME: $options = $this->processConsentScreenOptions($authorization);
        $ui_locale = $this->getUiLocale($authorization);
        $options = array_merge(
            //FIXME: $options,
            [
                'locale' => $ui_locale,
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

            if (is_bool($authorization->isAuthorized())) {
                throw new ProcessAuthorizationException($authorization);
                //FIXME
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
                //FIXME: 'is_pre_configured_authorization_enabled' => true,
            ]
        );

        $response = $this->messageFactory->createResponse(200);
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

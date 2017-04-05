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

namespace OAuth2Framework\Bundle\Server\Form\Handler;

use OAuth2Framework\Bundle\Server\Form\Model\AuthorizationModel;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormInterface;

class AuthorizationFormHandler
{
    /**
     * @param FormInterface          $form
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     * @param AuthorizationModel     $authorization_model
     *
     * @throws OAuth2Exception
     *
     * @return Authorization
     */
    public function handle(FormInterface $form, ServerRequestInterface $request, Authorization $authorization, AuthorizationModel $authorization_model): Authorization
    {
        if ('POST' !== $request->getMethod()) {
            throw new OAuth2Exception(
                405,
                []
            );
        }

        $httpFoundationFactory = new HttpFoundationFactory();
        $symfonyRequest = $httpFoundationFactory->createRequest($request);

        $form->submit($symfonyRequest->get($form->getName()));
        if (!$form->isValid()) {
            return $authorization;
        }

        $button = $form->get('accept');
        if (!$button instanceof ClickableInterface) {
            throw new InvalidArgumentException('Unable to find the button named "accept".');
        }
        if (true === $button->isClicked()) {
            $authorization = $authorization->allow();
        } else {
            $authorization = $authorization->deny();
        }
        $refused_scopes = array_diff(
            $authorization->getScopes(),
            $authorization_model->getScopes()
        );
        foreach ($refused_scopes as $refused_scope) {
            $authorization = $authorization->withoutScope($refused_scope);
        }

        return $authorization;
    }
}

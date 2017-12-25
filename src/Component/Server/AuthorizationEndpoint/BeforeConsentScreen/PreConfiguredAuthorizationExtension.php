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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\BeforeConsentScreen;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Exception\ProcessAuthorizationException;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Exception\ShowConsentScreenException;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\PreConfiguredAuthorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\PreConfiguredAuthorizationRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class PreConfiguredAuthorizationExtension implements BeforeConsentScreen
{
    /**
     * @var PreConfiguredAuthorizationRepository
     */
    private $preConfiguredAuthorizationRepository;

    /**
     * PreConfiguredAuthorizationExtension constructor.
     *
     * @param PreConfiguredAuthorizationRepository $preConfiguredAuthorizationRepository
     */
    public function __construct(PreConfiguredAuthorizationRepository $preConfiguredAuthorizationRepository)
    {
        $this->preConfiguredAuthorizationRepository = $preConfiguredAuthorizationRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        $preConfiguredAuthorization = $this->findPreConfiguredAuthorization($authorization);

        if (null !== $preConfiguredAuthorization) {
            if ($authorization->hasPrompt('consent')) {
                throw new ShowConsentScreenException($authorization);
            }

            $authorization = $authorization->allow();

            throw new ProcessAuthorizationException($authorization);
        } else {
            if ($authorization->hasPrompt('none')) {
                throw new OAuth2Exception(400, ['error' => OAuth2Exception::ERROR_INTERACTION_REQUIRED, 'error_description' => 'The resource owner interaction is required.'], $authorization);
            }
        }

        return $authorization;
    }

    /**
     * @param \OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization $authorization
     *
     * @return null|PreConfiguredAuthorization
     */
    private function findPreConfiguredAuthorization(Authorization $authorization)
    {
        if (null !== $this->preConfiguredAuthorizationRepository) {
            return $this->preConfiguredAuthorizationRepository->find(
                $authorization->getUserAccount()->getPublicId(),
                $authorization->getClient()->getPublicId(),
                $authorization->getScopes(),
                $authorization->getResourceServer() ? $authorization->getResourceServer()->getResourceServerId() : null
            );
        }
    }
}

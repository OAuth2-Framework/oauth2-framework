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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\ProcessAuthorizationException;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\ShowConsentScreenException;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorization;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

class PreConfiguredAuthorizationExtension implements BeforeConsentScreenInterface
{
    /**
     * @var PreConfiguredAuthorizationRepositoryInterface
     */
    private $preConfiguredAuthorizationRepository;

    /**
     * PreConfiguredAuthorizationExtension constructor.
     *
     * @param PreConfiguredAuthorizationRepositoryInterface $preConfiguredAuthorizationRepository
     */
    public function __construct(PreConfiguredAuthorizationRepositoryInterface $preConfiguredAuthorizationRepository)
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
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INTERACTION_REQUIRED, 'error_description' => 'The resource owner interaction is required.', 'authorization' => $authorization]);
            }
        }

        return $authorization;
    }

    /**
     * @param \OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization $authorization
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

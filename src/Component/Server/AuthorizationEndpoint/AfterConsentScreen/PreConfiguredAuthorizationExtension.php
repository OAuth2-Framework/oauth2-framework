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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\AfterConsentScreen;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\PreConfiguredAuthorizationRepository;
use Psr\Http\Message\ServerRequestInterface;

final class PreConfiguredAuthorizationExtension implements AfterConsentScreen
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
        /*if (!array_key_exists('save_authorization', $form_data) || true !== $form_data['save_authorization']) {
            return;
        }

        $configuration = $this->preConfiguredAuthorizationRepository->create(
            $authorization->getUserAccount()->getPublicId(),
            $authorization->getClient()->getPublicId(),
            $authorization->getScopes()
        );
        $this->preConfiguredAuthorizationRepository->save($configuration);*/

        return $authorization;
    }
}

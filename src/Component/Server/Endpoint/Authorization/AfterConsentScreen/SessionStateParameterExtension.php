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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use Psr\Http\Message\ServerRequestInterface;

abstract class SessionStateParameterExtension implements AfterConsentScreenInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        if ($authorization->hasScope('openid')) {
            $browserState = $this->getBrowserState($request, $authorization);
            $sessionState = $this->calculateSessionState($request, $authorization, $browserState);
            $authorization = $authorization->withResponseParameter('session_state', $sessionState);
        }

        return $authorization;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return string
     */
    abstract protected function getBrowserState(ServerRequestInterface $request, Authorization &$authorization): string;

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     * @param string                 $browserState
     *
     * @return string
     */
    abstract protected function calculateSessionState(ServerRequestInterface $request, Authorization $authorization, string $browserState): string;
}

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
use Psr\Http\Message\ServerRequestInterface;

final class BeforeConsentScreenManager
{
    /**
     * @var BeforeConsentScreen[]
     */
    private $extensions = [];

    /**
     * @param BeforeConsentScreen $extension
     */
    public function add(BeforeConsentScreen $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return Authorization
     */
    public function process(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        foreach ($this->extensions as $extension) {
            $authorization = $extension->process($request, $authorization);
        }

        return $authorization;
    }
}
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
use Psr\Http\Message\ServerRequestInterface;

final class BeforeConsentScreenManager
{
    /**
     * @var BeforeConsentScreenInterface[]
     */
    private $extensions = [];

    /**
     * @param BeforeConsentScreenInterface $extension
     */
    public function add(BeforeConsentScreenInterface $extension)
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

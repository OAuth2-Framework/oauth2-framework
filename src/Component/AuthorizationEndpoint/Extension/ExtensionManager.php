<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Extension;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;

class ExtensionManager
{
    /**
     * @var Extension[]
     */
    private array $extensions = [];

    public function add(Extension $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function process(ServerRequestInterface $request, AuthorizationRequest $authorization): void
    {
        foreach ($this->extensions as $extension) {
            $extension->process($request, $authorization);
        }
    }
}

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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\UserAccountDiscovery;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use Psr\Http\Message\ServerRequestInterface;

final class UserAccountDiscoveryManager
{
    /**
     * @var UserAccountDiscovery[]
     */
    private $extensions = [];

    /**
     * @param UserAccountDiscovery $extension
     */
    public function add(UserAccountDiscovery $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return Authorization
     */
    public function find(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        foreach ($this->extensions as $extension) {
            $authorization = $extension->find($request, $authorization);
        }

        return $authorization;
    }
}

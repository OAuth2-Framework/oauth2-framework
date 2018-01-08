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

namespace OAuth2Framework\Component\Server\NoneGrant;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

/**
 * Interface AuthorizationRepository.
 */
interface AuthorizationStorage
{
    /**
     * @param Authorization $authorization
     * @return mixed
     */
    public function save(Authorization $authorization);
}

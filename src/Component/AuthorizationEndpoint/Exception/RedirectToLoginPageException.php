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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Exception;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;

class RedirectToLoginPageException extends \Exception
{
    private $authorization;

    public function __construct(Authorization $authorization)
    {
        parent::__construct();
        $this->authorization = $authorization;
    }

    public function getAuthorization(): Authorization
    {
        return $this->authorization;
    }
}

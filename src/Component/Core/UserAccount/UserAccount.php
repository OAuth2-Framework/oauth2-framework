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

namespace OAuth2Framework\Component\Core\UserAccount;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;

interface UserAccount extends ResourceOwner
{
    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastLoginAt();
}

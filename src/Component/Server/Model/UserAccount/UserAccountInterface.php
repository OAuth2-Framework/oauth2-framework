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

namespace OAuth2Framework\Component\Server\Model\UserAccount;

use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;

interface UserAccountInterface extends ResourceOwnerInterface
{
    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastLoginAt();
}

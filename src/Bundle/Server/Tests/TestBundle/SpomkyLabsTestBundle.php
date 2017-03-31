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

namespace OAuth2Framework\Bundle\Server\Tests\TestBundle;

use OAuth2Framework\Bundle\Server\Tests\TestBundle\DependencyInjection\TestExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SpomkyLabsTestBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new TestExtension('oauth2_test');
    }
}

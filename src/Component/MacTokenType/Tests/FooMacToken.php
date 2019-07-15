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

namespace OAuth2Framework\Component\MacTokenType\Tests;

use OAuth2Framework\Component\MacTokenType\MacToken;

final class FooMacToken extends MacToken
{
    protected function generateMacKey(): string
    {
        return 'MAC_KEY_FOO_BAR';
    }
}

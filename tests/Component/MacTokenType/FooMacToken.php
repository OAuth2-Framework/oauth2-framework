<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\MacTokenType;

use OAuth2Framework\Component\MacTokenType\MacToken;

final class FooMacToken extends MacToken
{
    protected function generateMacKey(): string
    {
        return 'MAC_KEY_FOO_BAR';
    }
}

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

namespace OAuth2Framework\Component\Server\MacTokenType\Tests;

use OAuth2Framework\Component\Server\MacTokenType\MacToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class FooMacToken extends MacToken
{
    /**
     * {@inheritdoc}
     */
    protected function generateMacKey(): string
    {
        return 'MAC_KEY_FOO_BAR';
    }
}

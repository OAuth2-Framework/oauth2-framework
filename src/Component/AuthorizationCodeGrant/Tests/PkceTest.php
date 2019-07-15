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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Tests;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use PHPUnit\Framework\TestCase;

/**
 * @group PKCE
 *
 * @internal
 * @coversNothing
 */
final class PkceTest extends TestCase
{
    /**
     * @var null|PKCEMethodManager
     */
    private $pkceMethodManager;

    /**
     * @test
     */
    public function thePkceMethodManagerCanHandleSeveralMethods()
    {
        static::assertTrue($this->getPkceMethodManager()->has('S256'));
        static::assertTrue($this->getPkceMethodManager()->has('plain'));
        static::assertEquals(['plain', 'S256'], $this->getPkceMethodManager()->names());
    }

    /**
     * @test
     * @dataProvider challengeData
     */
    public function aChallengeCanBeVerified(string $name, string $codeChallenge, string $codeVerifier)
    {
        $method = $this->getPkceMethodManager()->get($name);

        static::assertTrue($method->isChallengeVerified($codeVerifier, $codeChallenge));
    }

    public function challengeData(): array
    {
        return [
            [
                'method' => 'S256',
                'challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
                'verifier' => 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk',
            ],
            [
                'method' => 'plain',
                'challenge' => 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk',
                'verifier' => 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk',
            ],
        ];
    }

    private function getPkceMethodManager(): PKCEMethodManager
    {
        if (null === $this->pkceMethodManager) {
            $this->pkceMethodManager = new PKCEMethodManager();
            $this->pkceMethodManager->add(new Plain());
            $this->pkceMethodManager->add(new S256());
        }

        return $this->pkceMethodManager;
    }
}

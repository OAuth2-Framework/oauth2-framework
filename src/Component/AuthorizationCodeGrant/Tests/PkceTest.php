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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Tests;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use PHPUnit\Framework\TestCase;

/**
 * @group PKCE
 */
final class PkceTest extends TestCase
{
    /**
     * @test
     */
    public function thePkceMethodManagerCanHandleSeveralMethods()
    {
        self::assertTrue($this->getPkceMethodManager()->has('S256'));
        self::assertTrue($this->getPkceMethodManager()->has('plain'));
        self::assertEquals(['plain', 'S256'], $this->getPkceMethodManager()->names());
    }

    /**
     * @test
     * @dataProvider challengeData
     *
     * @param string $name
     * @param string $codeVerifier
     * @param string $codeChallenge
     */
    public function aChallengeCanBeVerified(string $name, string $codeChallenge, string $codeVerifier)
    {
        $method = $this->getPkceMethodManager()->get($name);

        self::assertTrue($method->isChallengeVerified($codeVerifier, $codeChallenge));
    }

    /**
     * @return array
     */
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

    /**
     * @var null|PKCEMethodManager
     */
    private $pkceMethodManager = null;

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

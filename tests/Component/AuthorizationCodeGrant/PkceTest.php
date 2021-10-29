<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PkceTest extends TestCase
{
    private ?PKCEMethodManager $pkceMethodManager = null;

    /**
     * @test
     */
    public function thePkceMethodManagerCanHandleSeveralMethods(): void
    {
        static::assertTrue($this->getPkceMethodManager()->has('S256'));
        static::assertTrue($this->getPkceMethodManager()->has('plain'));
        static::assertSame(['plain', 'S256'], $this->getPkceMethodManager()->names());
    }

    /**
     * @test
     * @dataProvider challengeData
     */
    public function aChallengeCanBeVerified(string $name, string $codeChallenge, string $codeVerifier): void
    {
        $method = $this->getPkceMethodManager()
            ->get($name)
        ;

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
        if ($this->pkceMethodManager === null) {
            $this->pkceMethodManager = new PKCEMethodManager();
            $this->pkceMethodManager->add(new Plain());
            $this->pkceMethodManager->add(new S256());
        }

        return $this->pkceMethodManager;
    }
}

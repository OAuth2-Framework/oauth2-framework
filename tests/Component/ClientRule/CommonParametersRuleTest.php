<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\CommonParametersRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CommonParametersRuleTest extends TestCase
{
    /**
     * @test
     */
    public function aParameterIsNotAValidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter with key "client_uri" is not a valid URL.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'client_name' => 'Client name',
            'client_uri' => 'urn:foo:bar:OK',
        ]);
        $rule = new CommonParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function commonParameterRule(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'client_name' => 'Client name',
            'client_name#fr' => 'Nom du client',
            'client_uri' => 'http://localhost/information',
            'logo_uri' => 'http://127.0.0.1:8000/logo.png',
            'tos_uri' => 'http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/tos.html',
            'policy_uri' => 'http://localhost/policy',
        ]);
        $rule = new CommonParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('client_name'));
        static::assertSame('Client name', $validatedParameters->get('client_name'));
        static::assertTrue($validatedParameters->has('client_uri'));
        static::assertSame('http://localhost/information', $validatedParameters->get('client_uri'));
        static::assertTrue($validatedParameters->has('logo_uri'));
        static::assertSame('http://127.0.0.1:8000/logo.png', $validatedParameters->get('logo_uri'));
        static::assertTrue($validatedParameters->has('tos_uri'));
        static::assertSame(
            'http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/tos.html',
            $validatedParameters->get('tos_uri')
        );
        static::assertTrue($validatedParameters->has('policy_uri'));
        static::assertSame('http://localhost/policy', $validatedParameters->get('policy_uri'));
    }

    private function getCallable(): RuleHandler
    {
        return new RuleHandler(function (
            ClientId $clientId,
            DataBag $commandParameters,
            DataBag $validatedParameters
        ): DataBag {
            return $validatedParameters;
        });
    }
}

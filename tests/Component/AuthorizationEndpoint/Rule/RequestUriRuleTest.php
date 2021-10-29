<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\Rule;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\RequestUriRule;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RequestUriRuleTest extends TestCase
{
    protected function setUp(): void
    {
        if (! interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-rule" is not installed.');
        }
    }

    /**
     * @test
     */
    public function noResponseType(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $rule = new RequestUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
        static::assertFalse($validatedParameters->has('request_uris'));
    }

    /**
     * @test
     */
    public function theParameterMustBeAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "request_uris" must be a list of URI.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => 'hello',
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterMustBeAnArrayOfString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "request_uris" must be a list of URI.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => [123],
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterMustBeAnArrayOfUris(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "request_uris" must be a list of URI.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => ['hello'],
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => ['https://foo.com/bar'],
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('request_uris'));
        static::assertSame(['https://foo.com/bar'], $validatedParameters->get('request_uris'));
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

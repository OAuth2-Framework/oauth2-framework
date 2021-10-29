<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\RedirectionUriRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RedirectionUriRuleTest extends TestCase
{
    /**
     * @test
     */
    public function noResponseTypeIsUsed(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://foo.com/callback'],
        ]);
        $rule = new RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertSame([], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function aLeastOneRedirectUriMustBeSetForNonConfidentialClients(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-confidential clients must register at least one redirect URI.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function confidentialClientsUsingTokenResponseTypeMustRegisterAtLeastOneRedirectUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Confidential clients must register at least one redirect URI when using the "token" response type.'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theRedirectUrisParameterMustBeAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "redirect_uris" must be a list of URI or URN.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => 'hello',
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theRedirectUrisParameterMustBeAnArrayOfString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "redirect_uris" must be a list of URI or URN.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => [123],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theRedirectUrisParameterMustBeAnArrayOfUris(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "redirect_uris" must be a list of URI or URN.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['hello'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theRedirectUrisMustNotContainAnyFragmentParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "redirect_uris" must only contain URIs without fragment.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://foo.com/#test=bad'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theLocalhostHostIsNotAllowedWhenTheImplicitGrantTypeIsUsed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The host "localhost" is not allowed for web applications that use the Implicit Grant Type.'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://localhost/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theSchemeMustBeHttpsWhenTheImplicitGrantTypeIsUsed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The parameter "redirect_uris" must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://foo.com/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theRedirectUrisMustNotHavePathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The URI listed in the "redirect_uris" parameter must not contain any path traversal.'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['https://foo.com/bar/../bad'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theUrisAreValidatedWithTheImplicitGrantType(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['https://foo.com/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertSame(['https://foo.com/'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function theUrisAreValidatedWithOtherGrantTypes(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['http://localhost/'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['id_token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertSame(['http://localhost/'], $validatedParameters->get('redirect_uris'));
    }

    /**
     * @test
     */
    public function theUrnsAreAllowed(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob', 'urn:ietf:wg:oauth:2.0:oob:auto'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['id_token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('redirect_uris'));
        static::assertSame(
            ['urn:ietf:wg:oauth:2.0:oob', 'urn:ietf:wg:oauth:2.0:oob:auto'],
            $validatedParameters->get('redirect_uris')
        );
    }

    /**
     * @test
     */
    public function theUrnsAreNotValid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "redirect_uris" must be a list of URI or URN.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'redirect_uris' => ['urn:---------------'],
        ]);
        $validatedParameters = new DataBag([
            'response_types' => ['id_token', 'code'],
            'token_endpoint_auth_method' => 'none',
        ]);
        $rule = new RedirectionUriRule();
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
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

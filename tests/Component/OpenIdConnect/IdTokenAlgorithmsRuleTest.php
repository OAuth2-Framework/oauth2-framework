<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\OpenIdConnect;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\OpenIdConnect\Rule\IdTokenAlgorithmsRule;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class IdTokenAlgorithmsRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theIdTokenAlgorithmsAreSupported(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'id_token_signed_response_alg' => 'RS256',
            'id_token_encrypted_response_alg' => 'A256KW',
            'id_token_encrypted_response_enc' => 'A256GCM',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('id_token_signed_response_alg'));
        static::assertTrue($validatedParameters->has('id_token_encrypted_response_alg'));
        static::assertTrue($validatedParameters->has('id_token_encrypted_response_enc'));
    }

    /**
     * @test
     */
    public function theIdTokenSignatureAlgorithmIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The parameter "id_token_signed_response_alg" must be an algorithm supported by this server. Please choose one of the following value(s): RS256, ES256'
        );
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'id_token_signed_response_alg' => 'foo',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theIdTokenKeyEncryptionAlgorithmsIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The parameter "id_token_encrypted_response_alg" must be an algorithm supported by this server. Please choose one of the following value(s): A256KW'
        );
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'id_token_encrypted_response_alg' => 'foo',
            'id_token_encrypted_response_enc' => 'foo',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theIdTokenContentEncryptionAlgorithmsIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The parameter "id_token_encrypted_response_enc" must be an algorithm supported by this server. Please choose one of the following value(s): A256GCM'
        );
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'id_token_encrypted_response_alg' => 'A256KW',
            'id_token_encrypted_response_enc' => 'foo',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
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

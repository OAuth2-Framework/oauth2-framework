<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\OpenIdConnect;

use InvalidArgumentException;
use Jose\Component\Core\Algorithm;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\OpenIdConnect\Rule\IdTokenAlgorithmsRule;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class IdTokenAlgorithmsRuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function theIdTokenAlgorithmsAreSupported(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'id_token_signed_response_alg' => 'XS512',
            'id_token_encrypted_response_alg' => 'RSA_2_5',
            'id_token_encrypted_response_enc' => 'A512ECE+XS512',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

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
            'The parameter "id_token_signed_response_alg" must be an algorithm supported by this server. Please choose one of the following value(s): XS512'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'id_token_signed_response_alg' => 'foo',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theIdTokenKeyEncryptionAlgorithmsIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The parameter "id_token_encrypted_response_alg" must be an algorithm supported by this server. Please choose one of the following value(s): RSA_2_5'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'id_token_encrypted_response_alg' => 'foo',
            'id_token_encrypted_response_enc' => 'foo',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theIdTokenContentEncryptionAlgorithmsIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The parameter "id_token_encrypted_response_enc" must be an algorithm supported by this server. Please choose one of the following value(s): A512ECE+XS512'
        );
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'id_token_encrypted_response_alg' => 'RSA_2_5',
            'id_token_encrypted_response_enc' => 'foo',
        ]);
        $rule = new IdTokenAlgorithmsRule($this->getJWSBuilder(), $this->getJWEBuilder());
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    private function getJWSBuilder(): JWSBuilder
    {
        $algorithm = $this->prophesize(Algorithm::class);
        $algorithm->name()
            ->willReturn('XS512')
        ;

        return new JWSBuilder(new AlgorithmManager([$algorithm->reveal()]));
    }

    private function getJWEBuilder(): JWEBuilder
    {
        $algorithm1 = $this->prophesize(Algorithm::class);
        $algorithm1->name()
            ->willReturn('RSA_2_5')
        ;
        $algorithm2 = $this->prophesize(Algorithm::class);
        $algorithm2->name()
            ->willReturn('A512ECE+XS512')
        ;

        return new JWEBuilder(
            new AlgorithmManager([$algorithm1->reveal()]),
            new AlgorithmManager([$algorithm2->reveal()]),
            new CompressionMethodManager([])
        );
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

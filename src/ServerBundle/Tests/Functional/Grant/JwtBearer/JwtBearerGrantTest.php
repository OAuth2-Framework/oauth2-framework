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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\JwtBearer;

use Base64Url\Base64Url;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group JwtBearer
 */
class JwtBearerGrantTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(JwtBearerGrantType::class)) {
            $this->markTestSkipped('The component "oauth2-framework/jwt-bearer-grant" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionIsMissing()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Missing grant type parameter(s): assertion."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionIsInvalid()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => 'FOO'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported input"}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionDoesNotContainTheMandatoryClaims()
    {
        $client = static::createClient();
        $assertion = $this->createAnAssertionWithoutClaim();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The following claim(s) is/are mandatory: \"iss, sub, aud, exp\"."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionDoesNotContainTheSubjectClaims()
    {
        $client = static::createClient();
        $assertion = $this->createAnAssertionWithoutSubject();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The following claim(s) is/are mandatory: \"sub, aud, exp\"."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionDoesNotContainTheAudienceClaims()
    {
        $client = static::createClient();
        $assertion = $this->createAnAssertionWithoutAudience();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The following claim(s) is/are mandatory: \"aud, exp\"."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionDoesNotContainTheExpirationTimeClaims()
    {
        $client = static::createClient();
        $assertion = $this->createAnAssertionWithoutExpirationTime();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The following claim(s) is/are mandatory: \"exp\"."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAssertionIsValid()
    {
        $client = static::createClient();
        $assertion = $this->createAValidAssertion();
        $client->request('POST', '/token/get', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }

    /**
     * @return string
     */
    private function createAnAssertionWithoutClaim(): string
    {
        $jwk = JWK::create([
            'kty' => 'oct',
            'use' => 'sig',
            'k' => Base64Url::encode('secret'),
        ]);
        $claims = [];

        return $this->sign($claims, $jwk);
        //$jwk = JWK::createFromJson('{"kty":"RSA","n":"sLjaCStJYRr_y7_3GLlDb4bnGJ8XirSdFboYmvA38NXJ6PhIIjr-sFzfwlcpxZxz6zzjXkDFs3AcUOvC3_KRT5tn4XBOHcR6ABrT65dZTe_qalEpYeQG4oxevc01vmD_dD6Ho2O69amT4gscus2pvszFPdraMYybH24aQFztVtc","e":"AQAB","d":"By-tJhxNgpZfeoCW4rl95YYd1aF6iphnnt-PapWEINYAvOmDvWiavL86FiQHPdLr38_9CvMlVvOjIyNDLGonwHynPxAzUsT7M891N9D0cSCv9DlV3uqRVtdqF4MtWtpU5JWJ9q6auL1UPx2tJhOygu9tJ7w0bTGFwrUdb8PSnlE","p":"3p-6HWbX9YcSkeksJXW3_Y2cfZgRCUXH2or1dIidmscb4VVtTUwb-8gGzUDEq4iS_5pgLARl3O4lOHK0n6Qbrw","q":"yzdrGWwgaWqK6e9VFv3NXGeq1TEKHLkXjF7J24XWKm9lSmlssPRv0NwMPVp_CJ39BrLfFtpFr_fh0oG1sVZ5WQ","dp":"UQ6rP0VQ4G77zfCuSD1ibol_LyONIGkt6V6rHHEZoV9ZwWPPVlOd5MDh6R3p_eLOUw6scZpwVE7JcpIhPfcMtQ","dq":"Jg8g_cfkYhnUHm_2bbHm7jF0Ky1eCXcY0-9Eutpb--KVA9SuyI1fC6zKlgsG06RTKRgC9BK5DnXMU1J7ptTdMQ","qi":"17kC87NLUV6z-c-wtmbNqAkDbKmwpb2RMsGUQmhEPJwnWuwEKZpSQz776SUVwoc0xiQ8DpvU_FypflIlm6fq9w"}');
    }

    /**
     * @return string
     */
    private function createAnAssertionWithoutSubject(): string
    {
        $jwk = JWK::create([
            'kty' => 'oct',
            'use' => 'sig',
            'k' => Base64Url::encode('secret'),
        ]);
        $claims = ['iss' => 'CLIENT_ID_4'];

        return $this->sign($claims, $jwk);
    }

    /**
     * @return string
     */
    private function createAnAssertionWithoutAudience(): string
    {
        $jwk = JWK::create([
            'kty' => 'oct',
            'use' => 'sig',
            'k' => Base64Url::encode('secret'),
        ]);
        $claims = ['iss' => 'CLIENT_ID_4', 'sub' => 'CLIENT_ID_4'];

        return $this->sign($claims, $jwk);
    }

    /**
     * @return string
     */
    private function createAnAssertionWithoutExpirationTime(): string
    {
        $jwk = JWK::create([
            'kty' => 'oct',
            'use' => 'sig',
            'k' => Base64Url::encode('secret'),
        ]);
        $claims = [
            'iss' => 'CLIENT_ID_4',
            'sub' => 'CLIENT_ID_4',
            'aud' => 'https://oauth2.test/',
        ];

        return $this->sign($claims, $jwk);
    }

    /**
     * @return string
     */
    private function createAValidAssertion(): string
    {
        $jwk = JWK::create([
            'kty' => 'oct',
            'use' => 'sig',
            'k' => Base64Url::encode('secret'),
        ]);
        $claims = [
            'iss' => 'CLIENT_ID_4',
            'sub' => 'CLIENT_ID_4',
            'aud' => 'https://oauth2.test/',
            'exp' => time() + 3600,
        ];

        return $this->sign($claims, $jwk);
    }

    /**
     * @param array $claims
     * @param JWK   $jwk
     *
     * @return string
     */
    private function sign(array $claims, JWK $jwk): string
    {
        $jsonConverter = new StandardConverter();
        $jwsBuilder = new JWSBuilder(
            $jsonConverter,
            AlgorithmManager::create([new HS256()])
        );
        $payload = $jsonConverter->encode($claims);
        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => 'HS256'])
            ->build();
        $token = (new CompactSerializer($jsonConverter))->serialize($jws);

        return $token;
    }
}

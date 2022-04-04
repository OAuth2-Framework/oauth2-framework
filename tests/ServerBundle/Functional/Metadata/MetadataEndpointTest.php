<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Metadata;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use const JSON_THROW_ON_ERROR;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class MetadataEndpointTest extends WebTestCase
{
    use ArraySubsetAsserts;

    /**
     * @test
     */
    public function theMetadataEndpointIsAvailable(): void
    {
        $client = static::createClient(server: [
            'HTTPS' => 'on',
        ]);
        $client->request(method:'GET', uri:'/.well-known/openid-configuration');
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        static::assertIsArray($content);

        $data = [
            'token_endpoint_auth_methods_supported' => [
                'none',
                'client_secret_basic',
                'client_secret_post',
                'client_secret_jwt',
                'private_key_jwt',
            ],
            'token_endpoint_auth_signing_alg_values_supported' => [
                'RS256',
                'RS512',
                'HS256',
                'HS512',
                'ES256',
                'ES512',
            ],
            'token_endpoint_auth_encryption_alg_values_supported' => ['RSA-OAEP-256', 'ECDH-ES'],
            'token_endpoint_auth_encryption_enc_values_supported' => ['A128CBC-HS256'],
            'scopes_supported' => ['openid', 'scope1', 'scope2'],
            'registration_endpoint' => 'https://localhost/client/management',
            'authorization_request_entry_endpoint' => 'https://localhost/authorize',
            'request_parameter_supported' => true,
            'request_uri_parameter_supported' => true,
            'require_request_uri_registration' => true,
            'request_object_signing_alg_values_supported' => ['RS512', 'HS512'],
            'request_object_encryption_alg_values_supported' => ['RSA-OAEP-256', 'ECDH-ES'],
            'request_object_encryption_enc_values_supported' => ['A256CBC-HS512', 'A256GCM'],
            'response_modes_supported' => ['query', 'fragment', 'form_post'],
            'response_types_supported' => [
                'code',
                'token',
                'id_token',
                'code id_token',
                'code id_token token',
                'code token',
                'id_token token',
            ],
            'grant_types_supported' => [
                'authorization_code',
                'client_credentials',
                'implicit',
                'refresh_token',
                'password',
                'urn:ietf:params:oauth:grant-type:jwt-bearer',
            ],
            'token_endpoint' => 'https://localhost/token/get',
            'token_introspection_endpoint' => 'https://localhost/token/introspect',
            'token_revocation_endpoint' => 'https://localhost/token/revoke',
            'jwks_uri' => 'https://localhost/keys/public.jwkset',
            'issuer' => 'https://oauth2.test/',
            'service_documentation' => 'https://foo.foo/doc/service/developer',
            'op_policy_uri' => 'https://foo.foo/doc/policy',
            'op_tos_uri' => 'https://foo.foo/doc/tos',
            'acr_values_supported' => ['urn:mace:incommon:iap:silver', 'urn:mace:incommon:iap:bronze'],
            'display_values_supported' => ['page'],
            'ui_locales_supported' => ['fr', 'en'],
            'claims_locales_supported' => ['en', 'fr', 'de'],
            'check_session_iframe' => 'https://localhost/session/manager/iframe',
            'subject_types_supported' => ['public', 'pairwise'],
            'id_token_signing_alg_values_supported' => ['RS256', 'RS512', 'ES256', 'ES512'],
            'id_token_encryption_alg_values_supported' => ['RSA-OAEP-256', 'ECDH-ES'],
            'id_token_encryption_enc_values_supported' => ['A256CBC-HS512', 'A256GCM'],
            'claims_supported' => [
                'address',
                'auth_time',
                'birthdate',
                'email',
                'email_verified',
                'family_name',
                'gender',
                'given_name',
                'locale',
                'middle_name',
                'name',
                'nickname',
                'phone_number',
                'phone_number_verified',
                'picture',
                'preferred_username',
                'profile',
                'updated_at',
                'website',
                'zoneinfo',
            ],
            'claim_types_supported' => ['normal', 'aggregated', 'distributed'],
            'claims_parameter_supported' => true,
            'userinfo_endpoint' => 'https://localhost/userinfo',
            'userinfo_signing_alg_values_supported' => ['RS256', 'RS512', 'ES256', 'ES512'],
            'userinfo_encryption_alg_values_supported' => ['RSA-OAEP-256', 'ECDH-ES'],
            'userinfo_encryption_enc_values_supported' => ['A256CBC-HS512', 'A256GCM'],
        ];
        static::assertArraySubset($data, $content, true);
        static::assertArrayHasKey('signed_metadata', $content);
        static::assertStringStartsWith(
            'eyJhbGciOiJSUzI1NiIsImtpZCI6IjNKdnl6UUVOdzJ1QnMtbElfM3RuX21mTnRuN0VTeG81RFkzclBadlR2TEkifQ.',
            $content['signed_metadata']
        );
    }
}

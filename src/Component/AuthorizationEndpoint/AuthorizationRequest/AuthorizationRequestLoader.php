<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest;

use function array_key_exists;
use Assert\Assertion;
use Base64Url\Base64Url;
use function count;
use function in_array;
use function is_string;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Throwable;

class AuthorizationRequestLoader
{
    private bool $requestObjectAllowed = false;

    private bool $requestObjectReferenceAllowed = false;

    private ?JWKSet $keyEncryptionKeySet = null;

    private bool $requireRequestUriRegistration = true;

    private bool $requireEncryption = false;

    private ?ClientInterface $client = null;

    private ?JWSVerifier $jwsVerifier = null;

    private ?ClaimCheckerManager $claimCheckerManager = null;

    private ?JWELoader $jweLoader = null;

    private ?JKUFactory $jkuFactory = null;

    private ?RequestFactoryInterface $requestFactory = null;

    public function __construct(
        private ClientRepository $clientRepository
    ) {
    }

    public static function create(ClientRepository $clientRepository): static
    {
        return new self($clientRepository);
    }

    public function isRequestUriRegistrationRequired(): bool
    {
        return $this->requireRequestUriRegistration;
    }

    public function isRequestObjectSupportEnabled(): bool
    {
        return $this->requestObjectAllowed;
    }

    public function isRequestObjectReferenceSupportEnabled(): bool
    {
        return $this->requestObjectReferenceAllowed;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsVerifier === null ? [] : $this->jwsVerifier->getSignatureAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return $this->jweLoader === null ? [] : $this->jweLoader->getJweDecrypter()->getKeyEncryptionAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedContentEncryptionAlgorithms(): array
    {
        return $this->jweLoader === null ? [] : $this->jweLoader->getJweDecrypter()->getContentEncryptionAlgorithmManager()->list();
    }

    public function enableSignedRequestObjectSupport(
        JWSVerifier $jwsVerifier,
        ClaimCheckerManager $claimCheckerManager
    ): void {
        $this->jwsVerifier = $jwsVerifier;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->requestObjectAllowed = true;
    }

    public function enableRequestObjectReferenceSupport(
        ClientInterface $client,
        bool $requireRequestUriRegistration
    ): void {
        Assertion::true($this->isRequestObjectSupportEnabled(), 'Request object support must be enabled first.');
        $this->requestObjectReferenceAllowed = true;
        $this->requireRequestUriRegistration = $requireRequestUriRegistration;
        $this->requestFactory = new Psr17Factory();
        $this->client = $client;
    }

    public function enableEncryptedRequestObjectSupport(
        JWELoader $jweLoader,
        JWKSet $keyEncryptionKeySet,
        bool $requireEncryption
    ): void {
        Assertion::true($this->isRequestObjectSupportEnabled(), 'Request object support must be enabled first.');
        Assertion::greaterThan($keyEncryptionKeySet->count(), 0, 'The encryption key set must have at least one key.');
        $this->jweLoader = $jweLoader;
        $this->requireEncryption = $requireEncryption;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    public function enableJkuSupport(JKUFactory $jkuFactory): static
    {
        $this->jkuFactory = $jkuFactory;

        return $this;
    }

    public function isEncryptedRequestSupportEnabled(): bool
    {
        return $this->keyEncryptionKeySet !== null;
    }

    public function load(array $queryParameters): AuthorizationRequest
    {
        $client = null;
        if (array_key_exists('request', $queryParameters)) {
            $queryParameters = $this->createFromRequestParameter($queryParameters, $client);
        } elseif (array_key_exists('request_uri', $queryParameters)) {
            $queryParameters = $this->createFromRequestUriParameter($queryParameters, $client);
        } else {
            $client = $this->getClient($queryParameters);
        }

        return AuthorizationRequest::create($client, $queryParameters);
    }

    private function createFromRequestParameter(array $params, Client &$client = null): array
    {
        if ($this->isRequestObjectSupportEnabled() === false) {
            throw OAuth2Error::requestNotSupported('The parameter "request" is not supported.');
        }
        $request = $params['request'];
        if (! is_string($request)) {
            throw OAuth2Error::requestNotSupported('The parameter "request" must be an assertion.');
        }

        $params = $this->loadRequestObject($params, $request, $client);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    private function createFromRequestUriParameter(array $params, Client &$client = null): array
    {
        if ($this->isRequestObjectReferenceSupportEnabled() === false) {
            throw OAuth2Error::requestUriNotSupported('The parameter "request_uri" is not supported.');
        }
        $requestUri = $params['request_uri'];
        if (preg_match('#/\.\.?(/|$)#', $requestUri) === 1) {
            throw OAuth2Error::invalidRequestUri('The request Uri is not allowed.');
        }
        $content = $this->downloadContent($requestUri);
        $params = $this->loadRequestObject($params, $content, $client);
        $this->checkRequestUri($client, $requestUri);
        $this->checkIssuerAndClientId($params);

        return $params;
    }

    private function checkIssuerAndClientId(array $params): void
    {
        if (array_key_exists('iss', $params) && array_key_exists('client_id', $params)) {
            Assertion::eq(
                $params['iss'],
                $params['client_id'],
                'The issuer of the request object is not the client who requests the authorization.'
            );
        }
    }

    private function checkRequestUri(Client $client, string $requestUri): void
    {
        $storedRequestUris = $client->has('request_uris') ? $client->get('request_uris') : [];
        if (count($storedRequestUris) === 0) {
            if ($this->isRequestUriRegistrationRequired()) {
                throw OAuth2Error::invalidRequestUri('The clients shall register at least one request object uri.');
            }

            return;
        }

        foreach ($storedRequestUris as $storedRequestUri) {
            if (strcasecmp(
                mb_substr($requestUri, 0, mb_strlen($storedRequestUri, '8bit'), '8bit'),
                $storedRequestUri
            ) === 0) {
                return;
            }
        }

        throw OAuth2Error::invalidRequestUri('The request Uri is not allowed.');
    }

    private function loadRequestObject(array $params, string $request, Client &$client = null): array
    {
        // FIXME Can be
        // - encrypted (not supported)
        // - encrypted and signed (supported)
        // - signed (supported)
        $request = $this->tryToLoadEncryptedRequest($request);

        try {
            $serializer = new CompactSerializer();
            $jwt = $serializer->unserialize($request);

            $claims = JsonConverter::decode($jwt->getPayload());
            Assertion::isArray($claims, 'Invalid assertion. The payload must contain claims.');
            $this->claimCheckerManager->check($claims);
            $parameters = array_merge($params, $claims);
            $client = $this->getClient($parameters);

            $public_key_set = $this->getClientKeySet($client);
            $this->checkAlgorithms($jwt, $client);
            Assertion::true(
                $this->jwsVerifier->verifyWithKeySet($jwt, $public_key_set, 0),
                'The verification of the request object failed.'
            ); //FIXME: header checker should be used

            return $parameters;
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (Throwable $e) {
            throw OAuth2Error::invalidRequestObject($e->getMessage(), [], $e);
        }
    }

    private function tryToLoadEncryptedRequest(string $request): string
    {
        if ($this->jweLoader === null) {
            return $request;
        }

        try {
            $jwe = $this->jweLoader->loadAndDecryptWithKeySet($request, $this->keyEncryptionKeySet, $recipient);
            Assertion::eq(1, $jwe->countRecipients(), 'The request must use the compact serialization mode.');

            return $jwe->getPayload();
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (Throwable $e) {
            if ($this->requireEncryption === true) {
                throw OAuth2Error::invalidRequestObject($e->getMessage(), [], $e);
            }

            return $request;
        }
    }

    private function checkAlgorithms(JWS $jws, Client $client): void
    {
        $signatureAlgorithm = $jws->getSignature(0)
            ->getProtectedHeaderParameter('alg')
        ;
        Assertion::string($signatureAlgorithm, 'Invalid algorithm parameter in Request Object.');
        if ($client->has('request_object_signing_alg')) {
            Assertion::eq(
                $signatureAlgorithm,
                $client->get('request_object_signing_alg'),
                sprintf('The algorithm "%s" is not allowed by the client.', $signatureAlgorithm)
            );
        }

        $this->checkUsedAlgorithm($signatureAlgorithm);
    }

    private function checkUsedAlgorithm(string $algorithm): void
    {
        $supportedAlgorithms = $this->getSupportedSignatureAlgorithms();
        Assertion::inArray(
            $algorithm,
            $supportedAlgorithms,
            sprintf(
                'The algorithm "%s" is not allowed for request object signatures. Please use one of the following algorithm(s): %s',
                $algorithm,
                implode(', ', $supportedAlgorithms)
            )
        );
    }

    private function downloadContent(string $url): string
    {
        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        Assertion::eq(200, $response->getStatusCode(), 'Unable to load the request object');

        return $response->getBody()
            ->getContents()
        ;
    }

    private function getClient(array $params): Client
    {
        $client = array_key_exists('client_id', $params) ? $this->clientRepository->find(
            new ClientId($params['client_id'])
        ) : null;
        if (! $client instanceof Client || $client->isDeleted() === true) {
            throw OAuth2Error::invalidRequest('Parameter "client_id" missing or invalid.');
        }

        return $client;
    }

    private function getClientKeySet(Client $client): JWKSet
    {
        $keyset = new JWKSet([]);
        if ($client->has('jwks')) {
            $jwks = JWKSet::createFromJson($client->get('jwks'));
            foreach ($jwks as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }
        if ($client->has('client_secret')) {
            $jwk = new JWK([
                'kty' => 'oct',
                'use' => 'sig',
                'k' => Base64Url::encode($client->get('client_secret')),
            ]);
            $keyset = $keyset->with($jwk);
        }
        if ($this->jkuFactory !== null && $client->has('jwks_uri')) {
            $jwks_uri = $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            foreach ($jwks_uri as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }
        if (in_array('none', $this->getSupportedSignatureAlgorithms(), true)) {
            $keyset = $keyset->with(new JWK([
                'kty' => 'none',
                'alg' => 'none',
                'use' => 'sig',
            ]));
        }

        Assertion::greaterThan($keyset->count(), 0, 'The client has no key or key set.');

        return $keyset;
    }
}

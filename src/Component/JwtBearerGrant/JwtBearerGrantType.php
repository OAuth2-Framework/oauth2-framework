<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\JwtBearerGrant;

use Assert\Assertion;
use Base64Url\Base64Url;
use function count;
use function in_array;
use InvalidArgumentException;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class JwtBearerGrantType implements GrantType
{
    private bool $encryptionRequired = false;

    private ?JWEDecrypter $jweDecrypter = null;

    private ?JWKSet $keyEncryptionKeySet = null;

    private ?TrustedIssuerRepository $trustedIssuerRepository = null;

    private ?JKUFactory $jkuFactory = null;

    public function __construct(
        private JWSVerifier $jwsVerifier,
        private HeaderCheckerManager $headerCheckerManager,
        private ClaimCheckerManager $claimCheckerManager,
        private ClientRepository $clientRepository,
        private ?UserAccountRepository $userAccountRepository
    ) {
    }

    public static function create(
        JWSVerifier $jwsVerifier,
        HeaderCheckerManager $headerCheckerManager,
        ClaimCheckerManager $claimCheckerManager,
        ClientRepository $clientRepository,
        ?UserAccountRepository $userAccountRepository
    ): static {
        return new self(
            $jwsVerifier,
            $headerCheckerManager,
            $claimCheckerManager,
            $clientRepository,
            $userAccountRepository
        );
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function enableEncryptedAssertions(
        JWEDecrypter $jweDecrypter,
        JWKSet $keyEncryptionKeySet,
        bool $encryptionRequired
    ): void {
        $this->jweDecrypter = $jweDecrypter;
        $this->encryptionRequired = $encryptionRequired;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    public function enableTrustedIssuerSupport(TrustedIssuerRepository $trustedIssuerRepository): static
    {
        $this->trustedIssuerRepository = $trustedIssuerRepository;

        return $this;
    }

    public function enableJkuSupport(JKUFactory $jkuFactory): static
    {
        $this->jkuFactory = $jkuFactory;

        return $this;
    }

    public function name(): string
    {
        return 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['assertion'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (count($diff) !== 0) {
            throw OAuth2Error::invalidRequest(sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $assertion = $parameters['assertion'];
        $assertion = $this->tryToDecryptTheAssertion($assertion);

        try {
            $jwsSerializer = new JwsCompactSerializer();
            $jws = $jwsSerializer->unserialize($assertion);
            if ($jws->countSignatures() !== 1) {
                throw new InvalidArgumentException('The assertion must have only one signature.');
            }
            $this->headerCheckerManager->check($jws, 0);
            $payload = $jws->getPayload();
            Assertion::string($payload, 'The assertion is not valid. No payload available.');
            $claims = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            $this->claimCheckerManager->check($claims);
            $diff = array_diff(['iss', 'sub', 'aud', 'exp'], array_keys($claims));
            if (count($diff) !== 0) {
                throw new InvalidArgumentException(sprintf(
                    'The following claim(s) is/are mandatory: "%s".',
                    implode(', ', array_values($diff))
                ));
            }
            $this->checkJWTSignature($grantTypeData, $jws, $claims);
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (Throwable $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    private function tryToDecryptTheAssertion(string $assertion): string
    {
        if ($this->jweDecrypter === null) {
            return $assertion;
        }

        try {
            $jweSerializer = new JweCompactSerializer();
            $jwe = $jweSerializer->unserialize($assertion);
            if ($jwe->countRecipients() !== 1) {
                throw new InvalidArgumentException('The assertion must have only one recipient.');
            }
            Assertion::notNull($this->keyEncryptionKeySet, 'The key encryption key set is not set.');
            if ($this->jweDecrypter->decryptUsingKeySet($jwe, $this->keyEncryptionKeySet, 0) === true) {
                return $jwe->getPayload();
            }

            throw new InvalidArgumentException('Unable to decrypt the assertion.');
        } catch (Throwable $e) {
            if ($this->encryptionRequired === true) {
                throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
            }

            return $assertion;
        }
    }

    private function checkJWTSignature(GrantTypeData $grantTypeData, JWS $jws, array $claims): void
    {
        $iss = $claims['iss'];
        $sub = $claims['sub'];

        if ($iss === $sub) { // The issuer is the resource owner
            $client = $this->clientRepository->find(new ClientId($iss));

            if ($client === null || $client->isDeleted() === true) {
                throw OAuth2Error::invalidGrant('Unable to find the issuer of the assertion.');
            }
            if (! $grantTypeData->hasClient()) {
                $grantTypeData->setClient($client);
            } elseif ($grantTypeData->getClient()->getPublicId()->getValue() !== $client->getPublicId()->getValue()) {
                throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_CLIENT, 'Client authentication failed.');
            }
            $grantTypeData->setResourceOwnerId($client->getPublicId());
            $allowedSignatureAlgorithms = $this->jwsVerifier->getSignatureAlgorithmManager()
                ->list()
            ;
            $signatureKeys = $this->getClientKeySet($client);
        } elseif ($this->trustedIssuerRepository !== null) { // Trusted issuer support
            $issuer = $this->trustedIssuerRepository->find($iss);
            if ($issuer === null) {
                throw new InvalidArgumentException('Unable to find the issuer of the assertion.');
            }
            $allowedSignatureAlgorithms = $issuer->getAllowedSignatureAlgorithms();
            $signatureKeys = $issuer->getJWKSet();
            $resourceOwnerId = $this->findResourceOwner($sub);
            if ($resourceOwnerId === null) {
                throw new InvalidArgumentException(sprintf('Unknown resource owner with ID "%s"', $sub));
            }
            $grantTypeData->setResourceOwnerId($resourceOwnerId);
        } else {
            throw new InvalidArgumentException('Unable to find the issuer of the assertion.');
        }

        if (! $jws->getSignature(0)->hasProtectedHeaderParameter('alg') || ! in_array(
            $jws->getSignature(0)
                ->getProtectedHeaderParameter('alg'),
            $allowedSignatureAlgorithms,
            true
        )) {
            throw new InvalidArgumentException(sprintf(
                'The signature algorithm "%s" is not allowed.',
                $jws->getSignature(0)->getProtectedHeaderParameter('alg')
            ));
        }

        $this->jwsVerifier->verifyWithKeySet($jws, $signatureKeys, 0);
        $grantTypeData->getMetadata()
            ->set('jwt', $jws)
        ;
        $grantTypeData->getMetadata()
            ->set('claims', $claims)
        ;
    }

    private function findResourceOwner(string $subject): ?ResourceOwnerId
    {
        $userAccount = $this->userAccountRepository?->find(new UserAccountId($subject));
        if ($userAccount !== null) {
            return $userAccount->getUserAccountId();
        }
        $client = $this->clientRepository->find(new ClientId($subject));
        if ($client !== null) {
            return $client->getPublicId();
        }

        return null;
    }

    private function getClientKeySet(Client $client): JWKSet
    {
        switch (true) {
            case $client->has('jwks') && $client->getTokenEndpointAuthenticationMethod() === 'private_key_jwt':
                return JWKSet::createFromJson($client->get('jwks'));

            case $client->has(
                'client_secret'
            ) && $client->getTokenEndpointAuthenticationMethod() === 'client_secret_jwt':
                $jwk = new JWK([
                    'kty' => 'oct',
                    'use' => 'sig',
                    'k' => Base64Url::encode($client->get('client_secret')),
                ]);

                return new JWKSet([$jwk]);

            case $client->has(
                'jwks_uri'
            ) && $client->getTokenEndpointAuthenticationMethod() === 'private_key_jwt' && $this->jkuFactory !== null:
                return $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));

            default:
                throw new InvalidArgumentException('The client has no key or key set.');
        }
    }
}

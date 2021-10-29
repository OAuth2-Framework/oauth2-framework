<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use function count;
use InvalidArgumentException;
use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Throwable;

class JwksRule implements Rule
{
    public function __construct(
        private ?JKUFactory $jkuFactory
    ) {
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('jwks') && $commandParameters->has('jwks_uri')) {
            throw new InvalidArgumentException('The parameters "jwks" and "jwks_uri" cannot be set together.');
        }

        if ($commandParameters->has('jwks')) {
            try {
                $keyset = JWKSet::createFromKeyData($commandParameters->get('jwks'));
            } catch (Throwable $e) {
                throw new InvalidArgumentException('The parameter "jwks" must be a valid JWKSet object.', 0, $e);
            }
            if (count($keyset) === 0) {
                throw new InvalidArgumentException('The parameter "jwks" must not be empty.');
            }
            $validatedParameters->set('jwks', $commandParameters->get('jwks'));
        }
        if ($commandParameters->has('jwks_uri')) {
            if ($this->jkuFactory === null) {
                throw new InvalidArgumentException(
                    'Distant key sets cannot be used. Please use "jwks" instead of "jwks_uri".'
                );
            }

            try {
                $jwks = $this->jkuFactory->loadFromUrl($commandParameters->get('jwks_uri'));
            } catch (Throwable $e) {
                throw new InvalidArgumentException('The parameter "jwks_uri" must be a valid uri to a JWKSet.', 0, $e);
            }
            if ($jwks->count() === 0) {
                throw new InvalidArgumentException('The distant key set is empty.');
            }
            $validatedParameters->set('jwks_uri', $commandParameters->get('jwks_uri'));
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}

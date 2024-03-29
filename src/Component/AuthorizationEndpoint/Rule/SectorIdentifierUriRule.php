<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use Assert\Assertion;
use const JSON_THROW_ON_ERROR;
use League\Uri\Uri;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class SectorIdentifierUriRule implements Rule
{
    private readonly RequestFactoryInterface $requestFactory;

    public function __construct(
        private readonly ClientInterface $client
    ) {
        $this->requestFactory = new Psr17Factory();
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);

        if ($commandParameters->has('sector_identifier_uri')) {
            $redirectUris = $validatedParameters->has('redirect_uris') ? $validatedParameters->get(
                'redirect_uris'
            ) : [];
            $this->checkSectorIdentifierUri($commandParameters->get('sector_identifier_uri'), $redirectUris);
            $validatedParameters->set('sector_identifier_uri', $commandParameters->get('sector_identifier_uri'));
        }

        return $validatedParameters;
    }

    private function checkSectorIdentifierUri(string $url, array $redirectUris): void
    {
        $data = Uri::createFromString($url);
        Assertion::eq('https', $data->getScheme(), sprintf('The sector identifier URI "%s" is not valid.', $url));
        Assertion::notEmpty($data->getHost(), sprintf('The sector identifier URI "%s" is not valid.', $url));

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        Assertion::eq(
            200,
            $response->getStatusCode(),
            sprintf('Unable to get Uris from the Sector Identifier Uri "%s".', $url)
        );

        $body = $response->getBody()
            ->getContents()
        ;
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        Assertion::isArray($data, 'The provided sector identifier URI is not valid: it must contain at least one URI.');
        Assertion::notEmpty(
            $data,
            'The provided sector identifier URI is not valid: it must contain at least one URI.'
        );

        $diff = array_diff($redirectUris, $data);
        Assertion::noContent(
            $diff,
            'The provided sector identifier URI is not valid: it must contain at least the redirect URI(s) set in the registration request.'
        );
    }
}

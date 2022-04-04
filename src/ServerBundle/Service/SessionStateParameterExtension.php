<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Service;

use Assert\Assertion;
use Base64Url\Base64Url;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\OpenIdConnect\ConsentScreen\SessionStateParameterExtension as BaseSessionStateParameterExtension;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionStateParameterExtension extends BaseSessionStateParameterExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $storageName,
        private readonly ?string $path = '/',
        private readonly ?string $domain = null,
        private readonly bool $secure = false,
        private readonly bool $httpOnly = true,
        private readonly bool $raw = false,
        private readonly ?string $sameSite = null
    ) {
    }

    protected function getBrowserState(ServerRequestInterface $request, AuthorizationRequest $authorization): string
    {
        if ($this->requestStack->getSession()->has($this->storageName)) {
            return $this->requestStack->getSession()
                ->get($this->storageName)
            ;
        }

        $browserState = Base64Url::encode(random_bytes(64));
        $this->requestStack->getSession()
            ->set($this->storageName, $browserState)
        ;
        $cookie = new Cookie(
            $this->storageName,
            $browserState,
            0,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly,
            $this->raw,
            $this->sameSite
        );
        $authorization->setResponseHeader('Set-Cookie', (string) $cookie);

        return $browserState;
    }

    protected function calculateSessionState(
        ServerRequestInterface $request,
        AuthorizationRequest $authorization,
        string $browserState
    ): string {
        $redirectUri = $authorization->getRedirectUri();
        $origin = $this->getOriginUri($redirectUri);
        $salt = Base64Url::encode(random_bytes(16));
        $hash = hash(
            'sha256',
            sprintf('%s%s%s%s', $authorization->getClient()->getPublicId(), $origin, $browserState, $salt)
        );

        return sprintf('%s.%s', $hash, $salt);
    }

    private function getOriginUri(string $redirectUri): string
    {
        $url_parts = parse_url($redirectUri);
        Assertion::keyExists($url_parts, 'scheme', 'Invalid URI');
        Assertion::isArray($url_parts, 'Invalid URI');
        Assertion::keyExists($url_parts, 'host', 'Invalid URI');

        return sprintf(
            '%s://%s%s',
            $url_parts['scheme'],
            $url_parts['host'],
            isset($url_parts['port']) ? ':' . $url_parts['port'] : ''
        );
    }
}

<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GrantTypeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly GrantTypeManager $grantTypeManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $parameters = RequestBodyParser::parseFormUrlEncoded($request);
            if (! $parameters->has('grant_type')) {
                throw new InvalidArgumentException('The "grant_type" parameter is missing.');
            }
            $grant_type = $parameters->get('grant_type');
            if (! $this->grantTypeManager->has($grant_type)) {
                throw new InvalidArgumentException(sprintf(
                    'The grant type "%s" is not supported by this server.',
                    $grant_type
                ));
            }
            $type = $this->grantTypeManager->get($grant_type);
            $request = $request->withAttribute('grant_type', $type);

            return $handler->handle($request);
        } catch (InvalidArgumentException $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }
    }
}

<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Util;

use function count;
use function in_array;
use InvalidArgumentException;
use function is_array;
use const JSON_THROW_ON_ERROR;
use JsonException;
use League\Uri\QueryParser;
use Psr\Http\Message\ServerRequestInterface;

class RequestBodyParser
{
    public static function parseJson(ServerRequestInterface $request): array
    {
        if (! $request->hasHeader('Content-Type') || ! in_array(
            'application/json',
            $request->getHeader('Content-Type'),
            true
        )) {
            throw new InvalidArgumentException('Unsupported request body content type.');
        }
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && count($parsedBody) !== 0) {
            return $parsedBody;
        }

        $body = $request->getBody()
            ->getContents()
        ;
        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($json)) {
                throw new InvalidArgumentException('Invalid body');
            }
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid body');
        }

        return $json;
    }

    public static function parseFormUrlEncoded(ServerRequestInterface $request): array
    {
        if (! $request->hasHeader('Content-Type') || ! in_array(
            'application/x-www-form-urlencoded',
            $request->getHeader('Content-Type'),
            true
        )) {
            throw new InvalidArgumentException('Unsupported request body content type.');
        }
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && count($parsedBody) !== 0) {
            return $parsedBody;
        }

        $body = $request->getBody()
            ->getContents()
        ;

        return (new QueryParser())->parse($body, '&', QueryParser::RFC1738_ENCODING);
    }
}

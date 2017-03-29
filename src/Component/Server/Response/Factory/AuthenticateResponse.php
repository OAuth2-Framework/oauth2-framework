<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Response\Factory;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Response\OAuth2Error;
use Psr\Http\Message\ResponseInterface;

final class AuthenticateResponse extends OAuth2Error
{
    /**
     * @var array
     */
    private $schemes;

    /**
     * AuthenticateResponse constructor.
     *
     * {@inheritdoc}
     *
     * @param array $schemes Schemes
     */
    public function __construct(int $code, array $data, ResponseInterface $response, array $schemes)
    {
        Assertion::allString($schemes, 'invalid_schemes');
        parent::__construct(401, $data, $response);
        $this->schemes = $schemes;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaders(): array
    {
        $headers = parent::getHeaders();
        unset($headers['Content-Type']);

        $schemes = $this->computeSchemes($this->getData());
        $headers['WWW-Authenticate'] = $schemes;

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBody(): string
    {
        return '';
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function computeSchemes(array $data): array
    {
        $schemes = [];
        foreach ($this->schemes as $id => $scheme) {
            Assertion::string($scheme);
            $scheme = $this->appendParameters($scheme, $data);
            $schemes[$id] = $scheme;
        }

        return $schemes;
    }

    /**
     * @param string $scheme
     * @param array  $parameters
     *
     * @return string
     */
    private function appendParameters($scheme, array $parameters): string
    {
        $position = mb_strpos($scheme, ' ', 0, 'utf-8');
        $add_comma = false === $position ? false : true;

        foreach ($parameters as $key => $value) {
            if (false === $add_comma) {
                $add_comma = true;
                $scheme = sprintf('%s %s=%s', $scheme, $key, $this->quote($value));
            } else {
                $scheme = sprintf('%s,%s=%s', $scheme, $key, $this->quote($value));
            }
        }

        return $scheme;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    private function quote($data): string
    {
        return is_numeric($data) ? (string) $data : sprintf('"%s"', $data);
    }
}

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

namespace OAuth2Framework\Component\Server\Schema;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonEncoder;
use Webmozart\Json\JsonValidator;

final class DomainConverter
{
    /**
     * @var JsonValidator
     */
    private $validator;

    /**
     * @var JsonDecoder
     */
    private $decoder;

    /**
     * @var JsonEncoder
     */
    private $encoder;

    /**
     * EventConverter constructor.
     */
    public function __construct()
    {
        $this->validator = new JsonValidator(null, new DomainUriRetriever());
        $this->decoder = new JsonDecoder();
        $this->encoder = new JsonEncoder();
    }

    public function fromJson(string $jsonData): DomainObjectInterface
    {
        try {
            $decoded = $this->decoder->decode($jsonData);
            $errors = $this->validator->validate($decoded);
            Assertion::true(0 === count($errors), sprintf('Errors: %s', implode(', ', $errors)));
            Assertion::isInstanceOf($decoded, \stdClass::class, 'Unable to decode');
            $class = $decoded->type;
            Assertion::classExists($class, sprintf('Unsupported class \'%s\'', $class));
            $domainObject = $class::createFromJson($decoded);
        } catch (\Exception $e) {
            throw new OAuth2Exception(500, ['error' => OAuth2ResponseFactoryManager::ERROR_INTERNAL, 'error-description' => 'The server encountered and internal error.']);
        }

        return $domainObject;
    }

    public function toJson(DomainObjectInterface $domainObject): string
    {
        try {
            $jsonData = (object) $domainObject->jsonSerialize();
            $errors = $this->validator->validate($jsonData);
            Assertion::true(0 === count($errors), sprintf('Errors: %s', implode(', ', $errors)));
        } catch (\Exception $e) {
            throw new OAuth2Exception(500, ['error' => OAuth2ResponseFactoryManager::ERROR_INTERNAL, 'error-description' => 'The server encountered and internal error.']);
        }

        return $this->encoder->encode($jsonData);
    }
}

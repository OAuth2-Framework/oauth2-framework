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
use League\JsonGuard\Validator;
use League\JsonReference\Dereferencer;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use League\JsonReference\LoaderManager;

final class DomainConverter
{
    /**
     * @var Dereferencer
     */
    private $dereferencer;

    /**
     * @var int
     */
    private $options;

    /**
     * EventConverter constructor.
     */
    public function __construct()
    {
        $this->options = JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $domainUriLoader = new DomainUriLoader();
        $loaderManager = new LoaderManager(['https' => $domainUriLoader]);
        $this->dereferencer = new Dereferencer();
        $this->dereferencer->setLoaderManager($loaderManager);
    }

    public function fromJson(string $jsonData): DomainObjectInterface
    {
        try {
            $decoded = json_decode($jsonData, false, $this->options);
            Assertion::isInstanceOf($decoded, \stdClass::class, 'Unable to decode');
            Assertion::propertyExists($decoded, '$schema', 'The object is not a valid Json object from the domain.');
            $schema = $this->dereferencer->dereference($decoded->{'$schema'});

            $validator = new Validator($decoded, $schema);

            if ($validator->fails()) {
                throw new \InvalidArgumentException('The domain object cannot be verified with the selected schema.');
            }

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
            $schema = $this->dereferencer->dereference($jsonData->{'$schema'});

            $validator = new Validator($jsonData, $schema);

            if ($validator->fails()) {
                throw new \InvalidArgumentException('The domain object cannot be verified with the selected schema.');
            }
        } catch (\Exception $e) {
            throw new OAuth2Exception(500, ['error' => OAuth2ResponseFactoryManager::ERROR_INTERNAL, 'error-description' => 'The server encountered and internal error.']);
        }

        return json_encode($jsonData, $this->options);
    }
}

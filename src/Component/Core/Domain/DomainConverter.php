<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\Domain;

use League\JsonGuard\Validator;
use League\JsonReference\Dereferencer;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use League\JsonReference\LoaderManager;

class DomainConverter
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
     * DomainConverter constructor.
     *
     * @param DomainUriLoader $domainUriLoader
     */
    public function __construct(DomainUriLoader $domainUriLoader)
    {
        $this->options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $loaderManager = new LoaderManager(['https' => $domainUriLoader]);
        $this->dereferencer = new Dereferencer();
        $this->dereferencer->setLoaderManager($loaderManager);
    }

    /**
     * @param string $jsonData
     *
     * @return DomainObject
     *
     * @throws OAuth2Exception
     */
    public function fromJson(string $jsonData): DomainObject
    {
        try {
            $decoded = json_decode($jsonData, false, $this->options);
            if (!$decoded instanceof \stdClass) {
                throw new \RuntimeException('Unable to decode');
            }
            if (!property_exists($decoded, '$schema')) {
                throw new \InvalidArgumentException('The object is not a valid Json object from the domain.');
            }
            $schema = $this->dereferencer->dereference($decoded->{'$schema'});

            $validator = new Validator($decoded, $schema);

            if ($validator->fails()) {
                throw new \InvalidArgumentException('The domain object cannot be verified with the selected schema.');
            }

            $class = $decoded->type;
            if (!class_exists($class)) {
                throw new \InvalidArgumentException(sprintf('Unsupported class "%s"', $class));
            }
            $domainObject = $class::createFromJson($decoded);
        } catch (\Exception $e) {
            throw new OAuth2Exception(500, OAuth2Exception::ERROR_INTERNAL, 'The server encountered and internal error.', $e);
        }

        return $domainObject;
    }

    /**
     * @param DomainObject $domainObject
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    public function toJson(DomainObject $domainObject): string
    {
        try {
            $jsonData = (object) $domainObject->jsonSerialize();
            $schema = $this->dereferencer->dereference($jsonData->{'$schema'});

            $validator = new Validator($jsonData, $schema);

            if ($validator->fails()) {
                throw new \InvalidArgumentException('The domain object cannot be verified with the selected schema.');
            }
        } catch (\Exception $e) {
            throw new OAuth2Exception(500, OAuth2Exception::ERROR_INTERNAL, 'The server encountered and internal error.', $e);
        }

        return json_encode($jsonData, $this->options);
    }
}

<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Doctrine\Type;

use Assert\Assertion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use function Safe\sprintf;

final class ResourceOwnerIdType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return $value;
        }

        Assertion::isInstanceOf($value, ResourceOwnerId::class, 'Invalid object');

        return sprintf('%s:%s', \get_class($value), $value->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ResourceOwnerId
    {
        if (null === $value || $value instanceof ResourceOwnerId) {
            return $value;
        }

        $position = mb_strpos($value, ':');
        Assertion::integer($position, 'Invalid object');
        $class = mb_substr($value, 0, $position);
        $data = mb_substr($value, $position);

        return new $class($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'resource_owner_id';
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Doctrine\Type;

use Assert\Assertion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

final class ResourceOwnerIdType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return $value;
        }

        Assertion::isInstanceOf($value, ResourceOwnerId::class, 'Invalid object');

        return sprintf('%s:%s', $value::class, $value->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ResourceOwnerId
    {
        if ($value === null || $value instanceof ResourceOwnerId) {
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
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
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

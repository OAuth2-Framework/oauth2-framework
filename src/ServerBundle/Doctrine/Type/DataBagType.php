<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class DataBagType extends Type
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return serialize($value->all());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return new DataBag(unserialize($value));
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function getName()
    {
        return 'databag';
    }
}

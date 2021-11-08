<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\DataBag;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class DataBagTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function iCanCreateADataBag(): void
    {
        $data = DataBag::create([
            'foo' => 'bar',
        ]);
        $data->set('foo', 'BAR');

        static::assertInstanceOf(DataBag::class, $data);
        static::assertTrue($data->has('foo'));
        static::assertFalse($data->has('---'));
    }
}

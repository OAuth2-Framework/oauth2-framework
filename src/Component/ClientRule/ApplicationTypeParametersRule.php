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

namespace OAuth2Framework\Component\ClientRule;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ApplicationTypeParametersRule implements Rule
{
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if ($commandParameters->has('application_type')) {
            $application_type = $commandParameters->get('application_type');
            if (!\in_array($application_type, ['native', 'web'], true)) {
                throw new \InvalidArgumentException('The parameter "application_type" must be either "native" or "web".');
            }
            $validatedParameters->set('application_type', $application_type);
        } else {
            $validatedParameters->set('application_type', 'web');
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}

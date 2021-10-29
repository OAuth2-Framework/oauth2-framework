<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use function in_array;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ApplicationTypeParametersRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('application_type')) {
            $application_type = $commandParameters->get('application_type');
            if (! in_array($application_type, ['native', 'web'], true)) {
                throw new InvalidArgumentException(
                    'The parameter "application_type" must be either "native" or "web".'
                );
            }
            $validatedParameters->set('application_type', $application_type);
        } else {
            $validatedParameters->set('application_type', 'web');
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}

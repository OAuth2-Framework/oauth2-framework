<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\Rule;

use function in_array;
use InvalidArgumentException;
use function is_string;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

final class SubjectTypeRule implements Rule
{
    public function __construct(
        private UserInfo $userinfo
    ) {
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('subject_type')) {
            $subjectType = $commandParameters->get('subject_type');
            if (! is_string($subjectType)) {
                throw new InvalidArgumentException('Invalid parameter "subject_type". The value must be a string.');
            }
            $supported_types = ['public'];
            if ($this->userinfo->isPairwiseSubjectIdentifierSupported()) {
                $supported_types[] = 'pairwise';
            }

            if (! in_array($subjectType, $supported_types, true)) {
                throw new InvalidArgumentException(sprintf(
                    'The subject type "%s" is not supported. Please use one of the following value(s): %s',
                    $subjectType,
                    implode(', ', $supported_types)
                ));
            }
            $validatedParameters->set('subject_type', $subjectType);
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}

<?php declare(strict_types=1);

namespace Shibboleth;

return [
    'service_manager' => [
        'factories' => [
            'Omeka\AuthenticationService' => Service\AuthenticationServiceFactory::class,
        ],
    ],
];

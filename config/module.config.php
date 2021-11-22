<?php declare(strict_types=1);

namespace Shibboleth;

return [
    'controllers' => [
        'factories' => [
            'Omeka\Controller\Login' => Service\Controller\LoginControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Omeka\AuthenticationService' => Service\AuthenticationServiceFactory::class,
        ],
    ],
    // Copy this array into Omeka config/local.config.php and adapt it to your network.
    'shibboleth' => [
        // This option can be skipped.
        'config' => [
            // Set true if you want to use user accounts managed by Omeka.
            // This option requires a special login page.
            'shibboleth_password_login' => false,
            'shibboleth_url_logout' => '/Shibboleth.sso/Logout',
        ],
        'params' => [
            'attrPrefix' => '',
            'attrValueSeparator' => ';',
            'sessionIdVar' => 'Shib-Session-ID',
            'idpVar' => 'Shib-Identity-Provider',
            'appIdVar' => 'Shib-Application-ID',
            'authInstantVar' => 'Shib-Authentication-Instant',
            'authContextVar' => 'Shib-AuthnContext-Decl',
            'identityVar' => 'uid',
            'systemVarsInResult' => true,
            'attrMap' => [
                // Omeka S has a unique user name, but no public name.
                'uid' => 'name',
                'cn' => 'username',
                'mail' => 'email',
                'memberOf' => 'memberOf',
            ],
            'production' => [
                'roles' => [
                    'global_admin' => '(memberOf=*xxx-global_admin*)',
                    'site_admin' => '(memberOf=*xxx-site_admin*)',
                    'editor' => '(memberOf=*xxx-editor*)',
                    'reviewer' => '(memberOf=*xxx-reviewer*)',
                    'author' => '(memberOf=*xxx-author*)',
                    'researcher' => '(shibIdentityProvider=xxxx)',
                    // These roles require modules.
                    'guest' => '(memberOf=*xxx-guest*)',
                    'annotator' => '(memberOf=*xxx-annotator*)',
                ],
            ],
        ],
    ],
];

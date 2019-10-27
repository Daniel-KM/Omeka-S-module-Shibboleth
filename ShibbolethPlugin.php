<?php

/**
 * Shibboleth Plugin
 *
 * Allows to use Shibboleth to authenticate users.
 *
 * @author Vincent Pretet <Vincent.Pretet@univ-paris1.fr>
 * @author Daniel Berthereau <daniel.github@berthereau.net>
 * @license CeCILL v2.1 https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
 *
 * @package Omeka\Plugins\Shibboleth
 */
class ShibbolethPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = array(
        'define_routes',
    );

    /**
     * Override default user controller.
     *
     * @param array $args
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        $router->addRoute(
            'shibboleth',
            new Zend_Controller_Router_Route(
                'users/:action/:id',
                array(
                    'module' => 'shibboleth',
                    'controller' => 'users',
                    'action' => 'index',
                ),
                array(
                    'id' => '\d+',
                )
            )
        );
    }
}

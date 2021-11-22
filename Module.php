<?php declare(strict_types=1);

/**
 * Shibboleth Plugin
 *
 * Allows to use Shibboleth single sign-on to authenticate users.
 *
 * @author Vincent Pretet <Vincent.Pretet@univ-paris1.fr>
 * @author Daniel Berthereau <daniel.gitlab@berthereau.net>
 * @license CeCILL v2.1 https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
 *
 * @package Omeka\Plugins\Shibboleth
 */
class ShibbolethPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = [
        'install',
        'uninstall',
        'config',
        'config_form',
        'define_routes',
    ];

    /**
     * @var array This plugin's filters.
     */
    protected $_filters = [
        'admin_whitelist',
        'admin_navigation_users',
    ];

    /**
     * @var array This plugin's options.
     */
    protected $_options = [
        'shibboleth_display_email' => false,
    ];

    /**
     * Installs the plugin.
     */
    public function hookInstall(): void
    {
        if (!extension_loaded('ldap')) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the php extension "ldap" to be installed. See %s.',
                'https://gitlab.com/Daniel-KM/Omeka-plugin-Shibboleth#installation'
            ));
        }

        $filename = dirname(__FILE__) . '/vendor/autoload.php';
        if (!file_exists($filename)) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires to be installed with composer or extracted from a release. See %s.',
                'https://gitlab.com/Daniel-KM/Omeka-plugin-Shibboleth#installation'
            ));
        }

        require_once $filename;
        if (!class_exists('Net_LDAP2_Filter') || !class_exists('Net_LDAP2_Entry')) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the composer package "pear/neet_ldap2" to be installed. See %s.',
                'https://gitlab.com/Daniel-KM/Omeka-plugin-Shibboleth#installation'
            ));
        }

        $filename = APP_DIR . '/config/shibboleth.ini';
        if (!file_exists($filename)) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the file "shibboleth.ini" in the main config dir of Omeka to make the connection with the ldap. See %s.',
                'https://gitlab.com/Daniel-KM/Omeka-plugin-Shibboleth#installation'
            ));
        }

        $a = file_get_contents($filename);
        $b = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'shibboleth.ini');
        if ($a === $b) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the file "shibboleth.ini" to be adapted to your ldap. See %s.',
                'https://gitlab.com/Daniel-KM/Omeka-plugin-Shibboleth#installation'
            ));
        }

        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall(): void
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args): void
    {
        $view = get_view();
        echo $view->partial('plugins/shibboleth-config-form.php');
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args): void
    {
        $post = $args['post'];
        foreach ($this->_options as $optionKey => $defaultValue) {
            if (isset($post[$optionKey])) {
                if (is_array($defaultValue)) {
                    $post[$optionKey] = json_encode($post[$optionKey]);
                }
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    /**
     * Override default user controller.
     *
     * @param array $args
     */
    public function hookDefineRoutes($args): void
    {
        /** @var Zend_Controller_Router_Abstract $router */
        $router = $args['router'];

        $router
            ->addRoute(
                'shibboleth_id',
                new Zend_Controller_Router_Route(
                    'users/:action/:id',
                    [
                        'module' => 'shibboleth',
                        'controller' => 'users',
                        'action' => 'index',
                    ],
                    [
                        'id' => '\d+',
                    ]
                )
            )
            ->addRoute(
                'shibboleth',
                new Zend_Controller_Router_Route(
                    'users/:action',
                    [
                        'module' => 'shibboleth',
                        'controller' => 'users',
                        'action' => 'index',
                    ]
                )
            );
    }

    /**
     * @param array $adminWhitelist
     * @return array
     */
    public function filterAdminWhitelist($adminWhitelist)
    {
        $adminWhitelist[] = ['module' => 'shibboleth', 'controller' => 'users', 'action' => 'activate'];
        $adminWhitelist[] = ['module' => 'shibboleth', 'controller' => 'users', 'action' => 'login'];
        $adminWhitelist[] = ['module' => 'shibboleth', 'controller' => 'users', 'action' => 'forgot-password'];
        $adminWhitelist[] = ['module' => 'shibboleth', 'controller' => 'users', 'action' => 'notify'];
        $adminWhitelist[] = ['module' => 'shibboleth', 'controller' => 'users', 'action' => 'error'];
        return $adminWhitelist;
    }

    /**
     * @param array $navLinks
     * @param array $args
     * @return array
     */
    public function filterAdminNavigationUsers($navLinks, $args)
    {
        foreach ($navLinks as $key => $navLink) {
            if ($navLink['privilege'] === 'change-password') {
                unset($navLink[$key]);
            }
        }
        return $navLinks;
    }
}

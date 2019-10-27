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
        'install',
        'uninstall',
        'config',
        'config_form',
        'define_routes',
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        'shibboleth_display_email' => false,
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        if (!extension_loaded('ldap') || !class_exists('Net_LDAP2_Filter') || !class_exists('Net_LDAP2_Entry')) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the php extension "ldap" to be installed. See %sReadme%s.',
                '<a href="https://github.com/Daniel-KM/Omeka-plugin-Shibboleth#installation">',
                '</a>'
            ));
        }

        $filename = APP_DIR . '/config/shibboleth.ini';
        if (!file_exists($filename)) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the file "roles.ini" in the main config dir of Omeka to map roles. See %sReadme%s.',
                '<a href="https://github.com/Daniel-KM/Omeka-plugin-Shibboleth#installation">',
                '</a>'
            ));
        }

        $a = file_get_contents($filename);
        $b = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'shibboleth.ini');
        if ($a === $b) {
            throw new Omeka_Plugin_Installer_Exception(__(
                'The plugin Shibboleth requires the file "shibboleth.ini" to be adapted to your ldap. See %sReadme%s.',
                '<a href="https://github.com/Daniel-KM/Omeka-plugin-Shibboleth#installation">',
                '</a>'
            ));
        }

        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial('plugins/shibboleth-config-form.php');
    }

    /**
     * Saves plugin configuration page.
     *
     * @param array Options set in the config form.
     */
    public function hookConfig($args)
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

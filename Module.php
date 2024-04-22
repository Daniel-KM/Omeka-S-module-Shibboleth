<?php declare(strict_types=1);

/**
 * Shibboleth module
 *
 * Allows to use Shibboleth single sign-on to authenticate users.
 *
 * @author Vincent Pretet <Vincent.Pretet@univ-paris1.fr>
 * @author Daniel Berthereau <daniel.gitlab@berthereau.net>
 * @license CeCILL v2.1 https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software’s author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user’s attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software’s suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */
namespace Shibboleth;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\Stdlib\PsrMessage;
use Common\TraitModule;
use Laminas\ModuleManager\ModuleManager;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;

/**
 * Shibboleth.
 *
 * @copyright Vincent Pretet, 2016-2017 for Université Paris 1 - Panthéon-Sorbonne
 * @copyright Daniel Berthereau, 2019-2024
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    public function init(ModuleManager $moduleManager): void
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $plugins = $services->get('ControllerPluginManager');
        $translate = $plugins->get('translate');
        $translator = $services->get('MvcTranslator');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.56')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.56'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }

        if (!extension_loaded('ldap')) {
            throw new ModuleCannotInstallException((string) new PsrMessage(
                'The module Shibboleth requires the php extension "ldap" to be installed. See {link}readme{link_end}.', // @translate
                ['link' => '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation" rel="noopener">', 'link_end' => '</a>']
            ));
        }

        $filename = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($filename)) {
            throw new ModuleCannotInstallException((string) new PsrMessage(
                'The module Shibboleth requires to be installed with composer or extracted from a release. See {link}readme{link_end}.', // @translate
                ['link' => '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation" rel="noopener">', 'link_end' => '</a>']
            ));
        }

        require_once $filename;
        if (!class_exists('Net_LDAP2_Filter') || !class_exists('Net_LDAP2_Entry')) {
            throw new ModuleCannotInstallException((string) new PsrMessage(
                'The module Shibboleth requires the composer package "pear/neet_ldap2" to be installed. See {link}readme{link_end}.', // @translate
                ['link' => '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation" rel="noopener">', 'link_end' => '</a>']
            ));
        }

        // The local config should be different from the default config.
        $currentConfig = include OMEKA_PATH . '/config/local.config.php';
        $defaultConfig = include __DIR__ . '/config/module.config.php';
        if (empty($currentConfig['shibboleth']['params'])
            || $currentConfig['shibboleth']['params'] === $defaultConfig['shibboleth']['params']
        ) {
            throw new ModuleCannotInstallException((string) new PsrMessage(
                'The module Shibboleth requires the Omeka config in "config/local.config.php" to be adapted to your ldap. See {link}readme{link_end}.', // @translate
                ['link' => '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation" rel="noopener">', 'link_end' => '</a>']
            ));
        }
    }
}

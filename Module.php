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

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Laminas\ModuleManager\ModuleManager;
use Omeka\Module\Exception\ModuleCannotInstallException;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    public function init(ModuleManager $moduleManager): void
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    protected function preInstall(): void
    {
        if (!extension_loaded('ldap')) {
            throw new ModuleCannotInstallException((string) new \Omeka\Stdlib\Message(
                'The module Shibboleth requires the php extension "ldap" to be installed. See %s.', // @translate
                'https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation'
            ));
        }

        $filename = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($filename)) {
            throw new ModuleCannotInstallException((string) new \Omeka\Stdlib\Message(
                'The module Shibboleth requires to be installed with composer or extracted from a release. See %s.',
                'https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation'
            ));
        }

        require_once $filename;
        if (!class_exists('Net_LDAP2_Filter') || !class_exists('Net_LDAP2_Entry')) {
            throw new ModuleCannotInstallException((string) new \Omeka\Stdlib\Message(
                'The module Shibboleth requires the composer package "pear/neet_ldap2" to be installed. See %s.', // @translate
                'https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation'
            ));
        }

        $filename = OMEKA_PATH . '/config/shibboleth.ini';
        if (!file_exists($filename)) {
            throw new ModuleCannotInstallException((string) new \Omeka\Stdlib\Message(
                'The module Shibboleth requires the file "shibboleth.ini" in the main config dir of Omeka to make the connection with the ldap. See %s.', // @translate
                'https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation'
            ));
        }

        $a = file_get_contents($filename);
        $b = file_get_contents(__DIR__ . '/config/shibboleth.ini');
        if ($a === $b) {
            throw new ModuleCannotInstallException((string) new \Omeka\Stdlib\Message(
                'The module Shibboleth requires the file "shibboleth.ini" to be adapted to your ldap. See %s.', // @translate
                'https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth#installation'
            ));
        }
    }
}

Shibboleth (plugin for Omeka Classic)
=====================================

[Shibboleth] is a plugin for [Omeka Classic] that allows to use Shibboleth to
authenticate users.


Installation
------------

The plugin uses the php-extension `php-ldap`, so it must be installed on your
server. Furthermore, it uses [composer] too to manage a dependency. So use the
release zip to install it, or use and init the source.

* From the zip

Download the last release [Shibboleth.zip] from the list of releases (the master
does not contain the dependency), and uncompress it in the `plugins` directory.

* From the source and for development:

If the plugin was installed from the source, rename the name of the folder of
the plugin to `Shibboleth`, and go to the root plugin, and run:

```
    composer install
```

The next times:

```
    composer update
```

Then install it like any other Omeka plugin.

See general end user documentation for [Installing a plugin] and follow the
config instructions.


Usage
-----

Copy the file `shibboleth.ini` from the root of the plugin into the folder `application/config/`
of Omeka, then update this config file. In particular the attribute map may be
modified to get the good username and display name. The roles specified inside
your Ldap must be mapped to the ones uses by Omeka too. Generally, just replace
the `xxx` by the ones used in your ldap manager.

Before moving into production, check the security and check the rights of each
roles.

Don’t forget to enable Shibboleth in the param of the web server (Apache here),
according to your own configuration:
```
    <Location />
        AuthType shibboleth
        ShibRequireSession Off
        require shibboleth
    </Location>

    <Location /admin>
        AuthType shibboleth
        ShibRequireSession On
        ShibUseHeaders On
        ShibRequestSetting requireSession 1
        Require valid-user
    </Location>

    <Location /Shibboleth.sso>
    #   Order Deny,Allow
    #   Allow from all
        SetHandler shib
    </Location>
```


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI]. It contains some parts from the
library [ZfcShib], published under [BSD], and the library [PEAR Net_LDAP2],
published under [LGPL v3.0].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Ivan Novakov, 2013 (see [ZfcShib])
* Copyright Vincent Pretet, 2016-2017 for Université Paris 1 - Panthéon-Sorbonne
* Copyright Daniel Berthereau, 2019 (see [Daniel-KM])

First developed for the [Nubis] of [Université Paris 1 - Panthéon-Sorbonne].


[Shibboleth]: https://github.com/Daniel-KM/Omeka-plugin-Shibboleth
[Omeka Classic]: https://omeka.org/classic
[composer]: https://getcomposer.org
[Shibboleth.zip]: https://github.com/Daniel-KM/Omeka-plugin-Shibboleth/releases
[Installing a plugin]: https://omeka.org/classic/docs/Admin/Adding_and_Managing_Plugins
[plugin issues]: https://github.com/Daniel-KM/Omeka-plugin-Shibboleth/issues
[PEAR Net_LDAP2]: https://pear.php.net/package/Net_LDAP2
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[ZfcShib]: https://github.com/shuyg/ZfcShib
[BSD]: http://debug.cz/license/bsd-3-clause
[LGPL v3.0]: https://github.com/pear/Net_LDAP2/raw/master/LICENSE
[Nubis]: https://nubis.univ-paris1.fr
[Université Paris 1 - Panthéon-Sorbonne]: https://www.pantheonsorbonne.fr
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"

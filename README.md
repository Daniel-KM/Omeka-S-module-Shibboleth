Shibboleth (module for Omeka S)
===============================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Shibboleth] is a module for [Omeka S] that allows to use [Shibboleth single sign-on services]
to authenticate users.


Installation
------------

The module uses the php-extension [php-ldap], so it must be installed on your
server, generally a package named "php-ldap" in most common Linux distributions.

Furthermore, it uses [composer] too to manage a dependency. So use the
release zip to install it, or use and init the source.

* From the zip

Download the last release [Shibboleth.zip] from the list of releases (the master
does not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development:

If the module was installed from the source, rename the name of the folder of
the module to `Shibboleth`, and go to the root of the module, and run:

```sh
composer install --no-dev
```

Before enabling the module in admin interface, the config must be updated.

Then install it like any other Omeka module.

See general end user documentation for [installing a module].


Usage
-----

Copy the array [shibboleth] from the config of the module to the local config
of Omeka (file `config/local.config.php`) and adapt it to your network config.

In particular, the attribute map may be modified to get the good username. The
roles specified inside your Ldap must be mapped to the ones uses by Omeka too.
Generally, just replace the `xxx` by the ones used in your ldap manager. The
key `identityVar` may be updated to `uid` if the service doesn't provide the
`email`. The only required value is the `email`.

You can add user settings too, for example the institution or keys for the
module [User Profile] (`'supannEtablissement' => 'userprofile_institution_id'`).
All keys starting with `userprofile_` or in the specified list `user_settings`
will be stored when the user will be created (no update later).

Before moving into production, check the security and check the rights of each
role.

Don’t forget to enable Shibboleth in the param of the web server (Apache here),
according to your own configuration:

```
# Location / should be first in order to manage specific settings for sub-paths.
<Location />
    AuthType shibboleth
    ShibRequireSession Off
    # ShibRequestSetting entityIDSelf https://example.com/Shibboleth.sso
    Require shibboleth
</Location>

# When using module Guest, you may add /s/my-site/guest like /admin.
<Location /admin>
    AuthType shibboleth
    ShibRequireSession On
    ShibUseHeaders On
    ShibRequestSetting requireSession 1
    # ShibRequestSetting entityIDSelf https://example.com/Shibboleth.sso
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

See online issues on the [module issues] page on GitLab.


License
-------

# Module

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

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

# Libraries

This module contains some parts from the library [ZfcShib], published under [BSD],
and the library [PEAR Net_LDAP2], published under [LGPL v3.0].


Copyright
---------

* Copyright Ivan Novakov, 2013 (see [ZfcShib])
* Copyright Vincent Pretet, 2016-2017 for Université Paris 1 - Panthéon-Sorbonne
* Copyright Daniel Berthereau, 2019-2021 (see [Daniel-KM])

First developed for the [Nubis] of [Université Paris 1 - Panthéon-Sorbonne].


[Shibboleth]: https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth
[Omeka S]: https://omeka.org/s
[Shibboleth single sign-on services]: https://www.shibboleth.net
[php-ldap]: https://www.php.net/manual/fr/book.ldap.php
[composer]: https://getcomposer.org
[Shibboleth.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth/-/releases
[installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[shibboleth]: https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth/-/blob/master/config/module.config.php#L16-56
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Shibboleth/-/issues
[PEAR Net_LDAP2]: https://pear.php.net/package/Net_LDAP2
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[ZfcShib]: https://github.com/ivan-novakov/ZfcShib
[BSD]: http://debug.cz/license/bsd-3-clause
[LGPL v3.0]: https://github.com/pear/Net_LDAP2/raw/master/LICENSE
[Nubis]: https://nubis.univ-paris1.fr
[Université Paris 1 - Panthéon-Sorbonne]: https://www.pantheonsorbonne.fr
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"

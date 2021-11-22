<?php
/**
 * Shibboleth authentication adapter for the Zend Framework.
 *
 * Configuration options:
 * - attrPrefix (string, default '') - apply prefix when retrieving Shibboleth attributes
 * - attrValueSeparator (string, default ';') - value separator for multi-value attributes
 * - sessionIdVar (string, default 'Shib-Session-ID') - env value - containing the Shibboleth session
 * - idpVar (string, default 'Shib-Identity-Provider') - env value - the entity ID of the IdP
 * - appIdVar (string, default 'Shib-Application-ID') - env value - the application ID as configured in shibboleth2.xml
 * - authInstantVar (string, default 'Shib-Authentication-Instant') - env value - the time of authentication
 * - authContextVar (string, default 'Shib-AuthnContext-Decl') - env value - authentication context at the IdP
 * - identityVar (string, default 'uid') - the name of the mapped user attribute containing the user identity
 * - systemVarsInResult (boolean, default true) - true to add env var to the returned user attributes upon successful
 * authentication
 * - attrMap (array, default - see code) - array, that maps Shibboleth attribute names into internal variable names
 *
 * Usage:
 * ---------------------------------
 * $auth = Zend_Auth::getInstance();
 *
 * $authAdapter = new Zext_Auth_Adapter_Shibboleth(array(
 *   'identityVar' => 'id',
 *   'attrMap' => array(
 *       'uid' => 'id',
 *       'cn' => 'name',
 *       'mail' => 'email'
 *   )
 * ));
 *
 * $result = $auth->authenticate($authAdapter);
 * ---------------------------------
 *
 *
 * @author Ivan Novakov <ivan.novakov@debug.cz>
 * @license http://debug.cz/license/freebsd    FreeBSD License
 */
// require_once 'Net/LDAP2/Entry.php';
// require_once 'Net/LDAP2/Filter.php';

require_once dirname(__FILE__) . '/../../../vendor/autoload.php';

class ShibbolethAdapter implements Zend_Auth_Adapter_Interface
{
    /**
     * The configuration object.
     *
     * @var Zend_Config
     */
    protected $_config = null;

    /**
     * Array of default options.
     *
     * @var array
     */
    protected $_defaultOptions = array(
        'attrPrefix' => '',
        'attrValueSeparator' => ';',
        'sessionIdVar' => 'Shib-Session-ID',
        'idpVar' => 'Shib-Identity-Provider',
        'appIdVar' => 'Shib-Application-ID',
        'authInstantVar' => 'Shib-Authentication-Instant',
        'authContextVar' => 'Shib-AuthnContext-Decl',
        'identityVar' => 'uid',
        'systemVarsInResult' => true,
        'attrMap' => array(
            'uid' => 'username',
            'cn' => 'name',
            'mail' => 'email',
        ),
        'production' => array(
            'roles' => array(
                'super' => '',
                'admin' => '',
                'contributor' => '',
                'researcher' => '',
            ),
        ),
        // TODO Roles in development is currently not managed.
        'development' => array(
            'roles' => array(
                'super' => '',
                'admin' => '',
                'contributor' => '',
                'researcher' => '',
            ),
        ),
    );

    /**
     * System variable keys.
     *
     * @var array
     */
    protected $_systemVars = array(
        'idpVar',
        'appIdpVar',
        'authIdVar',
        'authInstantVar',
        'authContextVar',
    );

    /**
     * Array containing environment variables.
     *
     * @var array
     */
    protected $_env = array();

    /**
     * Constructor.
     *
     * @param array $config
     * @param array $env
     */
    public function __construct(array $config = array(), array $env = null)
    {
        $this->_config = new Zend_Config($config + $this->_defaultOptions);
        if (!$env) {
            $env = $_SERVER;
        }
        $this->_env = $env;
    }

    /**
     * Implementation of the authenticate() call defineed by the adapter interface.
     *
     * @see Zend_Auth_Adapter_Interface::authenticate()
     */
    public function authenticate()
    {
        /*
         * If there is no Shibboleth session, the authentication is impossible.
         */
        if (! $this->_isSession()) {
            return $this->_failureResult(array(
                'no_session',
            ));
        }

        /*
         * Get attributes from the Shibboleth session.
         */
        $userAttrs = $this->_extractAttributes();

        /*
         * Check if the "identityVar" is present. If not, the authentication cannot be completed.
         */

        if (! isset($userAttrs[$this->_config->identityVar])) {
            return $this->_failureResult(array(
                'no_identity',
            ), Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND);
        }

        /*
         * If the "identityVar" variable contains more than one value, throw an error.
         */
        if (is_array($userAttrs[$this->_config->identityVar])) {
            return $this->_failureResult(array(
                'multiple_id_attr_value',
            ), Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS);
        }

        // Find user by the specified identifier (generally uid or email).
        $username = $userAttrs[$this->_config->identityVar];
        $user = get_db()->getTable('User')->findBySql(
            'username = ?',
            array($username),
            true
        );

        $role = $this->_updateRole();

        if ($user) {
            // If user was found update his role in all cases.
            if ($role) {
                $user->role = $role;
            }
            // Deactivate user if already existing but does not have a role anymore.
            else {
                $user->active = false;
            }
            $user->save(false);
        }
        // Else create and activate a user, if there is a role.
        elseif ($role) {
            $user = new User();
            $user->username = $username;
            $user->name = $userAttrs['name'];
            $user->email = $userAttrs['email'];
            $user->role = $role;
            $user->active = true;
            $user->save(false);
        }

        // If the user was found and active, return success.
        if ($user && $user->active) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS,
                $user->id
            );
        }

        // Return that the user does not have an active account.
        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
            $username,
            array(__('User matching "%s" not found.', $username))
        );
    }

    /**
     * Returns a failure Zend_Auth_Result.
     *
     * @param array $messages
     * @param int $code
     * @return Zend_Auth_Result
     */
    protected function _failureResult(array $messages, $code = Zend_Auth_Result::FAILURE)
    {
        return new Zend_Auth_Result($code, null, $messages);
    }

    /**
     * Returns a successful Zend_Auth_Result.
     *
     * @param array $userAttrs
     * @return Zend_Auth_Result
     */
    protected function _successResult(array $userAttrs)
    {
        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $userAttrs);
    }

    /**
     * Parses Shibboleth attributes and maps them into an array.
     *
     * @return array
     */
    protected function _extractAttributes()
    {
        $attrs = array();

        /*
         * Use the "attrMap" configuration parameter to map attributes.
         */
        foreach ($this->_config->attrMap->toArray() as $srcIndex => $dstIndex) {
            if ($value = $this->_getEnv($srcIndex)) {
                /*
                 * Some Shibboleth attributes have multiple values, serialized.
                 * The only way to find that out is to try to split the values by separator.
                 */
                $values = explode($this->_config->attrValueSeparator, $value);
                if (count($values) > 1) {
                    $attrs[$dstIndex] = $values;
                } else {
                    $attrs[$dstIndex] = $value;
                }
            }
        }

        /*
         * Add relevant environment variables to the array.
         */
        if ($this->_config->systemVarsInResult) {
            foreach ($this->_systemVars as $systemVarName) {
                $envVarName = $this->_config->get($systemVarName);
                if ($envVarName && ($value = $this->_getEnv($envVarName))) {
                    $attrs['env'][$envVarName] = $value;
                }
            }
        }

        return $attrs;
    }

    /**
     * Returns true, if a Shibboleth session exists.
     *
     * @return bool
     */
    protected function _isSession()
    {
        return ($this->_getSession());
    }

    /**
     * Returns the Shibboleth session ID, if present. Otherwise returns NULL.
     *
     * @return string|NULL
     */
    protected function _getSession()
    {
        return $this->_getEnv($this->_config->sessionIdVar);
    }
    /**
     * Returns the corresponding environment variable value.
     *
     * @param string $index
     * @return string|NULL
     */
    protected function _getEnv($index)
    {
        $index = $this->_config->attrPrefix . $index;

        if (isset($this->_env[$index])) {
            return $this->_env[$index];
        }

        return null;
    }

    protected function _updateRole()
    {
        $roles = $this->_config->production->roles;

        $roleSuper = Net_LDAP2_Filter::parse($roles->super);
        $roleAdmin = Net_LDAP2_Filter::parse($roles->admin);
        $roleContributor = Net_LDAP2_Filter::parse($roles->contributor);
        $roleResearcher = Net_LDAP2_Filter::parse($roles->researcher);

        $userAttrs = $this->_extractAttributes();
        $entry = Net_LDAP2_Entry::createFresh('', $userAttrs);

        $role = '';

        if (is_a($roleSuper, 'Net_LDAP2_Filter') && $roleSuper->matches($entry)) {
            $role = 'super';
        } elseif (is_a($roleAdmin, 'Net_LDAP2_Filter') && $roleAdmin->matches($entry)) {
            $role = 'admin';
        } elseif (is_a($roleContributor, 'Net_LDAP2_Filter') && $roleContributor->matches($entry)) {
            $role = 'contributor';
        } elseif (is_a($roleResearcher, 'Net_LDAP2_Filter') && $roleResearcher->matches($entry)) {
            $role = 'researcher';
        }

        return $role;
    }
}

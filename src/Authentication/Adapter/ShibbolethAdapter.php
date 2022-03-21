<?php declare(strict_types=1);

namespace Shibboleth\Authentication\Adapter;

/*
 * Shibboleth authentication adapter for Laminas (ex-Zend Framework).
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
 * @author Daniel Berthereau <daniel.git@berthereau.net>
 * @license http://debug.cz/license/freebsd    FreeBSD License
 */
// require_once 'Net/LDAP2/Entry.php';
// require_once 'Net/LDAP2/Filter.php';

use Doctrine\ORM\EntityManager;
use Laminas\Authentication\Adapter\AbstractAdapter;
use Laminas\Authentication\Result;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Log\Logger;
use Omeka\Entity\User;
use Omeka\Stdlib\Message;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ShibbolethAdapter extends AbstractAdapter
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * The configuration array.
     */
    protected $config = [];

    /**
     * Default config.
     *
     * @var array
     */
    protected $defaultConfig = [
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
                'global_admin' => '',
                'site_admin' => '',
                'editor' => '',
                'reviewer' => '',
                'author' => '',
                'researcher' => '',
                // These roles require modules.
                'guest' => '',
                'annotator' => '',
            ],
        ],
        // TODO Roles in development are currently not managed.
        'development' => [
            'roles' => [
                'global_admin' => '',
                'site_admin' => '',
                'editor' => '',
                'reviewer' => '',
                'author' => '',
                'researcher' => '',
                'guest' => '',
                'annotator' => '',
            ],
        ],
    ];

    /**
     * System variable keys.
     *
     * @var array
     */
    protected $systemVars = [
        'idpVar',
        'appIdpVar',
        'authIdVar',
        'authInstantVar',
        'authContextVar',
    ];

    /**
     * Array containing environment variables.
     *
     * @var array
     */
    protected $env = [];

    public function __construct(
        EntityManager $entityManager,
        Logger $logger = null,
        array $config = [],
        array $env = null
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->config = $config + $this->defaultConfig;
        $this->env = $env ?: $_SERVER;
    }

    /**
     * @inheritdoc \Laminas\Authentication\Adapter\AdapterInterface::authenticate()
     */
    public function authenticate()
    {
        // If there is no Shibboleth session, the authentication is impossible.
        if (!$this->isSession()) {
            return new Result(
                Result::FAILURE_UNCATEGORIZED,
                null,
                ['no_session']
            );
        }

        // Get attributes from the Shibboleth session.
        $userAttrs = $this->extractAttributes();

        // Check if the "identityVar" is present. If not, the authentication
        // cannot be completed.
        if (!isset($userAttrs[$this->config['identityVar']])) {
            // This is a bug in the config, so log it.
            $this->logger->err(new Message(
                'Error in the config: the identityVar "%s" is not found in extracted attributes.', // @translate
                $this->config['identityVar']
            ));
            return $this->failureResult(
                ['no_identity'],
                Result::FAILURE_IDENTITY_NOT_FOUND
            );
        }

        // If the "identityVar" variable contains more than one value, throw an error.
        if (is_array($userAttrs[$this->config['identityVar']])) {
            $this->logger->err(new Message(
                'Error in the config: the identityVar "%s" has multiple values.', // @translate
                $this->config['identityVar']
            ));
            return $this->failureResult(
                ['multiple_id_attr_value'],
                Result::FAILURE_IDENTITY_AMBIGUOUS
            );
        }

        // Find user by the specified identifier (generally uid or email).
        // In Omeka S, there is no username, only an email and a display name.
        $email = $userAttrs[$this->config['identityVar']];
        $user = $this->entityManager->getRepository(\Omeka\Entity\User::class)
            ->findOneBy([
                'email' => $email,
            ]);

        $role = $this->ldapRoleToLocalRole();

        if ($user) {
            // If a user was found, update the role in all cases.
            if ($role) {
                $user->setRole($role);
            }
            // Deactivate user if already existing but does not have a role anymore.
            else {
                $user->setIsActive(false);
            }
        }
        // Else create and activate a user, if there is a role.
        elseif ($role) {
            $user = new User();
            $user->setName($userAttrs['name'] ?? $email);
            $user->setEmail($email);
            $user->setRole($role);
            $user->setIsActive(true);
        }

        if ($user) {
            // The entity manager didn't use rights.
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // If the user was found and active, return success.
        if ($user && $user->isActive()) {
            return new Result(
                Result::SUCCESS,
                $user
            );
        }

        // Return that the user does not have an active account.
        return $this->failureResult(
            // TODO Implement failure message translation.
            [new Message(
                'User matching "%s" not found.', // @translate
                $email
            )],
            Result::FAILURE_IDENTITY_NOT_FOUND
        );
    }

    /**
     * Returns a failure Result.
     */
    protected function failureResult(array $messages, int $code = Result::FAILURE): Result
    {
        if ($this->logger) {
            $ip = (new RemoteAddress())->getIpAddress();
            $this->logger->info(new Message(
                'Failed login attempt from ip "%s".', // @translate
                $ip
            ));
        }
        return new Result($code, null, $messages);
    }

    /**
     * Parses Shibboleth attributes and maps them into an array.
     */
    protected function extractAttributes(): array
    {
        $attrs = [];

        // Use the "attrMap" configuration parameter to map attributes.
        foreach ($this->config['attrMap'] as $srcIndex => $dstIndex) {
            if ($value = $this->getEnv($srcIndex)) {
                // Some Shibboleth attributes have multiple values, serialized.
                // The only way to find that out is to try to split the values by separator.
                $values = explode($this->config['attrValueSeparator'], $value);
                $attrs[$dstIndex] = count($values) <= 1
                    ? $value
                    : $values;
            }
        }

        // Add relevant environment variables to the array.
        if ($this->config['systemVarsInResult']) {
            foreach ($this->systemVars as $systemVarName) {
                $envVarName = $this->config[$systemVarName] ?? null;
                if ($envVarName && ($value = $this->getEnv($envVarName))) {
                    $attrs['env'][$envVarName] = $value;
                }
            }
        }

        return $attrs;
    }

    /**
     * Returns true, if a Shibboleth session exists.
     */
    protected function isSession(): bool
    {
        return (bool) $this->getSession();
    }

    /**
     * Returns the Shibboleth session ID, if present. Otherwise returns NULL.
     */
    protected function getSession(): ?string
    {
        $value = $this->getEnv($this->config['sessionIdVar']);
        return $value ? (string) $value : null;
    }

    /**
     * Returns the corresponding environment variable value.
     */
    protected function getEnv(string $index): ?string
    {
        $index = $this->config['attrPrefix'] . $index;
        return isset($this->env[$index])
            ? (string) $this->env[$index]
            : null;
    }

    /**
     */
    protected function ldapRoleToLocalRole(): ?string
    {
        $userAttrs = $this->extractAttributes();
        $entry = \Net_LDAP2_Entry::createFresh('', $userAttrs);

        foreach ($this->config['production']['roles'] as $role => $ldapRole) {
            $roleUser = \Net_LDAP2_Filter::parse($ldapRole);
            if ($roleUser
                && is_a($roleUser, \Net_LDAP2_Filter::class)
                && $roleUser->matches($entry)
            ) {
                return $role;
            }
        }

        return null;
    }
}

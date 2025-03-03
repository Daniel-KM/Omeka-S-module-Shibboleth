<?php declare(strict_types=1);

namespace Shibboleth\Service;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\Adapter\Callback;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Authentication\Storage\Session;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Authentication\Adapter\KeyAdapter;
use Omeka\Authentication\Storage\DoctrineWrapper;
use Shibboleth\Authentication\Adapter\ShibbolethAdapter;
use Shibboleth\Authentication\Adapter\ShibbolethOrPasswordAdapter;

/**
 * Authentication service factory.
 */
class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create the authentication service.
     *
     * @return AuthenticationService
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $entityManager = $services->get('Omeka\EntityManager');
        $status = $services->get('Omeka\Status');

        // Skip auth retrieval entirely if we're installing or migrating.
        if (!$status->isInstalled() ||
            ($status->needsVersionUpdate() && $status->needsMigration())
        ) {
            $storage = new NonPersistent;
            $adapter = new Callback(fn () => null);
        } else {
            $userRepository = $entityManager->getRepository('Omeka\Entity\User');
            if ($status->isKeyauthRequest()) {
                // Authenticate using key for requests that require key authentication.
                $keyRepository = $entityManager->getRepository('Omeka\Entity\ApiKey');
                $storage = new DoctrineWrapper(new NonPersistent, $userRepository);
                $adapter = new KeyAdapter($keyRepository, $entityManager);
            } else {
                // Authenticate using user/password for all other requests.
                $storage = new DoctrineWrapper(new Session, $userRepository);
                $config = $services->get('Config');
                $logger = $services->get('Omeka\Logger');
                $adapter = empty($config['shibboleth']['config']['shibboleth_password_login'])
                    ? new ShibbolethAdapter($entityManager, $logger, $config['shibboleth']['params'], null)
                    : new ShibbolethOrPasswordAdapter($entityManager, $logger, $config['shibboleth']['params'], null);
            }
        }

        return new AuthenticationService($storage, $adapter);
    }
}

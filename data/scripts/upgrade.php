<?php declare(strict_types=1);

namespace Shibboleth;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
// $settings = $services->get('Omeka\Settings');
// $config = require dirname(__DIR__, 2) . '/config/module.config.php';
// $connection = $services->get('Omeka\Connection');
// $entityManager = $services->get('Omeka\EntityManager');
// $plugins = $services->get('ControllerPluginManager');
// $api = $plugins->get('api');

if (version_compare((string) $oldVersion, '3.3.0.6', '<')) {
    $messenger = new Messenger();
    $message = new Message(
        'New options were added in the config to define a default role, to update the role automatically on login, and to store user settings on user creation.' // @translate
    );
    $messenger->addSuccess($message);
}

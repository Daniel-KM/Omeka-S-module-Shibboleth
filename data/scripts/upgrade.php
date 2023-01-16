<?php declare(strict_types=1);

namespace Shibboleth;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (version_compare((string) $oldVersion, '3.3.0.7', '<')) {
    $message = new Message(
        'New options were added in the config to define a default role, to update the role automatically on login, and to store user settings on user creation.' // @translate
    );
    $messenger->addSuccess($message);
    if (version_compare((string) $oldVersion, '3.3.0.6') === 0) {
        $message = new Message(
            'A fix has been done to store data on user creation to manage static and dynamic values.' // @translate
        );
        $messenger->addSuccess($message);
    }
}

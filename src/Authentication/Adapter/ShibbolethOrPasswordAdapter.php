<?php declare(strict_types=1);

namespace Shibboleth\Authentication\Adapter;

use Omeka\Authentication\Adapter\PasswordAdapter;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ShibbolethOrPasswordAdapter extends ShibbolethAdapter
{
    /**
     * @inheritdoc Shibboleth\Authentication\Adapter\ShibbolethAdapter::authenticate()
     */
    public function authenticate()
    {
        $result = parent::authenticate();
        if ($result->isValid()) {
            return $result;
        }

        $passwordAdapter = new PasswordAdapter($this->entityManager->getRepository(\Omeka\Entity\User::class));
        $passwordAdapter->setIdentity($this->identity);
        $passwordAdapter->setCredential($this->credential);
        return $passwordAdapter->authenticate();
    }
}

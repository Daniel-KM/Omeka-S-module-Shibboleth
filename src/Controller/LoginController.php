<?php declare(strict_types=1);

namespace Shibboleth\Controller;

use Laminas\Session\Container;

class LoginController extends \Omeka\Controller\LoginController
{
    public function loginAction()
    {
        $user = $this->auth->getIdentity();
        if ($user) {
            return $user->getRole() === 'guest'
                ? $this->redirect()->toRoute('top')
                : $this->redirect()->toRoute('admin');
        }

        $sessionManager = Container::getDefaultManager();
        $sessionManager->regenerateId();

        $result = $this->auth->authenticate();
        if (!$result->isValid()) {
            $message = $result->getMessages();
            if ($message) {
                $message = is_array($message) ? reset($message) : $message;
                $this->messenger()->addError(sprintf('Error during authentication: %s', $message)); // @translate
            } else {
                $this->messenger()->addError('Error during authentication'); // @translate
            }
            $this->messenger()->addError('The resource you were trying to access is restricted'); // @translate
            return $this->redirect()->toRoute('top');
        }

        $user = $this->auth->getIdentity();

        $this->messenger()->addSuccess('Successfully logged in'); // @translate
        $eventManager = $this->getEventManager();
        $eventManager->trigger('user.login', $user);
        $session = $sessionManager->getStorage();

        if ($redirectUrl = $session->offsetGet('redirect_url')) {
            return $this->redirect()->toUrl($redirectUrl);
        }

        return $user->getRole() === 'guest'
            ? $this->redirect()->toRoute('top')
            : $this->redirect()->toRoute('admin');
    }

    public function logoutAction()
    {
        $this->auth->clearIdentity();

        $sessionManager = Container::getDefaultManager();

        $eventManager = $this->getEventManager();
        $eventManager->trigger('user.logout');

        $sessionManager->destroy();

        $this->messenger()->addSuccess('Successfully logged out'); // @translate

        $shibbolethLogout = $this->settings()->get('shibboleth_url_logout') ?: '/Shibboleth.sso/Logout';
        $url = $shibbolethLogout . '?return=' . rawurlencode($this->viewHelpers()->get('url')('top'));

        return $this->redirect()->toUrl($url);
    }

    public function createPasswordAction()
    {
        // Disable changePasswordAction as password is managed by Shibboleth.
        return $this->notFoundAction();
    }

    public function forgotPasswordAction()
    {
        // Disable changePasswordAction as password is managed by Shibboleth.
        return $this->notFoundAction();
    }
}

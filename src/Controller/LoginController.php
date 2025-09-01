<?php declare(strict_types=1);

namespace Shibboleth\Controller;

use Common\Stdlib\PsrMessage;
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

        // Create a new session, avoiding the warning in case of error.
        // Avoid warning:  session_regenerate_id(): Session object destruction failed. ID: user (path: /home/mdb/tmp) in /home/mdb/public_html/vendor/laminas/laminas-session/src/SessionManager.php on line 337
        $sessionManager = Container::getDefaultManager();
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED & ~E_WARNING);
        $sessionManager->regenerateId();
        error_reporting($errorReporting);

        $result = $this->auth->authenticate();
        if (!$result->isValid()) {
            $message = $result->getMessages();
            if ($message) {
                $message = is_array($message) ? reset($message) : $message;
                $this->messenger()->addError(new PsrMessage(
                    'Error during authentication via Shibboleth: {message}', // @translate
                    ['message' => $message]
                ));
            } else {
                $this->messenger()->addError(new PsrMessage(
                    'Error during authentication via Shibboleth.' // @translate
                ));
            }
            $this->messenger()->addError(new PsrMessage(
                'The resource you were trying to access is restricted.' // @translate
            ));
            return $this->redirect()->toRoute('top');
        }

        $user = $this->auth->getIdentity();

        $this->messenger()->addSuccess(new PsrMessage(
            'Successfully logged in' // @translate
        ));
        $eventManager = $this->getEventManager();
        $eventManager->trigger('user.login', $user);
        $session = $sessionManager->getStorage();

        if ($redirectUrl = $session->offsetGet('redirect_url')) {
            return $this->redirect()->toUrl($redirectUrl);
        }

        return $this->userIsAllowed('Omeka\Controller\Admin\Index', 'index')
            ? $this->redirect()->toRoute('admin')
            : $this->redirect()->toRoute('top');
    }

    public function logoutAction()
    {
        $this->auth->clearIdentity();

        $sessionManager = Container::getDefaultManager();

        $eventManager = $this->getEventManager();
        $eventManager->trigger('user.logout');

        $sessionManager->destroy();

        $this->messenger()->addSuccess(new PsrMessage(
            'Successfully logged out' // @translate
        ));

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

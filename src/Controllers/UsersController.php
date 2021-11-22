<?php declare(strict_types=1);

if (!class_exists('UsersController')) {
    require_once CONTROLLER_DIR . '/UsersController.php';
}

class Shibboleth_UsersController extends UsersController
{
    public function forgotPasswordAction(): void
    {
        // Disable forgotAction as users are created at log in with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function activateAction(): void
    {
        // Disable activateAction as users are activated with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function addAction(): void
    {
        // Disable addAction as users are now created at log in with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function editAction(): void
    {
        // Disable editAction as users are now edited with Shibboleth.
        // But keep the action, since it's enabled by default in the admin bar.
        $this->forward('show');
    }

    public function changePasswordAction(): void
    {
        // Disable changePasswordAction as password is managed by Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function deleteAction(): void
    {
        // Disable deleteAction as users are now managed with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function loginAction(): void
    {
        $this->_helper->viewRenderer->setNoRender();
        require_once dirname(__FILE__) . '/../libraries/Shibboleth/adapters/ShibbolethAdapter.php';

        $shibbolethConfig = new Zend_Config_Ini(APP_DIR . '/config/shibboleth.ini', null, true);
        $authAdapter = new ShibbolethAdapter($shibbolethConfig->toArray());
        $authResult = $this->_auth->authenticate($authAdapter);

        if (!$authResult->isValid()) {
            if ($log = $this->_getLog()) {
                $ip = $this->getRequest()->getClientIp();
                $log->info("Failed login attempt from '$ip'.");
            }
            $this->_helper->flashMessenger($this->getLoginErrorMessages($authResult), 'error');
            $this->_helper->flashMessenger(
                __('The resource you were trying to access is restricted.'), 'error');
            // Redirect to public page if no rights.
            $this->_helper->redirector->gotoUrl(WEB_ROOT);
        }

        $session = new Zend_Session_Namespace;
        if ($session->redirect) {
            $this->_helper->redirector->gotoUrl($session->redirect);
        } else {
            $this->_helper->redirector->gotoUrl('/');
        }
    }

    public function logoutAction(): void
    {
        $auth = $this->_auth;
        //http://framework.zend.com/manual/en/zend.auth.html
        $auth->clearIdentity();
        $_SESSION = [];
        Zend_Session::destroy();
        $this->_helper->redirector->gotoUrl(WEB_ROOT . '/Shibboleth.sso/Logout?return=' . WEB_ROOT);
    }

    private function _getLog()
    {
        return $this->getInvokeArg('bootstrap')->logger;
    }
}
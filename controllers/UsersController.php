<?php

if (!class_exists('UsersController')) {
    require_once CONTROLLER_DIR . '/UsersController.php';
}

class Shibboleth_UsersController extends UsersController
{
    public function forgotPasswordAction()
    {
        // Disable forgotAction as users are created at log in with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function activateAction()
    {
        // Disable activateAction as users are activated with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function showAction()
    {
        // Disable showAction as users are now managed with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function addAction()
    {
        // Disable addAction as users are now created at log in with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function editAction()
    {
        // Disable editAction as users are now edited with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function changePasswordAction()
    {
        // Disable changePasswordAction as password is managed by Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function deleteAction()
    {
        // Disable deleteAction as users are now managed with Shibboleth.
        throw new Omeka_Controller_Exception_404();
    }

    public function loginAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        require_once '../../libraries/Shibboleth/adapters/ShibbolethAdapter.php';

        $authAdapter = new ShibbolethAdapter();
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

    public function logoutAction()
    {
        $auth = $this->_auth;
        //http://framework.zend.com/manual/en/zend.auth.html
        $auth->clearIdentity();
        $_SESSION = array();
        Zend_Session::destroy();
        $this->_helper->redirector->gotoUrl(WEB_ROOT . '/Shibboleth.sso/Logout?return=' . WEB_ROOT);
    }
}

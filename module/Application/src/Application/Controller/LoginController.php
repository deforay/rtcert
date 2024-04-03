<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;

class LoginController extends AbstractActionController
{

    public \Application\Service\UserService $userService;

    public function __construct($userService)
    {
        $this->userService = $userService;
    }

    public function indexAction()
    {
        $logincontainer = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $route = $this->userService->login($params);
            return $this->redirect()->toRoute($route);
        }
        if (!empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("dashboard");
        } else {
            $vm = new ViewModel();
            $vm->setTerminal(true);
            return $vm;
        }
    }

    public function logoutAction()
    {
        $sessionLogin = new Container('credo');
        $redirect = '/login';
        if ($sessionLogin->roleCode == 'provider') {
            $redirect = '/provider/login';
        }
        $sessionLogin->getManager()->getStorage()->clear();
        return $this->redirect()->toUrl($redirect);
    }
}

<?php
namespace MonthlyBasis\UserHttps\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use MonthlyBasis\Flash\Model\Service\Flash as FlashService;
use MonthlyBasis\User\Model\Factory as UserFactory;
use MonthlyBasis\User\Model\Service as UserService;
use MonthlyBasis\User\Model\Table as UserTable;

class Login extends AbstractActionController
{
    public function __construct(
        FlashService $flashService,
        UserFactory\User $userFactory,
        UserService\LoggedIn $loggedInService,
        UserService\LoggedInUser $loggedInUserService,
        UserService\Login $loginService,
        UserTable\LoginLog $loginLogTable
    ) {
        $this->flashService        = $flashService;
        $this->userFactory         = $userFactory;
        $this->loggedInService     = $loggedInService;
        $this->loggedInUserService = $loggedInUserService;
        $this->loginService        = $loginService;
        $this->loginLogTable       = $loginLogTable;
    }

    public function indexAction()
    {
        if ($this->loggedInService->isLoggedIn()) {
            $userEntity      = $this->loggedInUserService->getLoggedInUser();
            $parameters = [
                'userId'   => $userEntity->getUserId(),
                'username' => $userEntity->getUsername(),
            ];
            return $this->redirect()->toRoute('users/view', $parameters)->setStatusCode(303);
        }

        if (!empty($_POST)) {
            return $this->loginAction();
        }
    }

    protected function loginAction()
    {
        if ($this->loginService->login()) {
            return $this->loginSuccess();
        }

        $this->loginLogTable->insert($_SERVER['REMOTE_ADDR'], 0);
        $this->flashService->set('error', 'Invalid username or password.');
        return $this->redirect()->toRoute('login')->setStatusCode(303);
    }

    protected function loginSuccess()
    {
        $this->loginLogTable->insert($_SERVER['REMOTE_ADDR'], 1);

        $userEntity = $this->userFactory->buildFromUsername(
            $_POST['username']
        );
        $parameters = [
            'userId'   => $userEntity->getUserId(),
            'username' => $userEntity->getUsername(),
        ];

        if (empty($_POST['redirect'])) {
            return $this->redirect()->toRoute('users/view', $parameters)->setStatusCode(303);
        }

        return $this->redirect()->toUrl($_POST['redirect'])->setStatusCode(303);
    }
}

<?php
namespace MonthlyBasis\UserHttps\Controller\Account;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use MonthlyBasis\Flash\Model\Service as FlashService;
use MonthlyBasis\User\Model\Service as UserService;

class ChangePassword extends AbstractActionController
{
    public function __construct(
        FlashService\Flash $flashService,
        UserService\LoggedIn $loggedInService,
        UserService\LoggedInUser $loggedInUserService,
        UserService\Password\Change $changeService,
        UserService\Password\Change\Errors $errorsService
    ) {
        $this->flashService        = $flashService;
        $this->loggedInService     = $loggedInService;
        $this->loggedInUserService = $loggedInUserService;
        $this->changeService       = $changeService;
        $this->errorsService       = $errorsService;
    }

    public function onDispatch(MvcEvent $mvcEvent)
    {
        if (!$this->loggedInService->isLoggedIn()) {
            return $this->redirect()->toRoute('login')->setStatusCode(303);
        }

        return parent::onDispatch($mvcEvent);
    }

    public function changePasswordAction()
    {
        if (!empty($_POST)) {
            return $this->postAction();
        }

        return [
            'user' => $this->loggedInUserService->getLoggedInUser(),
        ];
    }

    protected function postAction()
    {
        if (false != ($errors = $this->errorsService->getErrors())) {
            $this->flashService->set(
                'errors',
                $errors
            );
            return $this->redirect()->toRoute('account/change-password')->setStatusCode(303);
        }

        $this->changeService->changePassword(
            $this->loggedInUserService->getLoggedInUser(),
            $_POST['new-password']
        );

        return (new ViewModel())
            ->setTemplate('monthly-basis/user-https/account/change-password/success')
            ;
    }
}

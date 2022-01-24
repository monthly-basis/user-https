<?php
namespace MonthlyBasis\UserHttps\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use MonthlyBasis\Flash\Model\Service as FlashService;
use MonthlyBasis\User\Model\Exception as UserException;
use MonthlyBasis\User\Model\Service as UserService;
use MonthlyBasis\User\Model\Table as UserTable;

class SignUp extends AbstractActionController
{
    public function __construct(
        FlashService\Flash $flashService,
        UserService\LoggedInUser $loggedInUserService,
        UserService\Register $registerService,
        UserService\Url $urlService
    ) {
        $this->flashService        = $flashService;
        $this->loggedInUserService = $loggedInUserService;
        $this->registerService     = $registerService;
        $this->urlService          = $urlService;
    }

    public function onDispatch(MvcEvent $mvcEvent)
    {
        try {
            $userEntity = $this->loggedInUserService->getLoggedInUser();
            $url        = $this->urlService->getUrl($userEntity);
            return $this->redirect()->toUrl($url)->setStatusCode(303);
        } catch (UserException $userException) {
            // Do nothing.
        }

        return parent::onDispatch($mvcEvent);
    }

    public function indexAction()
    {
        if (!empty($_POST)) {
            return $this->postAction();
        }

        return [
            'errors' => $this->flashService->get('errors'),
        ];
    }

    public function successAction()
    {
    }

    protected function postAction()
    {
        try {
            $this->registerService->register();
        } catch (UserException $userException) {
            return $this->redirect()->toRoute('sign-up')->setStatusCode(303);
        }

        $params = [
            'action' => 'success',
        ];
        return $this->redirect()
                    ->toRoute('sign-up', $params)
                    ->setStatusCode(303);
    }
}

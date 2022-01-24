<?php
namespace MonthlyBasis\UserHttps\Controller;

use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use MonthlyBasis\User\Model\Exception as UserException;
use MonthlyBasis\Flash\Model\Service as FlashService;
use MonthlyBasis\User\Model\Service as UserService;

class ResetPassword extends AbstractActionController
{
    public function __construct(
        FlashService\Flash $flashService,
        UserService\LoggedInUser $loggedInUserService,
        UserService\Password\Reset $resetService,
        UserService\Url $urlService
    ) {
        $this->flashService        = $flashService;
        $this->loggedInUserService = $loggedInUserService;
        $this->resetService        = $resetService;
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

        $this->layout()->setVariables([
            'showAds' => false,
        ]);

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

    public function emailSentAction()
    {
        $email = $this->flashService->get('email');
        if (empty($email)) {
            return $this->redirect()->toRoute('reset-password')->setStatusCode(303);
        }

        return [
            'email' => $email,
        ];
    }

    public function successAction()
    {

    }

    protected function postAction()
    {
        try {
            $this->resetService->reset();
        } catch (Exception $exception) {
            return $this->redirect()
                        ->toRoute('reset-password')
                        ->setStatusCode(303);
        }

        return $this->redirect()
                    ->toRoute('reset-password/email-sent')
                    ->setStatusCode(303);
    }
}

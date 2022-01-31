<?php
namespace MonthlyBasis\UserHttps\Controller\ResetPassword\UserId;

use Exception;
use MonthlyBasis\Flash\Model\Service as FlashService;
use MonthlyBasis\User\Model\Factory as UserFactory;
use MonthlyBasis\User\Model\Service as UserService;
use MonthlyBasis\User\Model\Table as UserTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;

class Code extends AbstractActionController
{
    protected string $code;
    protected int $userId;

    public function __construct(
        FlashService\Flash $flashService,
        UserFactory\Password\Reset\FromUserIdAndCode $fromUserIdAndCodeFactory,
        UserService\Logout $logoutService,
        UserService\Password\Reset\Accessed\ConditionallyUpdate $conditionallyUpdateService,
        UserService\Password\Reset\Expired $expiredService,
        UserTable\ResetPassword $resetPasswordTable,
        UserTable\ResetPasswordAccessLog $resetPasswordAccessLogTable,
        UserTable\User\UserId $userIdTable
    ) {
        $this->flashService                = $flashService;
        $this->fromUserIdAndCodeFactory    = $fromUserIdAndCodeFactory;
        $this->logoutService               = $logoutService;
        $this->conditionallyUpdateService  = $conditionallyUpdateService;
        $this->expiredService              = $expiredService;
        $this->resetPasswordTable          = $resetPasswordTable;
        $this->resetPasswordAccessLogTable = $resetPasswordAccessLogTable;
        $this->userIdTable                 = $userIdTable;
    }

    public function onDispatch(MvcEvent $mvcEvent)
    {
        $count = $this->resetPasswordAccessLogTable
            ->selectCountWhereIpAndValidAndCreatedGreaterThan(
                $_SERVER['REMOTE_ADDR'],
                0,
                date('Y-m-d H:i:s', strtotime('-7 days'))
            );
        if ($count >= 3) {
            return $this->redirect()->toRoute('reset-password')->setStatusCode(303);
        }

        $this->layout()->setVariables([
            'showAds' => false,
        ]);

        return parent::onDispatch($mvcEvent);
    }

    public function indexAction()
    {
        $this->userId = $this->params()->fromRoute('userId');
        $this->code   = $this->params()->fromRoute('code');

        try {
            $resetEntity = $this->fromUserIdAndCodeFactory->buildFromUserIdAndCode(
                $this->userId,
                $this->code,
            );
        } catch (Exception $exception) {
            $this->resetPasswordAccessLogTable->insert(
                $_SERVER['REMOTE_ADDR'],
                0
            );
            return $this->redirect()->toRoute('reset-password')->setStatusCode(303);
        }

        $this->conditionallyUpdateService->conditionallyUpdateAccessed(
            $resetEntity
        );

        if ($this->expiredService->isExpired($resetEntity)) {
            return $this->redirect()->toRoute('reset-password')->setStatusCode(303);
        }

        // At this point, code is valid and not expired.

        if (!empty($_POST)) {
            return $this->postAction();
        }

        $this->resetPasswordAccessLogTable->insert(
            $_SERVER['REMOTE_ADDR'],
            1
        );

        return [
            'errors' => $this->flashService->get('errors'),
        ];
    }

    protected function postAction()
    {
        $errors = [];
        if (empty($_POST['new_password'])) {
            $errors[] = 'Invalid new password.';
        }
        if ($_POST['new_password'] != $_POST['confirm_new_password']) {
            $errors[] = 'New password and confirm new password do not match.';
        }

        if ($errors) {
            $this->flashService->set('errors', $errors);
            $parameters = [
                'userId' => $this->userId,
                'code'   => $this->code,
            ];
            return $this->redirect()
                ->toRoute('reset-password/user-id/code', $parameters)
                ->setStatusCode(303);
        }

        $this->logoutService->logout();

        $this->resetPasswordTable->updateSetUsedToUtcTimestampWhereUserIdAndCode(
            $this->userId,
            $this->code,
        );

        $this->userIdTable->updateSetPasswordHashWhereUserId(
            password_hash($_POST['new_password'], PASSWORD_DEFAULT),
            $this->userId
        );

        return $this->redirect()
            ->toRoute('reset-password/success')
            ->setStatusCode(303);
    }
}

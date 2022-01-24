<?php
namespace MonthlyBasis\UserHttpsTest\Controller\ResetPassword\UserId;

use Laminas\Mvc\MvcEvent;
use MonthlyBasis\Flash\Model\Service as FlashService;
use MonthlyBasis\User\Model\Factory as UserFactory;
use MonthlyBasis\User\Model\Service as UserService;
use MonthlyBasis\User\Model\Table as UserTable;
use MonthlyBasis\UserHttps\Controller as UserController;
use PHPUnit\Framework\TestCase;

class CodeTest extends TestCase
{
    protected function setUp(): void
    {
        $this->flashServiceMock = $this->createMock(
            FlashService\Flash::class
        );
        $this->fromUserIdAndCodeFactoryMock = $this->createMock(
            UserFactory\Password\Reset\FromUserIdAndCode::class
        );
        $this->logoutServiceMock = $this->createMock(
            UserService\Logout::class
        );
        $this->conditionallyUpdateServiceMock = $this->createMock(
            UserService\Password\Reset\Accessed\ConditionallyUpdate::class
        );
        $this->expiredServiceMock = $this->createMock(
            UserService\Password\Reset\Expired::class
        );
        $this->resetPasswordTableMock = $this->createMock(
            UserTable\ResetPassword::class
        );
        $this->resetPasswordAccessLogTableMock = $this->createMock(
            UserTable\ResetPasswordAccessLog::class
        );
        $this->passwordHashTableMock = $this->createMock(
            UserTable\User\PasswordHash::class
        );

        $this->codeController = new UserController\ResetPassword\UserId\Code(
            $this->flashServiceMock,
            $this->fromUserIdAndCodeFactoryMock,
            $this->logoutServiceMock,
            $this->conditionallyUpdateServiceMock,
            $this->expiredServiceMock,
            $this->resetPasswordTableMock,
            $this->resetPasswordAccessLogTableMock,
            $this->passwordHashTableMock,
        );
    }

    public function test_onDispatch_countIsLessThan3_expectedExceptionThrown()
    {
        $mvcEventMock = $this->createMock(
            MvcEvent::class
        );
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->resetPasswordAccessLogTableMock
            ->expects($this->once())
            ->method('selectCountWhereIpAndValidAndCreatedGreaterThan')
            ->with('1.2.3.4', 0)
            ->willReturn(2)
            ;

        try {
            $this->codeController->onDispatch($mvcEventMock);
            $this->fail();
        } catch (\Laminas\Mvc\Exception\DomainException $domainException) {
            $this->assertSame(
                'Missing route matches; unsure how to retrieve action',
                $domainException->getMessage(),
            );
        }
    }

    public function test_onDispatch_countIsGreaterThanOrEqualTo3_expectedExceptionThrown()
    {
        $mvcEventMock = $this->createMock(
            MvcEvent::class
        );
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->resetPasswordAccessLogTableMock
            ->expects($this->once())
            ->method('selectCountWhereIpAndValidAndCreatedGreaterThan')
            ->with('1.2.3.4', 0)
            ->willReturn(3)
            ;

        try {
            $this->codeController->onDispatch($mvcEventMock);
            $this->fail();
        } catch (\Laminas\Mvc\Exception\DomainException $domainException) {
            $this->assertSame(
                'Url plugin requires that controller event compose a router; none found',
                $domainException->getMessage(),
            );
        }
    }
}

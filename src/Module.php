<?php
namespace MonthlyBasis\UserHttps;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use MonthlyBasis\Flash\Model\Service as FlashService;
use MonthlyBasis\User\Model\Factory as UserFactory;
use MonthlyBasis\User\Model\Service as UserService;
use MonthlyBasis\User\Model\Table as UserTable;
use MonthlyBasis\UserHttps\Controller as UserHttpsController;

class Module
{
    public function getConfig()
    {
        return [
            'router' => [
                'routes' => [
                    'account' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/account',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'change-password' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/change-password',
                                    'defaults' => [
                                        'controller' => UserHttpsController\Account\ChangePassword::class,
                                        'action'     => 'change-password',
                                    ],
                                ],
                                'may_terminate' => true,
                            ],
                        ],
                    ],
                    'activate' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/activate/:registerId/:activationCode',
                            'defaults' => [
                                'controller' => UserHttpsController\Activate::class,
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'login' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/login[/:action]',
                            'defaults' => [
                                'controller' => UserHttpsController\Login::class,
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'logout' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/logout',
                            'defaults' => [
                                'controller' => UserHttpsController\Logout::class,
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'sign-up' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/sign-up[/:action]',
                            'defaults' => [
                                'controller' => UserHttpsController\SignUp::class,
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'reset-password' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/reset-password',
                            'defaults' => [
                                'controller' => UserHttpsController\ResetPassword::class,
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'user-id' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/:userId',
                                    'constraints' => [
                                        'userId' => '\d+',
                                    ],
                                ],
                                'may_terminate' => false,
                                'child_routes' => [
                                    'code' => [
                                        'type'    => Segment::class,
                                        'options' => [
                                            'route'    => '/:code',
                                            'defaults' => [
                                                'controller' => UserHttpsController\ResetPassword\UserId\Code::class,
                                                'action'     => 'index',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'email-sent' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/email-sent',
                                    'defaults' => [
                                        'controller' => UserHttpsController\ResetPassword::class,
                                        'action'     => 'email-sent',
                                    ],
                                ],
                            ],
                            'success' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/success',
                                    'defaults' => [
                                        'controller' => UserHttpsController\ResetPassword::class,
                                        'action'     => 'success',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'view_manager' => [
                'template_path_stack' => [
                    'monthly-basis/user-https' => __DIR__ . '/../view',
                ],
            ],
        ];
    }

    public function getControllerConfig()
    {
        return [
            'factories' => [
                UserHttpsController\Account\ChangePassword::class => function ($sm) {
                    return new UserHttpsController\Account\ChangePassword(
                        $sm->get(FlashService\Flash::class),
                        $sm->get(UserService\LoggedIn::class),
                        $sm->get(UserService\LoggedInUser::class),
                        $sm->get(UserService\Password\Change::class),
                        $sm->get(UserService\Password\Change\Errors::class),
                    );
                },
                UserHttpsController\Activate::class => function ($sm) {
                    return new UserHttpsController\Activate(
                        $sm->get(UserService\Activate::class)
                    );
                },
                UserHttpsController\Login::class => function ($sm) {
                    return new UserHttpsController\Login(
                        $sm->get(FlashService\Flash::class),
                        $sm->get(UserFactory\User::class),
                        $sm->get(UserService\LoggedIn::class),
                        $sm->get(UserService\LoggedInUser::class),
                        $sm->get(UserService\Login::class),
                        $sm->get(UserTable\LoginLog::class),
                    );
                },
                UserHttpsController\Logout::class => function ($sm) {
                    return new UserHttpsController\Logout(
                        $sm->get(UserService\Logout::class),
                    );
                },
                UserHttpsController\SignUp::class => function ($sm) {
                    return new UserHttpsController\SignUp(
                        $sm->get(FlashService\Flash::class),
                        $sm->get(UserService\LoggedInUser::class),
                        $sm->get(UserService\Register::class),
                        $sm->get(UserService\Url::class),
                    );
                },
                UserHttpsController\ResetPassword::class => function ($sm) {
                    return new UserHttpsController\ResetPassword(
                        $sm->get(FlashService\Flash::class),
                        $sm->get(UserService\LoggedInUser::class),
                        $sm->get(UserService\Password\Reset::class),
                        $sm->get(UserService\Url::class),
                    );
                },
                UserHttpsController\ResetPassword\UserId\Code::class => function ($sm) {
                    return new UserHttpsController\ResetPassword\UserId\Code(
                        $sm->get(FlashService\Flash::class),
                        $sm->get(UserFactory\Password\Reset\FromUserIdAndCode::class),
                        $sm->get(UserService\Logout::class),
                        $sm->get(UserService\Password\Reset\Accessed\ConditionallyUpdate::class),
                        $sm->get(UserService\Password\Reset\Expired::class),
                        $sm->get(UserTable\ResetPassword::class),
                        $sm->get(UserTable\ResetPasswordAccessLog::class),
                        $sm->get(UserTable\User\UserId::class),
                    );
                },
            ],
        ];
    }

    public function onBootstrap()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

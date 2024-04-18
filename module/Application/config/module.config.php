<?php

namespace Application;

return array(
    'router' => array(
        'routes' => array(
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'home' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        'controller'    => 'Application\Controller\IndexController',
                        'action'        => 'index',
                    ),
                ),

            ),
            'homeAuditPerformance' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/audit-performance',
                    'defaults' => array(
                        'controller' => 'Application\Controller\IndexController',
                        'action' => 'audit-performance',
                    ),
                ),
            ),
            'homeAuditLocations' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/audit-locations',
                    'defaults' => array(
                        'controller' => 'Application\Controller\IndexController',
                        'action' => 'audit-locations',
                    ),
                ),
            ),
            'login' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/login[/:action]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\LoginController',
                        'action' => 'index',
                    ),
                ),
            ),
            'spi-facility' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/facility[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\FacilityController',
                        'action' => 'index',
                    ),
                ),
            ),
            'common' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/common[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\CommonController',
                        'action' => 'index',
                    ),
                ),
            ),
            'roles' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/roles[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RolesController',
                        'action' => 'index',
                    ),
                ),
            ),
            'users' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/users[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\UsersController',
                        'action' => 'index',
                    ),
                ),
            ),
            'config' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/config[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ConfigController',
                        'action' => 'index',
                    ),
                ),
            ),
            'email' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/email[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\EmailController',
                        'action' => 'index',
                    ),
                ),
            ),
            'dashboard' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/dashboard[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DashboardController',
                        'action' => 'index',
                    ),
                ),
            ),
            'test-config' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/test-config[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\TestConfigController',
                        'action' => 'index',
                    ),
                ),
            ),
            'test-section' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/test-section[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\TestSectionController',
                        'action' => 'index',
                    ),
                ),
            ),
            'test-question' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/test-question[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\TestQuestionController',
                        'action' => 'index',
                    ),
                ),
            ),
            'test' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/test[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\TestController',
                        'action' => 'index',
                    ),
                ),
            ),
            'print-test-pdf' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/print-test-pdf[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\PrintTestPdfController',
                        'action' => 'index',
                    ),
                ),
            ),
            'mail-template' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/mail-template[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\MailTemplateController',
                        'action' => 'index',
                    ),
                ),
            ),
            'dashboard-content' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/config[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ConfigController',
                        'action' => 'dashboard-content',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
            'Laminas\Log\LoggerAbstractServiceFactory',
        ),
        'factories' => array(
            'translator' => 'Laminas\Mvc\Service\TranslatorServiceFactory',
            \Application\Command\SendTempMail::class => \Application\Command\SendTempMailFactory::class,
            \Application\Command\SendTestAlertMail::class => \Application\Command\SendTestAlertMailFactory::class,
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            //'Application\Controller\CronController'           => "Application\Controller\CronController",
            'Application\Controller\TestSectionController'    => 'Application\Controller\TestSectionController',
            'Application\Controller\TestQuestionController'   => 'Application\Controller\TestQuestionController',
            'Application\Controller\TestController'           => 'Application\Controller\TestController',
            //'Application\Controller\PrintTestPdfController'   => 'Application\Controller\PrintTestPdfController',
            'Application\Controller\MailTemplateController'   => 'Application\Controller\MailTemplateController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    'laminas-cli' => [
        'commands' => [
            'send-mail' => \Application\Command\SendTempMail::class,
            'send-test-alert-mail' => \Application\Command\SendTestAlertMail::class,
        ],
    ],
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                'mail-console-route' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route' => 'send-mail',
                        'defaults' => array(
                            'controller' => 'Application\Controller\CronController',
                            'action' => 'send-mail'
                        ),
                    ),
                ),
                'test-mail-console-route' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route' => 'send-test-alert-mail',
                        'defaults' => array(
                            'controller' => 'Application\Controller\CronController',
                            'action' => 'send-test-alert-mail'
                        ),
                    ),
                ),
                'db-backup-console-route' => array(
                    'type'    => 'simple',
                    'options' => array(
                        'route'    => 'db-backup',
                        'defaults' => array(
                            'controller' => 'Application\Controller\CronController',
                            'action' => 'db-backup'
                        ),
                    ),
                ),
                'audit-mail-console-route' => array(
                    'type'    => 'simple',
                    'options' => array(
                        'route'    => 'send-audit-mail',
                        'defaults' => array(
                            'controller' => 'Application\Controller\CronController',
                            'action' => 'send-audit-mail'
                        ),
                    ),
                ),
                'generate-bulk-pdf' => array(
                    'type'    => 'simple',
                    'options' => array(
                        'route'    => 'generate-bulk-pdf',
                        'defaults' => array(
                            'controller' => 'Application\Controller\CronController',
                            'action' => 'generate-bulk-pdf'
                        ),
                    ),
                ),
            ),
        ),
    ),
);

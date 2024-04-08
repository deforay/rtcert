<?php

namespace Application;


use Laminas\Mvc\MvcEvent;

use Application\Model\Acl;
use Laminas\Http\Response;
use Laminas\Session\Container;
use Application\Model\RolesTable;
use Application\Model\TestsTable;
use Application\Model\UsersTable;
use Laminas\View\Model\ViewModel;
use Application\Model\GlobalTable;
use Application\Model\EventLogTable;
use Application\Model\QuestionTable;
use Application\Model\TempMailTable;
use Laminas\Mvc\ModuleRouteListener;
use Application\Model\AuditMailTable;
use Application\Model\ResourcesTable;
use Application\Model\TestConfigTable;
use Application\Model\TestOptionsTable;
use Application\Model\TestSectionTable;
use Application\Model\UserRoleMapTable;
use Application\Model\FeedbackMailTable;
use Application\Model\MailTemplateTable;
use Application\Model\PrintTestPdfTable;
use Application\Model\UserTokenMapTable;
use Application\View\Helper\GlobalConfig;
use Application\Model\UserCountryMapTable;
use Application\Model\LocationDetailsTable;


use Application\Model\UserDistrictMapTable;
use Application\Model\UserProvinceMapTable;
use Application\Model\PretestQuestionsTable;

use Application\Model\PostTestQuestionsTable;
use Application\Model\TestConfigDetailsTable;
use Application\Model\PrintTestPdfDetailsTable;
use Application\View\Helper\GetNotificationCount;
use Application\View\Helper\HumanReadableDateFormat;
use Application\View\Helper\IsAllowed;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        /** @var $application \Laminas\Mvc\Application */
        $application = $e->getApplication();

        $eventManager        = $application->getEventManager();

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //no need to call presetter if request is from CLI
        if (php_sapi_name() != 'cli') {
            $eventManager->attach('dispatch', function (MvcEvent $e) {
                return $this->preSetter($e);
            }, 100);
        }
    }

    public function dispatchError(MvcEvent $event)
    {
        $baseModel = new ViewModel();
        $baseModel->setTemplate('layout/layout');
    }

    public function preSetter(MvcEvent $e)
    {


        $session = new Container('credo');
        $diContainer = $e->getApplication()->getServiceManager();
        $commonService = $diContainer->get('CommonService');
        $config = $commonService->getGlobalConfigDetails();
        $session->countryName = $config['country-name'];

        /** @var $application \Laminas\Mvc\Application */
        $application = $e->getApplication();
        /** @var \Laminas\Http\Request $request */
        $request = $e->getRequest();

        if (
            !$request->isXmlHttpRequest() &&
            $e->getRouteMatch()->getParam('controller') != 'Application\Controller\LoginController'
            && $e->getRouteMatch()->getParam('controller') != 'Application\Controller\IndexController'
            && $e->getRouteMatch()->getParam('controller') != 'Certification\Controller\ProviderController'
        ) {
            if (empty($session) || empty($session->userId)) {
                if ($e->getRouteMatch()->getParam('controller') != 'Certification\Controller\ProviderController') {
                    $url = $e->getRouter()->assemble(array(), array('name' => 'provider'));
                } else {
                    $url = $e->getRouter()->assemble(array(), array('name' => 'login'));
                }

                /** @var Response $response */

                $response = $e->getResponse();
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(302);
                $response->sendHeaders();

                // To avoid additional processing
                // we can attach a listener for Event Route with a high priority
                $stopCallBack = function ($event) use ($response) {
                    $event->stopPropagation();
                    return $response;
                };
                //Attach the "break" as a listener with a high priority
                $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                return $response;
            } else {
                $diContainer = $e->getApplication()->getServiceManager();
                //$viewModel = $e->getApplication()->getMvcEvent()->getViewModel();
                $acl = $diContainer->get('AppAcl');
                //$viewModel->acl = $acl;
                $session->acl = serialize($acl);

                $params = $e->getRouteMatch()->getParams();
                $resource = $params['controller'];
                $privilege = $params['action'];

                $role = $session->roleCode;

                //\Zend\Debug\Debug::dump($role);
                //\Zend\Debug\Debug::dump($resource);
                //\Zend\Debug\Debug::dump($acl->isAllowed($role, $resource, $privilege));
                //\Zend\Debug\Debug::dump($privilege);
                //die;
                //if($e->getRequest()->isXmlHttpRequest() || $role == 'SA') {

                if (!$acl->hasResource($resource) || (!$acl->isAllowed($role, $resource, $privilege))) {
                    /** @var \Laminas\Http\Response $response */
                    $response = $e->getResponse();
                    $response->setStatusCode(403);
                    $response->sendHeaders();

                    // To avoid additional processing
                    // we can attach a listener for Event Route with a high priority
                    $stopCallBack = function ($event) use ($response) {
                        $event->stopPropagation();
                        return $response;
                    };
                    //Attach the "break" as a listener with a high priority
                    $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                    return $response;
                }
            }
        } elseif (!empty($session->userId)) {
            $diContainer = $e->getApplication()->getServiceManager();
            // $viewModel = $e->getApplication()->getMvcEvent()->getViewModel();
            $acl = $diContainer->get('AppAcl');
            // $viewModel->acl = $acl;
            $session->acl = serialize($acl);
        }
    }


    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }


    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'AppAcl' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $resourcesTable = $diContainer->get('ResourcesTable');
                        $rolesTable = $diContainer->get('RolesTable');
                        return new Acl($resourcesTable->fetchAllResourceMap(), $rolesTable->fecthAllActiveRoles());
                    }
                },
                'UsersTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UsersTable($dbAdapter);
                    }
                },
                'RolesTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new RolesTable($dbAdapter);
                    }
                },
                'UserRoleMapTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserRoleMapTable($dbAdapter);
                    }
                },
                'GlobalTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $certificationTable = $diContainer->get('\Certification\Model\CertificationTable');
                        return new GlobalTable($dbAdapter, $certificationTable);
                    }
                },
                'EventLogTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new EventLogTable($dbAdapter);
                    }
                },
                'ResourcesTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new ResourcesTable($dbAdapter);
                    }
                },
                'TempMailTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TempMailTable($dbAdapter);
                    }
                },
                'UserTokenMapTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserTokenMapTable($dbAdapter);
                    }
                },
                'LocationDetailsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new LocationDetailsTable($dbAdapter);
                    }
                },
                'UserCountryMapTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserCountryMapTable($dbAdapter);
                    }
                },
                'UserProvinceMapTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserProvinceMapTable($dbAdapter);
                    }
                },
                'UserDistrictMapTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserDistrictMapTable($dbAdapter);
                    }
                },
                'TestConfigTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TestConfigTable($dbAdapter);
                    }
                },
                'TestConfigDetailsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TestConfigDetailsTable($dbAdapter);
                    }
                },
                'TestSectionTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TestSectionTable($dbAdapter);
                    }
                },
                'QuestionTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new QuestionTable($dbAdapter);
                    }
                },
                'TestOptionsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TestOptionsTable($dbAdapter);
                    }
                },
                'PostTestQuestionsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new PostTestQuestionsTable($dbAdapter);
                    }
                },
                'PretestQuestionsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $writtenExamTable = $diContainer->get('Certification\Model\WrittenExamTable');
                        return new PretestQuestionsTable($dbAdapter, $writtenExamTable);
                    }
                },
                'TestsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TestsTable($dbAdapter);
                    }
                },
                'PrintTestPdfTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new PrintTestPdfTable($dbAdapter);
                    }
                },
                'PrintTestPdfDetailsTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new PrintTestPdfDetailsTable($dbAdapter);
                    }
                },
                'MailTemplateTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new MailTemplateTable($dbAdapter);
                    }
                },
                'FeedbackMailTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new FeedbackMailTable($dbAdapter);
                    }
                },

                //services
                'DashboardService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\DashboardService($diContainer);
                    }
                },
                'CommonService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\CommonService($diContainer);
                    }
                },
                'UserService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\UserService($diContainer);
                    }
                },
                'FacilityService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\FacilityService($diContainer);
                    }
                },
                'RoleService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\RoleService($diContainer);
                    }
                },
                'TcpdfExtends' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\TcpdfExtends($diContainer);
                    }
                },
                'TestSectionService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\TestSectionService($diContainer);
                    }
                },
                'QuestionService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\QuestionService($diContainer);
                    }
                },
                'TestService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\TestService($diContainer);
                    }
                },
                'PrintTestPdfService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\PrintTestPdfService($diContainer);
                    }
                },
                'MailService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Application\Service\MailService($diContainer);
                    }
                },
            ),

        );
    }


    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'getNotificationCount'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        $certificationTable = $diContainer->get('Certification\Model\CertificationTable');
                        return new GetNotificationCount($certificationTable);
                    }
                },
                'globalConfigHelper'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalTable = $diContainer->get('GlobalTable');
                        return new GlobalConfig($globalTable);
                    }
                },
                'humanReadableDateFormat'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new HumanReadableDateFormat();
                    }
                },
                'isAllowed'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        $acl = $diContainer->get('AppAcl');
                        return new IsAllowed($acl);
                    }
                },
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Application\Controller\IndexController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dashboardService = $diContainer->get('DashboardService');
                        return new \Application\Controller\IndexController($dashboardService);
                    }
                },
                'Application\Controller\LoginController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        return new \Application\Controller\LoginController($userService);
                    }
                },
                'Application\Controller\FacilityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $facilityService = $diContainer->get('FacilityService');
                        return new \Application\Controller\FacilityController($facilityService);
                    }
                },
                'Application\Controller\RolesController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $roleService = $diContainer->get('RoleService');
                        return new \Application\Controller\RolesController($roleService);
                    }
                },
                'Application\Controller\CommonController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new \Application\Controller\CommonController($commonService);
                    }
                },
                'Application\Controller\DashboardController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dashboardService = $diContainer->get('DashboardService');
                        return new \Application\Controller\DashboardController($dashboardService);
                    }
                },
                'Application\Controller\UsersController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $roleService = $diContainer->get('RoleService');
                        $userService = $diContainer->get('UserService');
                        return new \Application\Controller\UsersController($userService, $commonService, $roleService);
                    }
                },
                'Application\Controller\ConfigController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $certificationTable = $diContainer->get('Certification\Model\CertificationTable');
                        return new \Application\Controller\ConfigController($commonService, $certificationTable);
                    }
                },
                'Application\Controller\TestConfigController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $testSectionService = $diContainer->get('TestSectionService');
                        return new \Application\Controller\TestConfigController($commonService, $testSectionService);
                    }
                },
                'Application\Controller\PrintTestPdfController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $printTestPdfService = $diContainer->get('PrintTestPdfService');
                        return new \Application\Controller\PrintTestPdfController($printTestPdfService);
                    }
                },
                'Application\Controller\TestController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $testService = $diContainer->get('TestService');
                        $questionService = $diContainer->get('QuestionService');
                        $providerTable = $diContainer->get('Certification\Model\ProviderTable');
                        return new \Application\Controller\TestController($commonService, $testService, $questionService, $providerTable);
                    }
                },
                'Application\Controller\TestQuestionController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $testSectionService = $diContainer->get('TestSectionService');
                        $questionService = $diContainer->get('QuestionService');
                        return new \Application\Controller\TestQuestionController($testSectionService, $questionService);
                    }
                },
                'Application\Controller\TestSectionController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $testSectionService = $diContainer->get('TestSectionService');
                        return new \Application\Controller\TestSectionController($testSectionService);
                    }
                },
                'Application\Controller\MailTemplateController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $mailService = $diContainer->get('MailService');
                        return new \Application\Controller\MailTemplateController($mailService);
                    }
                },
            ),
        );
    }


    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}

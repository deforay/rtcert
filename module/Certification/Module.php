<?php

namespace Certification;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Certification\Model\ProviderTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\ProviderTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\TrainingOrganizationTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\TrainingOrganizationTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\TrainingTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\TrainingTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\WrittenExamTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\WrittenExamTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\PracticalExamTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\PracticalExamTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\CertificationTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\CertificationTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\RecertificationTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\RecertificationTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\ExaminationTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\ExaminationTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\CertificationMailTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\CertificationMailTable($dbAdapter);
                    }
                },
                'Certification\Model\RegionTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\RegionTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\DistrictTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\DistrictTable($dbAdapter, $diContainer);
                    }
                },
                'Certification\Model\FacilityTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new \Certification\Model\FacilityTable($dbAdapter, $diContainer);
                    }
                },
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Certification\Controller\CertificationController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $certificationTable = $diContainer->get('Certification\Model\CertificationTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $certificationForm = new \Certification\Form\CertificationForm($dbAdapter);
                        return new \Certification\Controller\CertificationController($commonService, $certificationTable, $certificationForm);
                    }
                },
                'Certification\Controller\PracticalExamController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $practicalExamTable = $diContainer->get('Certification\Model\PracticalExamTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $practicalExamForm = new \Certification\Form\PracticalExamForm($dbAdapter);
                        return new \Certification\Controller\PracticalExamController($practicalExamTable, $practicalExamForm);
                    }
                },
                'Certification\Controller\ProviderController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $testService = $diContainer->get('TestService');
                        $commonService = $diContainer->get('CommonService');
                        $questionService = $diContainer->get('QuestionService');
                        $providerTable = $diContainer->get('Certification\Model\ProviderTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $providerForm = new \Certification\Form\ProviderForm($dbAdapter);
                        return new \Certification\Controller\ProviderController($commonService, $questionService, $testService, $providerForm, $providerTable);
                    }
                },
                'Certification\Controller\RecertificationController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recertificationTable = $diContainer->get('Certification\Model\RecertificationTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $recertificationForm = new \Certification\Form\RecertificationForm($dbAdapter);
                        return new \Certification\Controller\RecertificationController($recertificationForm, $recertificationTable);
                    }
                },
                'Certification\Controller\TrainingController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $trainingTable = $diContainer->get('Certification\Model\TrainingTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $trainingForm = new \Certification\Form\TrainingForm($dbAdapter);
                        return new \Certification\Controller\TrainingController($trainingTable, $trainingForm);
                    }
                },
                'Certification\Controller\TrainingOrganizationController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $trainingOrganizationTable = $diContainer->get('Certification\Model\TrainingOrganizationTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $trainingOrganizationForm = new \Certification\Form\TrainingOrganizationForm($dbAdapter);
                        return new \Certification\Controller\TrainingOrganizationController($trainingOrganizationForm, $trainingOrganizationTable);
                    }
                },
                'Certification\Controller\WrittenExamController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $writtenExamTable = $diContainer->get('Certification\Model\WrittenExamTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $writtenExamform = new \Certification\Form\WrittenExamForm($dbAdapter);
                        return new \Certification\Controller\WrittenExamController($writtenExamform, $writtenExamTable);
                    }
                },
                'Certification\Controller\CertificationMailController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $certificationMailTable = $diContainer->get('Certification\Model\CertificationMailTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $certificationMailForm = new \Certification\Form\CertificationMailForm($dbAdapter);
                        return new \Certification\Controller\CertificationMailController($commonService, $certificationMailTable, $certificationMailForm);
                    }
                },
                'Certification\Controller\ExaminationController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $examinationTable = $diContainer->get('Certification\Model\ExaminationTable');
                        return new \Certification\Controller\ExaminationController($commonService, $examinationTable);
                    }
                },
                'Certification\Controller\FacilityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $facilityTable = $diContainer->get('Certification\Model\FacilityTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $facilityForm = new \Certification\Form\FacilityForm($dbAdapter);
                        return new \Certification\Controller\FacilityController($commonService, $facilityTable, $facilityForm);
                    }
                },
                'Certification\Controller\RegionController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $regionTable = $diContainer->get('Certification\Model\RegionTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $regionForm = new \Certification\Form\RegionForm($dbAdapter);
                        return new \Certification\Controller\RegionController($commonService, $regionTable, $regionForm);
                    }
                },
                'Certification\Controller\DistrictController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $districtTable = $diContainer->get('Certification\Model\DistrictTable');
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $districtForm = new \Certification\Form\DistrictForm($dbAdapter);
                        return new \Certification\Controller\DistrictController($commonService, $districtTable, $districtForm);
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

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}

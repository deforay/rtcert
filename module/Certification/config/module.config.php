<?php

namespace Certification;

return array(
    'router' => array(
        'routes' => array(
            'certification' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/certification[/:action][/:id][/:written][/:practical][/:direct][/:sample][/:last][/:first][/:middle][/:provider][/:certification_id][/:professional_reg_no][/:date_issued][/:key]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\CertificationController',
                        'action' => 'index',
                    ),
                ),
            ),
            'recertification' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/recertification[/:action][/:recertification_id][/:certification_id][/:key]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'recertification_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\RecertificationController',
                        'action' => 'index',
                    ),
                ),
            ),
            'written-exam' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/written-exam[/:action][/:id_written_exam]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'id_written_exam' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\WrittenExamController',
                        'action' => 'index',
                    ),
                ),
            ),
            'practical-exam' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/practical-exam[/:action][/:practice_exam_id][/:id_written_exam]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'practice_exam_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\PracticalExamController',
                        'action' => 'index',
                    ),
                ),
            ),
            'examination' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/examination[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\ExaminationController',
                        'action' => 'index',
                    ),
                ),
            ),
            'provider' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/provider[/][:action][/:id][/:q]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\ProviderController',
                        'action' => 'index',
                    ),
                ),
            ),
            'training' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/training[/:action][/:training_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'training_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\TrainingController',
                        'action' => 'index',
                    ),
                ),
            ),
            //            
            'training-organization' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/training-organization[/:action][/:training_organization_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'training_organization_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\TrainingOrganizationController',
                        'action' => 'index',
                    ),
                ),
            ),
            'certification-mail' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/certification-mail[/:action][/:id][/:email][/:date_end_validity][/:provider][/:provider_id][/:facility_in_charge_email]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\CertificationMailController',
                        'action' => 'index',
                    ),
                ),
            ),
            'region' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/region[/:action][/:id][/:azerty]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\RegionController',
                        'action' => 'index',
                    ),
                ),
            ),
            'district' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/district[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\DistrictController',
                        'action' => 'index',
                    ),
                ),
            ),
            //            
            'facility' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/certification-facility[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        //                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Certification\Controller\FacilityController',
                        'action' => 'index',
                    ),
                ),
            ),
            'generate-certificate-pdf' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/generate-certificate-pdf',
                    'defaults' => array(
                        'controller' => 'Certification\Controller\CertificationMailController',
                        'action' => 'generate-certificate-pdf',
                    ),
                ),
            )
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            //'Certification\Controller\CertificationController'        => "Certification\Controller\CertificationController",
            //'Certification\Controller\CertificationIssuerController'  => "Certification\Controller\CertificationIssuerController",
            //'Certification\Controller\PracticalExamController'        => "Certification\Controller\PracticalExamController",
            //'Certification\Controller\ProviderController'             => "Certification\Controller\ProviderController",
            //'Certification\Controller\RecertificationController'      => "Certification\Controller\RecertificationController",
            //'Certification\Controller\TrainingCertificate'            => "Certification\Controller\TrainingCertificateController",
            //'Certification\Controller\TrainingController'             => "Certification\Controller\TrainingController",
            //'Certification\Controller\TrainingOrganizationController' => "Certification\Controller\TrainingOrganizationController",
            //'Certification\Controller\WrittenExamController'          => "Certification\Controller\WrittenExamController",
            //'Certification\Controller\CertificationMailController'    => "Certification\Controller\CertificationMailController",
            //'Certification\Controller\ExaminationController'          => "Certification\Controller\ExaminationController",
            'Certification\Controller\RegionController'               => "Certification\Controller\RegionController",
            'Certification\Controller\DistrictController'             => "Certification\Controller\DistrictController",
            'Certification\Controller\FacilityController'             => "Certification\Controller\FacilityController",
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);

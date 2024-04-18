<?php
return array(
    'NCDM' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'allow',
            'add' => 'allow',
            'approval' => 'allow',
            'edit' => 'allow',
            'pdf' => 'allow',
            'xls' => 'allow',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'pending' => 'allow',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'allow',
            'testers' => 'allow',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'district' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'allow',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'NCC' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'allow',
            'add' => 'deny',
            'approval' => 'allow',
            'edit' => 'deny',
            'pdf' => 'deny',
            'xls' => 'allow',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'pending' => 'allow',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'allow',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'district' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
            'facility' => 'deny',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
    ),
    'DDM' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'allow',
            'add' => 'allow',
            'approval' => 'allow',
            'edit' => 'allow',
            'pdf' => 'allow',
            'xls' => 'allow',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'pending' => 'allow',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'allow',
            'testers' => 'allow',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'district' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'allow',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'DCC' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'edit' => 'deny',
            'pdf' => 'deny',
            'xls' => 'allow',
            'header-text' => 'deny',
            'recommend' => 'deny',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'allow',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'deny',
            'district' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
            'facility' => 'deny',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'PDM' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'certificate-pdf' => 'deny',
            'edit' => 'deny',
            'pdf' => 'allow',
            'xls' => 'deny',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\MailTemplateController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'deny',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'get-province-list' => 'deny',
            'map-province' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'import-excel' => 'deny',
            'certificate-pdf' => 'deny',
            'delete' => 'allow',
            'district' => 'deny',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'deny',
            'frequency-question' => 'deny',
            'login' => 'deny',
            'logout' => 'deny',
            'send-test-link' => 'allow',
            'test-frequency' => 'deny',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestSectionController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestConfigController' => array(
            'index' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestQuestionController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestController' => array(
            'index' => 'deny',
            'intro' => 'deny',
            'result' => 'deny',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\PrintTestPdfController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'answer-key-one' => 'deny',
            'answer-key-two' => 'deny',
            'examination' => 'deny',
            'print-pdf-question' => 'deny',
            'view-pdf-question' => 'deny',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'provider' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'certificate-pdf' => 'deny',
            'edit' => 'deny',
            'pdf' => 'deny',
            'xls' => 'deny',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'dashboard-content' => 'allow',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\MailTemplateController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'deny',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'get-province-list' => 'deny',
            'map-province' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'import-excel' => 'deny',
            'certificate-pdf' => 'deny',
            'delete' => 'deny',
            'district' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'facility' => 'deny',
            'frequency-question' => 'deny',
            'login' => 'allow',
            'logout' => 'allow',
            'send-test-link' => 'deny',
            'test-frequency' => 'deny',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestSectionController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestConfigController' => array(
            'index' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestQuestionController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestController' => array(
            'index' => 'allow',
            'intro' => 'allow',
            'result' => 'allow',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\PrintTestPdfController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'answer-key-one' => 'deny',
            'answer-key-two' => 'deny',
            'examination' => 'deny',
            'print-pdf-question' => 'deny',
            'view-pdf-question' => 'deny',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
    ),
    'HTS Coord.' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'certificate-pdf' => 'deny',
            'edit' => 'deny',
            'pdf' => 'allow',
            'xls' => 'deny',
            'header-text' => 'deny',
            'recommend' => 'deny',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\MailTemplateController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'deny',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'get-province-list' => 'deny',
            'map-province' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'import-excel' => 'deny',
            'certificate-pdf' => 'deny',
            'delete' => 'allow',
            'district' => 'deny',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'deny',
            'frequency-question' => 'deny',
            'login' => 'deny',
            'logout' => 'deny',
            'send-test-link' => 'allow',
            'test-frequency' => 'deny',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestSectionController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\TestConfigController' => array(
            'index' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\TestQuestionController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\TestController' => array(
            'index' => 'allow',
            'intro' => 'deny',
            'result' => 'allow',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\PrintTestPdfController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'answer-key-one' => 'deny',
            'answer-key-two' => 'deny',
            'examination' => 'deny',
            'print-pdf-question' => 'deny',
            'view-pdf-question' => 'deny',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'PARTNER' => array(
        'Certification\\Controller\\CertificationController' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'certificate-pdf' => 'deny',
            'edit' => 'deny',
            'pdf' => 'allow',
            'xls' => 'deny',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\CommonController' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\ExaminationController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\ConfigController' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\IndexController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\MailTemplateController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\DashboardController' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'deny',
        ),
        'Application\\Controller\\EmailController' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'get-province-list' => 'deny',
            'map-province' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\ProviderController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'import-excel' => 'deny',
            'certificate-pdf' => 'deny',
            'delete' => 'allow',
            'district' => 'deny',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'deny',
            'frequency-question' => 'deny',
            'login' => 'deny',
            'logout' => 'deny',
            'send-test-link' => 'allow',
            'test-frequency' => 'deny',
        ),
        'Application\\Controller\\RolesController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\UsersController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestSectionController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestConfigController' => array(
            'index' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestQuestionController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestController' => array(
            'index' => 'deny',
            'intro' => 'deny',
            'result' => 'deny',
        ),
        'Certification\\Controller\\DistrictController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\FacilityController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\RegionController' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\PrintTestPdfController' => array(
            'index' => 'allow',
            'add' => 'deny',
            'answer-key-one' => 'deny',
            'answer-key-two' => 'deny',
            'examination' => 'deny',
            'print-pdf-question' => 'deny',
            'view-pdf-question' => 'deny',
        ),
        'Certification\\Controller\\RecertificationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganizationController' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExamController' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
);

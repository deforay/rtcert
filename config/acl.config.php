<?php
return array(
    'NCDM' => array(
        'Certification\\Controller\\Certification' => array(
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
        'Certification\\Controller\\CertificationMail' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Common' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\Examination' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'pending' => 'allow',
        ),
        'Application\\Controller\\Config' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\Index' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Dashboard' => array(
            'index' => 'allow',
            'audi-details' => 'allow',
            'testers' => 'allow',
        ),
        'Application\\Controller\\Email' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Facility' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\Provider' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'district' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'allow',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Users' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\District' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Facility' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Region' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExam' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\Recertification' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\Training' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganization' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\WrittenExam' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'NCC' => array(
        'Certification\\Controller\\Certification' => array(
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
        'Certification\\Controller\\CertificationMail' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Common' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\Examination' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'pending' => 'allow',
        ),
        'Application\\Controller\\Config' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\Index' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Dashboard' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'allow',
        ),
        'Application\\Controller\\Email' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Facility' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\Provider' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'district' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
            'facility' => 'deny',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Users' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\District' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Facility' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Region' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExam' => array(
            'index' => 'allow',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Recertification' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\Training' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganization' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExam' => array(
            'index' => 'allow',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
    ),
    'DDM' => array(
        'Certification\\Controller\\Certification' => array(
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
        'Certification\\Controller\\CertificationMail' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Common' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\Examination' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'pending' => 'allow',
        ),
        'Application\\Controller\\Config' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\Index' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Dashboard' => array(
            'index' => 'allow',
            'audi-details' => 'allow',
            'testers' => 'allow',
        ),
        'Application\\Controller\\Email' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Facility' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\Provider' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'district' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
            'facility' => 'allow',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Users' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\District' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Facility' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Region' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExam' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\Recertification' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\Training' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganization' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\WrittenExam' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'DC' => array(
        'Certification\\Controller\\Certification' => array(
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
        'Certification\\Controller\\CertificationMail' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Common' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\Examination' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\Config' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\Index' => array(
            'index' => 'allow',
        ),
        'Application\\Controller\\Dashboard' => array(
            'index' => 'allow',
            'audi-details' => 'deny',
            'testers' => 'allow',
        ),
        'Application\\Controller\\Email' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Facility' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\Provider' => array(
            'index' => 'allow',
            'add' => 'allow',
            'delete' => 'deny',
            'district' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
            'facility' => 'deny',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Users' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\District' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Facility' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Region' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExam' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\Recertification' => array(
            'index' => 'allow',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\Training' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'allow',
        ),
        'Certification\\Controller\\TrainingOrganization' => array(
            'index' => 'allow',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExam' => array(
            'index' => 'allow',
            'add' => 'allow',
            'attempt' => 'allow',
            'delete' => 'allow',
            'edit' => 'allow',
        ),
    ),
    'PDM' => array(
        'Certification\\Controller\\Certification' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'edit' => 'deny',
            'pdf' => 'deny',
            'xls' => 'deny',
            'header-text' => 'deny',
            'recommend' => 'deny',
        ),
        'Certification\\Controller\\CertificationMail' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Common' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\Examination' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\Config' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\Index' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Dashboard' => array(
            'index' => 'deny',
            'audi-details' => 'deny',
            'testers' => 'deny',
        ),
        'Application\\Controller\\Email' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Facility' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\Provider' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'district' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'facility' => 'deny',
        ),
        'Application\\Controller\\Roles' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Users' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestSection' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\TestConfig' => array(
            'index' => 'allow',
            'edit' => 'allow',
        ),
        'Application\\Controller\\Test' => array(
            'index' => 'allow',
            'result' => 'allow',
        ),
        'Certification\\Controller\\District' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Facility' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Region' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExam' => array(
            'index' => 'deny',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestQuestion' => array(
            'index' => 'allow',
            'add' => 'allow',
            'edit' => 'allow',
        ),
        'Certification\\Controller\\Recertification' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\Training' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingOrganization' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExam' => array(
            'index' => 'deny',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
    ),
    'provider' => array(
        'Certification\\Controller\\Certification' => array(
            'pdf-setting' => 'deny',
            'index' => 'deny',
            'add' => 'deny',
            'approval' => 'deny',
            'certification-expiry' => 'allow',
            'edit' => 'deny',
            'pdf' => 'deny',
            'xls' => 'deny',
            'header-text' => 'deny',
            'recommend' => 'allow',
        ),
        'Certification\\Controller\\CertificationMail' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Common' => array(
            'audit-locations' => 'deny',
        ),
        'Certification\\Controller\\Examination' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
            'pending' => 'deny',
        ),
        'Application\\Controller\\Config' => array(
            'index' => 'deny',
            'edit-global' => 'deny',
        ),
        'Application\\Controller\\Index' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\MailTemplate' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Dashboard' => array(
            'index' => 'deny',
            'audi-details' => 'deny',
            'testers' => 'deny',
        ),
        'Application\\Controller\\Email' => array(
            'index' => 'deny',
        ),
        'Application\\Controller\\Facility' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'export-facility' => 'deny',
            'map-province' => 'deny',
            'get-province-list' => 'deny',
            'get-facility-name' => 'deny',
        ),
        'Certification\\Controller\\Provider' => array(
            'index' => 'deny',
            'add' => 'deny',
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
        'Application\\Controller\\Roles' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Users' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestSection' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestConfig' => array(
            'index' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\TestQuestion' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\Test' => array(
            'index' => 'allow',
            'intro' => 'allow',
            'result' => 'allow',
        ),
        'Certification\\Controller\\District' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Facility' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\Region' => array(
            'index' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\PracticalExam' => array(
            'index' => 'deny',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Application\\Controller\\PrintTestPdf' => array(
            'index' => 'deny',
            'add' => 'deny',
            'answer-key-one' => 'deny',
            'answer-key-two' => 'deny',
            'examination' => 'deny',
            'print-pdf-question' => 'deny',
            'view-pdf-question' => 'deny',
        ),
        'Certification\\Controller\\Recertification' => array(
            'index' => 'deny',
            'add' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\Training' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
            'xls' => 'deny',
        ),
        'Certification\\Controller\\TrainingOrganization' => array(
            'index' => 'deny',
            'add' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
        'Certification\\Controller\\WrittenExam' => array(
            'index' => 'deny',
            'add' => 'deny',
            'attempt' => 'deny',
            'delete' => 'deny',
            'edit' => 'deny',
        ),
    ),
);

<?php
namespace Application\Service;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;

class TestSectionService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function addTestSectionData($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $testSectionDb = $this->sm->get('TestSectionTable');
            $response = $testSectionDb->addTestSection($params);
           if($response > 0){
                $subject = '';
                $eventType = 'test-section-add';
                $action = 'added test section details';
                $resourceName = 'test-section';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Online question category added successfully';
            }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }

    public function getTestSectionList($parameters) {
        $testSectionDb = $this->sm->get('TestSectionTable');
        $acl = $this->sm->get('AppAcl');
        return $testSectionDb->fetchTestSectionList($parameters,$acl);
    }

    public function getTestSectionById($testSectionId) {
        $testSectionDb = $this->sm->get('TestSectionTable');
        return $testSectionDb->fetchTestSectionById($testSectionId);
    }
    // Get from the Question Controller
    public function getTestSectionAllList() {
        $testSectionDb = $this->sm->get('TestSectionTable');
        return $testSectionDb->fetchTestSectionAllList();
    }

    public function updateTestSectionDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
            $testSectionDb = $this->sm->get('TestSectionTable');
            $testSectionId = $testSectionDb->updateTestSectionInfo($params);
            if($testSectionId > 0){
                $subject = '';
                $eventType = 'test-section-update';
                $action = 'updated test section details';
                $resourceName = 'test-section';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Online question category updated successfully';
            }
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }

    }
}

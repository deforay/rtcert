<?php
namespace Application\Service;

use PHPExcel;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;

class PrintTestPdfService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getprintTestPdfGrid($params){
        $db = $this->sm->get('PrintTestPdfTable');
        $acl = $this->sm->get('AppAcl');
        return $db->fetchprintTestPdfList($params,$acl);
    }
    
    public function getPtpDetailsInGrid($params){
        $db = $this->sm->get('PrintTestPdfTable');
        $acl = $this->sm->get('AppAcl');
        return $db->fetchPtpDetailsInGrid($params,$acl);
    }

    public function addPrintTestPdfData($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $db = $this->sm->get('PrintTestPdfTable');
            $result = $db->savePrintTestPdfData($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'New test details added successfully';
                // Add event log
                $subject                = $result;
                $eventType              = 'New test details-add';
                $action                 = 'Added a new test details';
                $resourceName           = 'Print Test PDF Details';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getPtpDetailsById($ptpId){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchPtpDetailsById($ptpId);
    }
    
    public function getAllprintTestPdf(){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchAllprintTestPdf();
    }
    
    public function getPdfDetailsById($ptpId){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchPdfDetailsById($ptpId);
    }
    
    public function getPrintTestPdfDetailsById($ptpId){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchPrintTestPdfDetailsById($ptpId);
    }
    
    public function savePdfTitle($params){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->savePdfTitleData($params);
    }
    
    public function changeStatus($params){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->saveChangedStatus($params);
    }
}
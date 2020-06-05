<?php
namespace Application\Service;

use Zend\Session\Container;

class MailService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }
    
    public function getMailTemplate($mailPurpose){
        $db = $this->sm->get('MailTemplateTable');
        return $db->fetchMailTemplate($mailPurpose);
    }
    
    public function updateMailTemplateDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $db = $this->sm->get('MailTemplateTable');
            $result = $db->updateMailTemplate($params);
            $adapter->commit();
            $container = new Container('alert');
            $container->alertMsg = 'Mail Template updated successfully';            

            
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}

?>

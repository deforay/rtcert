<?php
namespace Application\Service;

use Laminas\Session\Container;

class MailService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getMailServiceListInGrid($parameters) {
        $mailDb = $this->sm->get('MailTemplateTable');
        $acl = $this->sm->get('AppAcl');
        return $mailDb->fetchMailServiceListInGrid($parameters,$acl);
    }
    
    public function getMailTemplate($id){
        $db = $this->sm->get('MailTemplateTable');
        return $db->fetchMailTemplate($id);
    }
    
    public function saveMailTemplateDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $db = $this->sm->get('MailTemplateTable');
            $result = $db->saveMailTemplate($params);
            if($result > 0){
                $adapter->commit();
                $container = new Container('alert');
                $container->alertMsg = 'Mail Template saved successfully';            
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}

?>

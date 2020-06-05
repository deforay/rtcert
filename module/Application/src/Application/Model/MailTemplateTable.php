<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;

class MailTemplateTable extends AbstractTableGateway {

    protected $table = 'mail_template';
    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function fetchMailTemplate($mailPurpose) {
       return $this->select(array('mail_purpose' =>$mailPurpose))->current();
    }
    public function updateMailTemplate($params) {
        $params['mainContent'] = str_replace("&nbsp;"," ",strval($params['mainContent']));
        $params['mainContent'] = str_replace("&amp;nbsp;"," ",strval($params['mainContent']));
        $params['subject'] = str_replace("&nbsp;"," ",strval($params['subject']));
        $params['subject'] = str_replace("&amp;nbsp;"," ",strval($params['subject']));
        $params['footer'] = str_replace("&nbsp;"," ",strval($params['footer']));
        $params['footer'] = str_replace("&amp;nbsp;"," ",strval($params['footer']));
        $data=array(
            'from_name' => $params['fromName'],
            'mail_from' => $params['fromMail'],
            'mail_subject' => $params['subject'],
            'mail_content' => $params['mainContent'],
            'mail_footer' => $params['footer']);
        $this->update($data, "mail_purpose='".$params['mailPurpose']."' and mail_temp_id=".$params['mailTempId']);
    }
    
}

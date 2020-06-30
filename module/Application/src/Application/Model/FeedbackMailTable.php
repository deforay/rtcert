<?php

namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Sql;
use Application\Service\CommonService;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author amit
 */
class FeedbackMailTable extends AbstractTableGateway {

    protected $table = 'feedback_mail';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function insertFeedbackMailDetails($name,$email,$subject,$message){
        $common=new CommonService();
        $data = array(
            'feedback_name' => $name,
            'feedback_email' => $email,
            'feedback_subject' => $subject,
            'feedback_message' => $message,
            'added_on' => $common->getDateTime()
        );
        $this->insert($data);
        return $this->lastInsertValue;
    }
    

}
<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Sql;

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
class TempMailTable extends AbstractTableGateway {

    protected $table = 'temp_mail';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function insertTempMailDetails($toEmailAddress,$subject,$message,$fromEmailAddress,$fromName,$cc,$bcc){
        $data = array(
            'from_full_name' => $fromName,
            'from_mail' => $fromEmailAddress,
            'to_email' => $toEmailAddress,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $subject,
            'message' => $message
        );
        $this->insert($data);
        return $this->lastInsertValue;
    }
    
    public function updateTempMailStatus($id){
        return $this->update(array('status'=>'not-sent'),array('temp_id'=>$id));
    }
    
    public function deleteTempMail($id){
        $this->delete(array('temp_id = '.$id));
    }
}
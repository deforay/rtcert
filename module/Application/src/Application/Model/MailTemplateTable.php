<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Debug\Debug;

class MailTemplateTable extends AbstractTableGateway {

    protected $table = 'mail_template';
    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function fetchMailServiceListInGrid($parameters,$acl) {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('from_name', 'mail_from', 'mail_subject', 'mail_purpose', 'mail_status');
        $orderColumns = array('from_name', 'mail_from', 'mail_subject', 'mail_purpose', 'mail_status');
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /*
         * Ordering
         */

        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
                if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
                }
            }
            $sOrder = substr_replace($sOrder, "", -1);
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $sWhere = "";
        if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
            $searchArray = explode(" ", $parameters['sSearch']);
            $sWhereSub = "";
            foreach ($searchArray as $search) {
                if ($sWhereSub == "") {
                    $sWhereSub .= "(";
                } else {
                    $sWhereSub .= " AND (";
                }
                $colSize = count($aColumns);

                for ($i = 0; $i < $colSize; $i++) {
                    if ($i < $colSize - 1) {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                } else {
                    $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                }
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from($this->table);

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
        // echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        //\Zend\Debug\Debug::dump($rResult);die;

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iTotal = $this->select(array("mail_temp_id"))->count();


        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
     
        $loginContainer = new Container('credo');
        $role = $loginContainer->roleCode;
        
        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = ucwords($aRow['from_name']);
            $row[] = $aRow['mail_from'];
            $row[] = ucwords($aRow['mail_subject']);
            $row[] = ucwords($aRow['mail_title']);
            $row[] = ucwords($aRow['mail_status']);
            if ($acl->isAllowed($role, 'Application\Controller\MailTemplate', 'edit')) {
                $row[] = '<a href="/mail-template/edit/' . base64_encode($aRow['mail_temp_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="fa fa-pencil"> Edit</i></a>';
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchMailTemplate($id) {
       return $this->select(array('mail_temp_id' =>$id))->current();
    }
    
    public function fetchMailTemplateByPurpose($purpose) {
       return $this->select(array('mail_purpose' =>$purpose,'mail_status' => 'active'))->current();
    }
    public function saveMailTemplate($params) {
        // Debug::dump($params);die;
        $params['mainContent'] = str_replace("&nbsp;"," ",strval($params['mainContent']));
        $params['mainContent'] = str_replace("&amp;nbsp;"," ",strval($params['mainContent']));
        $params['subject'] = str_replace("&nbsp;"," ",strval($params['subject']));
        $params['subject'] = str_replace("&amp;nbsp;"," ",strval($params['subject']));
        $params['footer'] = str_replace("&nbsp;"," ",strval($params['footer']));
        $params['footer'] = str_replace("&amp;nbsp;"," ",strval($params['footer']));

        $data=array(
            'mail_title'    => $params['mailTitle'],
            'mail_purpose'  => $params['mailPurpose'],
            'from_name'     => $params['fromName'],
            'mail_from'     => $params['fromMail'],
            'mail_subject'  => $params['subject'],
            'mail_content'  => $params['mainContent'],
            'mail_footer'   => $params['footer'],
            'mail_status'   => $params['status']
        );
        if(isset($params['mailTempId']) && $params['mailTempId'] != ''){
            return $this->update($data, "mail_temp_id=".base64_decode($params['mailTempId']));
        } else{
            $this->insert($data);
            return $this->lastInsertValue;
        }
    }
    
}

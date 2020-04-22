<?php

namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author Thanaseelan
 */
class TestSectionTable extends AbstractTableGateway {

    protected $table = 'test_sections';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
   
    public function addTestSection($params) {
        if(isset($params['sectionName']) && trim($params['sectionName']) != ""){
            $this->insert(array(
                'section_name'          => $params['sectionName'],
                'section_slug'          => $param['sectionSlug'],
                'section_description'   => $param['sectionDescription'],
                'status'                => $params['status']
            ));
            return $this->lastInsertValue;
        }
        return false;
	}
    
    public function fetchTestSectionList($parameters,$acl) {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('section_name', 'status');
        $orderColumns = array('section_name','status');
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
        $iTotal = $this->select(array("section_id"))->count();


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
            $row[] = ucwords($aRow['section_name']);
            // $row[] = $aRow['section_slug'];
            $row[] = ucwords($aRow['status']);
            if ($acl->isAllowed($role, 'Application\Controller\TestSection', 'edit')) {
                $row[] = '<a href="/test-section/edit/' . base64_encode($aRow['section_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="fa fa-pencil"> Edit</i></a>';
            }else{
                $row[] = "";
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function fetchTestSectionById($testSectionId) {
        return $this->select(array('section_id' => (int)$testSectionId))->current();
    }
    // Get from the Question Controller
    public function fetchTestSectionAllList() {
        return $this->select(array('status'=>'active'))->toArray();
    }
    
   public function updateTestSectionInfo($param){
        $result = 0;
        if(isset($param['sectionId']) && trim($param['sectionId']) != ""){
            $result = $this->update(array(
                'section_name'          => $param['sectionName'],
                'section_slug'          => $param['sectionSlug'],
                'section_description'   => $param['sectionDescription'],
                'status'                => $param['status']
            ),array("section_id"=>base64_decode($param['sectionId'])));
        } 
        return $result;
	}
}
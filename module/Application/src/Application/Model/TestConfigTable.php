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
 * @author Thanaseelan
 */
class TestConfigTable extends AbstractTableGateway
{

    protected $table = 'test_config';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchTestConfig($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('display_name', 'test_config_value');
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
            for ($i = 0; $i < (int) $parameters['iSortingCols']; $i++) {
                if ($parameters['bSortable_' . (int) $parameters['iSortCol_' . $i]] == "true") {
                    $sOrder .= $aColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }
        /* Individual column filtering */
        $counter = count($aColumns);

        /* Individual column filtering */
        for ($i = 0; $i < $counter; $i++) {
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
        //$sQuery=$this->select();
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

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance 
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->buildSqlString($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iTotal = $this->select()->count();


        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = ucwords($aRow['display_name']);
            $row[] = ucwords($aRow['test_config_value']);
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchTestConfigDetails()
    {
        return $this->select()->toArray();
    }
    
    public function fetchTestConfigEdit()
    {
        $testCondifDetailsDB = new TestConfigDetailsTable($this->adapter);
        $result['config'] =  $this->select()->toArray();
        $configDetails =  $testCondifDetailsDB->select()->toArray();
        if(isset($configDetails) && count($configDetails) > 0){
            foreach($configDetails as $cd){
                $result['configDetails'][$cd['section_id']] = array(
                    'no_of_questions'   => $cd['no_of_questions'],
                    'percentage'        => $cd['percentage']
                );
            }
        }
        return $result;
    }

    public function fetchTestValue($globalName)
    {
        $configValues = $this->select(array('test_config_name' => $globalName))->current();
        return $configValues['test_config_value'];
    }

    public function updateTestConfigDetails($params)
    {
        $testCondifDetailsDB = new TestConfigDetailsTable($this->adapter);
        foreach ($params as $fieldName => $fieldValue) {
            $this->update(array('test_config_value' => $fieldValue), array('test_config_name' => $fieldName));
        }
        if(isset($params['sectionId']) && count($params['sectionId']) > 0){
            foreach($params['sectionId'] as $key=>$sectionId){
                if($testCondifDetailsDB->select(array('section_id'=>$sectionId))->current()){
                    $testCondifDetailsDB->update(array(
                        'no_of_questions'   => $params['noQuestion'][$key],
                        'percentage'        => $params['noPercentage'][$key],
                    ),array('section_id'    => $sectionId));
                }else{
                    $testCondifDetailsDB->insert(array(
                        'section_id'        => $sectionId,
                        'no_of_questions'   => $params['noQuestion'][$key],
                        'percentage'        => $params['noPercentage'][$key],
                    ));
                }
            }

        }
        return true;
    }
}

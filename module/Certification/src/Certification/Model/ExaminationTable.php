<?php

namespace Certification\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Session\Container;

class ExaminationTable {

    protected $tableGateway;

    public function __construct(TableGateway $tableGateway, Adapter $adapter) {
        $this->tableGateway = $tableGateway;
        $this->adapter = $adapter;
    }

    public function fetchAll($parameters) {
        $aColumns = array('e.id','professional_reg_no','certification_reg_no','certification_id','first_name','middle_name','last_name','final_score','practical_total_score');
        $orderColumns = array('e.id','professional_reg_no','certification_reg_no','certification_id','first_name','final_score','practical_total_score');

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
        $sQuery = $sql->select()->from(array('e'=>'examination'))
                               ->columns(array('id', 'provider', 'id_written_exam', 'practical_exam_id'))
                               ->join(array('w_ex' => 'written_exam'), "w_ex.id_written_exam=e.id_written_exam", array('final_score'),'left')
                               ->join(array('p_ex' => 'practical_exam'), "p_ex.practice_exam_id=e.practical_exam_id", array('practical_total_score', 'Sample_testing_score', 'direct_observation_score'),'left')
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'certification_reg_no'),'left')
                               ->where(array('add_to_certification' => 'no'));

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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('e'=>'examination'))
                               ->columns(array('id', 'provider', 'id_written_exam', 'practical_exam_id'))
                               ->join(array('w_ex' => 'written_exam'), "w_ex.id_written_exam=e.id_written_exam", array('final_score'),'left')
                               ->join(array('p_ex' => 'practical_exam'), "p_ex.practice_exam_id=e.practical_exam_id", array('practical_total_score', 'Sample_testing_score', 'direct_observation_score'),'left')
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'certification_reg_no'),'left')
                               ->where(array('add_to_certification' => 'no'));
        $tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iTotal = count($tResult);
        $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
        );
        
        foreach ($rResult as $aRow) {
          $practical_score = $aRow['practical_total_score'];
          $written_score = $aRow['final_score'];
          $sample_testing = $aRow['Sample_testing_score'];
          $direct_observation = $aRow['direct_observation_score'];  
          if ($written_score >= 80 && $direct_observation >= 90 && $sample_testing = 100) {
            $final_decision = 'Certified';
          } elseif ($written_score < 80 && ($direct_observation < 90 || $sample_testing < 100)) {
            $final_decision = 'Failed';
          } else {
            $final_decision = 'Pending';
          }  
         $row = array();
         if (empty($aRow['final_score']) || empty($aRow['final_score'])) {
            $row[] = '';
         }else{
            $row[] = '<input class="recommendationRow" type="checkbox" id="'.$aRow['id'].'" onchange="selectForRecommendation(this);" value="'.$aRow['id'].'#'.$aRow['final_score'].'#'.$aRow['practical_total_score'].'#'.$aRow['direct_observation_score'].'#'.$aRow['Sample_testing_score'].'#'.$aRow['provider'].'#'.$final_decision.'"/>';
         }
         $row[] = $aRow['professional_reg_no'];
         $row[] = $aRow['certification_reg_no'];
         $row[] = $aRow['certification_id'];
         $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
         $row[] = (isset($aRow['final_score']))?$aRow['final_score'].' %':'';
         $row[] = (isset($aRow['practical_total_score']))?$aRow['practical_total_score'].' %':'';
         $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function fetchAllRecommended(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('e'=>'examination'))
                                ->columns(array('provider'))
                                ->join(array('c' => 'certification'), "c.examination=e.id", array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'),'left')
                                ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email'),'left')
                                ->where('c.approval_status IN("pending","Pending")')
                                ->order('c.date_certificate_issued desc');
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchAllApproved(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('e'=>'examination'))
                                ->columns(array('provider'))
                                ->join(array('c' => 'certification'), "c.examination=e.id", array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'),'left')
                                ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email'),'left')
                                ->where('c.approval_status IS NOT NULL AND c.approval_status!= "" AND c.approval_status NOT IN("pending","Pending")')
                                ->order('c.date_certificate_issued desc');
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchAllPendingTests(){
        $logincontainer = new Container('credo');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('e'=>'examination'))
                                ->columns(array())
                                ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name','first_name','middle_name','phone','email'))
                                ->join(array('l_d_r' => 'location_details'), "l_d_r.location_id=p.region", array('regionName'=>'location_name'),'left')
                                ->join(array('l_d_d' => 'location_details'), "l_d_d.location_id=p.district", array('districtName'=>'location_name'),'left')
                                ->join(array('p_ex' => 'practical_exam'), "p_ex.practice_exam_id=e.practical_exam_id", array('writenExamDate'=>'date', 'practical_total_score'),'left')
                                ->join(array('w_ex' => 'written_exam'), "w_ex.id_written_exam=e.id_written_exam", array('practicalExamDate'=>'date', 'final_score'),'left')
                                ->where('e.id_written_exam IS NULL OR e.practical_exam_id IS NULL')
                                ->order('e.id desc');
        if(isset($logincontainer->district) && count($logincontainer->district) > 0){
            $sQuery->where('p.district IN('.implode(',',$logincontainer->district).')');
        }else if(isset($logincontainer->region) && count($logincontainer->region) > 0){
            $sQuery->where('p.region IN('.implode(',',$logincontainer->region).')');
        }else if(isset($logincontainer->country) && count($logincontainer->country) > 0){
            $sQuery->where('l_d_r.country IN('.implode(',',$logincontainer->country).')');
        }
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function fetchAllFailedTests(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('e'=>'examination'))
                                ->columns(array())
                                ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name','first_name','middle_name','phone','email'))
                                ->join(array('p_ex' => 'practical_exam'), "p_ex.practice_exam_id=e.practical_exam_id", array('writenExamDate'=>'date', 'practical_total_score'))
                                ->join(array('w_ex' => 'written_exam'), "w_ex.id_written_exam=e.id_written_exam", array('practicalExamDate'=>'date', 'final_score'))
                                ->join(array('l_d_r' => 'location_details'), "l_d_r.location_id=p.region", array('regionName'=>'location_name'),'left')
                                ->join(array('l_d_d' => 'location_details'), "l_d_d.location_id=p.district", array('districtName'=>'location_name'),'left')
                                ->where('w_ex.final_score < 80 AND (p_ex.direct_observation_score < 90 OR p_ex.Sample_testing_score < 100)')
                                ->order('e.id desc');
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getExamination($id) {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }
    
} 
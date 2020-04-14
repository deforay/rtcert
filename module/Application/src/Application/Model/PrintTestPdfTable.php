<?php

namespace Application\Model;

use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Sql;
use Application\Service\CommonService;
use Zend\Session\Container;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author thanaseelan
 */
class PrintTestPdfTable extends AbstractTableGateway {

    protected $table = 'print_test_pdf';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function fetchprintTestPdfList($parameters,$acl) {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $querycontainer = new Container('query');
        $common = new CommonService();
        $aColumns = array('ptp_title', 'ptp_no_participants', 'ptp_variation', 'first_name',"DATE_FORMAT(ptp_create_on,'%d-%b-%Y %H:%i:%s')");
        $orderColumns = array('ptp_title', 'ptp_no_participants', 'ptp_variation', 'first_name','ptp_create_on');
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
        $sQuery = $sql->select()->from(array('ptp'=>$this->table))
        ->join(array('u'=>'users'),'ptp.ptp_create_by=u.id',array('first_name','last_name'));

        /* Export Filder start */
        if(isset($parameters['pdfTestDate']) && $parameters['pdfTestDate']!='')
        {
            $pdfDate = explode(" to ",$parameters['pdfTestDate']);
            $pdfStartDate = date('Y-m-d', strtotime($pdfDate[0]));
            $pdfEndDate = date('Y-m-d', strtotime($pdfDate[1]));
            $sQuery = $sQuery->where(array("DATE(ptp_create_on) >='" . $pdfStartDate . "'", "DATE(ptp_create_on) <='" . $pdfEndDate . "'"));
        }
        
        if(isset($parameters['pdfTitle']) && $parameters['pdfTitle']!='')
        {
            $sQuery = $sQuery->where(array('ptp.ptp_id'=> (int)base64_decode($parameters['pdfTitle'])));
        }
        /* Export Filder end */
        
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
        $querycontainer->testQueryStr =  $sQueryStr;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iTotal = $this->select()->count();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $editAccess = false;$changeStatusAccess = false;$viewPdfAccess=false;
        foreach ($rResult as $aRow) {
            $eidOption = '';$viewPdfQuestion = '';$changeStatus = '';
            $row = array();
            $row[] = ucwords($aRow['ptp_title']);
            $row[] = $aRow['ptp_no_participants'];
            $row[] = $aRow['ptp_variation'];
            $row[] = ucwords($aRow['first_name'].' '.$aRow['last_name']);
            $row[] = date('d-M-Y h:i A',strtotime($aRow['ptp_create_on']));
            if($acl->isAllowed($role, 'Application\Controller\PrintTestPdf', 'edit')){
                $editAccess = true;
                $eidOption = '<a href="javascript:void(0);" onclick="showModal(\'/facility/get-facility-name\',800,450);" class="btn btn-success" style="width: auto;align-content: center;margin: auto;"><i class="fa fa-pencil">  Edit Title</i></a>';
            }
            if($acl->isAllowed($role, 'Application\Controller\PrintTestPdf', 'change-status')){
                $changeStatusAccess = true;
                $changeStatus = '';
            }
            if($acl->isAllowed($role, 'Application\Controller\PrintTestPdf', 'view-pdf-question')){
                $viewPdfAccess = true;
                $viewPdfQuestion = '<a href="/print-test-pdf/view-pdf-question/' . base64_encode($aRow['ptp_id']) . '" class="btn btn-success" style="width: auto;align-content: center;margin: auto;"><i class="fa fa-eye">  View PDF Questions Variants</i></a>';
            }
            if($editAccess = true || $changeStatusAccess == true || $viewPdfAccess == true){
                $row[] = $eidOption.$changeStatus.$viewPdfQuestion;
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function fetchPtpDetailsInGrid($parameters,$acl) {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $querycontainer = new Container('query');
        $common = new CommonService();
        $aColumns = array('ptp_title', 'ptp_no_participants', 'variant_no', 'first_name',"DATE_FORMAT(ptp_create_on,'%d-%b-%Y %H:%i:%s')");
        $orderColumns = array('ptp_title', 'ptp_no_participants', 'variant_no', 'first_name','ptp_create_on');
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
        $sQuery = $sql->select()->from(array('ptp'=>$this->table))
        ->join(array('ptpd'=>'print_test_pdf_details'),'ptpd.ptp_id=ptp.ptp_id',array('variant_no'))
        ->join(array('u'=>'users'),'ptp.ptp_create_by=u.id',array('first_name','last_name'))
        ->group(array('ptpd'=>'variant_no'));

        /* Export Filder start */
        if(isset($parameters['pdfTestDate']) && $parameters['pdfTestDate']!='')
        {
            $pdfDate = explode(" to ",$parameters['pdfTestDate']);
            $pdfStartDate = date('Y-m-d', strtotime($pdfDate[0]));
            $pdfEndDate = date('Y-m-d', strtotime($pdfDate[1]));
            $sQuery = $sQuery->where(array("DATE(ptp_create_on) >='" . $pdfStartDate . "'", "DATE(ptp_create_on) <='" . $pdfEndDate . "'"));
        }
        
        if(isset($parameters['pdfTitle']) && $parameters['pdfTitle']!='')
        {
            $sQuery = $sQuery->where(array('ptp.ptp_id'=> (int)base64_decode($parameters['pdfTitle'])));
        }
        
        if(isset($parameters['ptpId']) && $parameters['ptpId']!='')
        {
            $sQuery = $sQuery->where(array('ptp.ptp_id'=> (int)$parameters['ptpId']));
        }
        /* Export Filder end */
        
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
        $querycontainer->testQueryStr =  $sQueryStr;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iTotalsQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
        $iTotal = $dbAdapter->query($iTotalsQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->count();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;

        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = ucwords($aRow['ptp_title']);
            $row[] = $aRow['ptp_no_participants'];
            $row[] = $aRow['variant_no'];
            $row[] = ucwords($aRow['first_name'].' '.$aRow['last_name']);
            $row[] = date('d-M-Y h:i A',strtotime($aRow['ptp_create_on']));
            if($acl->isAllowed($role, 'Application\Controller\PrintTestPdf', 'print-pdf-question')){
                $row[] = '<a href="/print-test-pdf/print-pdf-question/' . base64_encode($aRow['ptp_id'].'##'.$aRow['variant_no']) . '" class="btn btn-success" style="width: auto;align-content: center;margin: auto;" target="_blank"><i class="fa fa-print">  Print PDF Questions</i></a>';
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function savePrintTestPdfData($params){
        $logincontainer = new Container('credo');
        $common = new CommonService();
        $ptpdetailsDb = new \Application\Model\PrintTestPdfDetailsTable($this->adapter);
        $questionDb = new \Application\Model\QuestionTable($this->adapter);
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
		$testConfigResult = $testConfigDb->fetchTestConfigDetails();
        if(!isset($params['ptpTitle']) && $params['ptpTitle'] == ''){
            return false;
        }

        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone('Asia/Calcutta'));
        $data = array(
            'ptp_title' => $params['ptpTitle'],
            'ptp_no_participants' => $params['ptpNoOfParticipants'],
            'ptp_variation' => $params['ptpNoOfVariants'],
            'ptp_create_on' => $date->format('Y-m-d H:i:s'),
            'ptp_create_by' => $logincontainer->userId
        );

        $this->insert($data);
        $lastInsertedId = $this->lastInsertValue;
        if($lastInsertedId > 0){
            $questionResult = $this->getRandomPdfQuestions(null, $testConfigResult[1]['test_config_value'], 'print_test_pdf', 'ptp_id');
            if($params['ptpNoOfVariants'] > 0){
                foreach(range(1, $params['ptpNoOfVariants']) as $number){
                    $randomArray = $common->getRandomArray(0,count($questionResult));
                    foreach ($randomArray as $randNo) {
                        $ptpdetailsData = array(
                            'ptp_id'        => $lastInsertedId,
                            'variant_no'    => $number,
                            'question_id'   => $questionResult[$randNo]['question_id'],
                            'response_id'   => $questionResult[$randNo]['correct_option']
                        );
                        $ptpdetailsDb->insert($ptpdetailsData);
                    }
                } 
            }else{
                foreach ($questionResult as $questionList) {
                    $ptpdetailsData = array(
                        'ptp_id'        => $lastInsertedId,
                        'variant_no'    => $number,
                        'question_id'   => $questionList['question_id'],
                        'response_id'   => $questionList['correct_option']
                    );
                    $ptpdetailsDb->insert($ptpdetailsData);
                }
            }
        }
        return $lastInsertedId;
    }

    public function getRandomPdfQuestions($testId = null, $limit = null, $tableName, $primary)
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		//get list of all questions
		$qQuery = $sql->select()->from(array('q' => 'test_questions'));
		if ($testId == null) {
			$qQuery = $qQuery->order(new Expression('RAND()'))
				->where(array('status' => 'active'))
				->limit($limit);
		} else if ($limit == null) {
            $qQuery = $qQuery->join(array('ptp' => $tableName), 'ptp.question_id=q.question_id', array($primary, 'ptp_id', 'question_id', 'response_id'))
                ->join(array('tq'=>'test_questions'),'tq.question_id=ptp.question_id',array('section'))
                ->join(array('ts'=>'test_sections'),'ts.section_id=tq.section',array('section_name','section_slug'))
				->where(array('ptp.ptp_id' => $testId,'tq.status'=>'active','ts.status'=>'active'))
				->order('ptp.' . $primary . ' ASC');
		}
		$qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
		$questionResult = $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		return $questionResult;
    }
    
    public function fetchPtpDetailsById($ptpId){
        return $this->select(array('ptp_id'=>(int)$ptpId))->current();
    }
    
    public function fetchAllprintTestPdf(){
        return $this->select()->toArray();
    }
    
    public function fetchPdfDetailsById($ptpId){
        $ptpId = explode('##',$ptpId);
        // \Zend\Debug\Debug::dump($ptpId);die;
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        // To get the ptp details
        $ptpQuery = $sql->select()->from(array('ptp'=>$this->table))
        ->join(array('u'=>'users'),'ptp.ptp_create_by=u.id',array('first_name','last_name'))
        ->where(array('ptp_id'=>(int)$ptpId[0]));
        $ptpQueryStr = $sql->getSqlStringForSqlObject($ptpQuery);
        $ptpResult = $dbAdapter->query($ptpQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $questionList['ptpDetails'] = array(
            'title'         => $ptpResult['ptp_title'],
            'noParticipant' => $ptpResult['ptp_no_participants'],
            'noVariant'     => $ptpResult['ptp_variation'],
            'createBy'      => ucwords($ptpResult['first_name'].' '.$ptpResult['last_name'])
        );
        // To get the ptp details
        $qQuery = $sql->select()->from(array('ptp' => 'print_test_pdf'))
        ->join(array('ptpd'=>'print_test_pdf_details'),'ptpd.ptp_id=ptp.ptp_id',array('ptpd_id','variant_no','question_id','response_id'))
        ->join(array('tq'=>'test_questions'),'tq.question_id=ptpd.question_id',array('section','question_code','question'))
        ->join(array('ts'=>'test_sections'),'ts.section_id=tq.section',array('section_name','section_slug'))
        ->where(array('ptpd.ptp_id'=>(int)$ptpId[0],'ptpd.variant_no'=>(int)$ptpId[1],'tq.status'=>'active','ts.status'=>'active'))
        ->order(array('ptpd_id ASE'));
        $qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
        $questionResult = $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        
        foreach($questionResult as $key=>$question){
            $optionsList = array();
            $questionList['questions'][($key+1)]['questionList'] = array(
                'variant_no'            => $question['variant_no'],
                'section_name'          => $question['section_name'],
                'question_code'         => $question['question_code'],
                'question'              => $question['question']
            );
            // To get the option list
            $optionsList = $this->fetchOptionList((int)$question['question_id']);
            foreach($optionsList as $option){
                $questionList['questions'][($key+1)]['optionList'][] = $option['option'];
            }
        }
        return $questionList;
    }

    public function fetchOptionList($qId){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $qQuery = $sql->select()->from(array('to' => 'test_options'))
        ->join(array('ptpd'=>'print_test_pdf_details'),'ptpd.question_id=to.question',array('question_id'))
        ->where(array('ptpd.question_id'=>$qId,'to.status'=>'active'))
        ->order('to.option_id ASE')
        ->group('to.option_id');
        $qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
        // die($qQueryStr);
        return $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}
<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class TestsTable extends AbstractTableGateway {

    protected $table = 'tests';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function getTestDataByUserId($userId){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //if the user completed his pre test
        $sQuery = $sql->select()->from(array('t' => 'tests'))->columns(array('pre_test_status','post_test_status','test_id','pretest_start_datetime','posttest_start_datetime','pre_test_score','post_test_score'))
                                ->where(array('t.user_id' => $userId))
                                ->order('test_id DESC')
                                ->limit(1);
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $preTestStatus = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return array('testStatus'=>$preTestStatus);
    }

    public function fetchUserTestList($parameters,$acl) {
        // \Zend\Debug\Debug::dump($parameters);die;
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $querycontainer = new Container('query');
        $common = new CommonService();
        $aColumns = array('first_name', "DATE_FORMAT(pretest_start_datetime,'%d-%b-%Y %H:%i:%s')","DATE_FORMAT(pretest_end_datetime,'%d-%b-%Y %H:%i:%s')",'pre_test_score','pre_test_status');
        $orderColumns = array('first_name', 'pretest_start_datetime', 'pretest_end_datetime','pre_test_score','pre_test_status');
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
        $sQuery = $sql->select()->from(array('t' => 'tests'))
                                ->join(array('p' => 'provider'), 'p.id=t.user_id', array('first_name','last_name'));

        /* Export Filder start */
        if(isset($parameters['preTestDateRange']) && $parameters['preTestDateRange']!='')
        {
            $preDate = explode(" to ",$parameters['preTestDateRange']);
            $preStartDate = date('Y-m-d', strtotime($preDate[0]));
            $preEndDate = date('Y-m-d', strtotime($preDate[1]));
            $sQuery = $sQuery->where(array("DATE(t.pretest_start_datetime) >='" . $preStartDate . "'", "DATE(t.pretest_start_datetime) <='" . $preEndDate . "'"));
        }
        /* if(isset($parameters['postTestDateRange']) && $parameters['postTestDateRange']!='')
        {
            $postDate = explode(" to ",$parameters['postTestDateRange']);
            $postStartDate = date('Y-m-d', strtotime($postDate[0]));
            $postEndDate = date('Y-m-d', strtotime($postDate[1]));
            $sQuery = $sQuery->where(array("DATE(t.posttest_start_datetime) >='" . $postStartDate . "'", "DATE(t.posttest_start_datetime) <='" . $postEndDate . "'"));
        } */
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

        foreach ($rResult as $aRow) {
            $preStartDate = '';$preEndDate = '';$postStartDate = '';$postEndDate = '';
            if($aRow['pretest_start_datetime']!=NULL && $aRow['pretest_start_datetime']!='0000-00-00 00:00:00' && $aRow['pretest_start_datetime']!=''){
                $preStartAry = explode(" ",$aRow['pretest_start_datetime']);
                $preStartDate = $common->humanDateFormat($preStartAry[0])." ".$preStartAry[1];
            }
            if($aRow['pretest_end_datetime']!=NULL && $aRow['pretest_end_datetime']!='0000-00-00 00:00:00' && $aRow['pretest_end_datetime']!=''){
                $preEndAry = explode(" ",$aRow['pretest_end_datetime']);
                $preEndDate = $common->humanDateFormat($preEndAry[0])." ".$preEndAry[1];
            }
            /* if($aRow['posttest_start_datetime']!=NULL && $aRow['posttest_start_datetime']!='0000-00-00 00:00:00' && $aRow['posttest_start_datetime']!=''){
                $postStartAry = explode(" ",$aRow['posttest_start_datetime']);
                $postStartDate = $common->humanDateFormat($postStartAry[0])." ".$postStartAry[1];
            }
            if($aRow['posttest_end_datetime']!=NULL && $aRow['posttest_end_datetime']!='0000-00-00 00:00:00' && $aRow['posttest_end_datetime']!=''){
                $postEndAry = explode(" ",$aRow['posttest_end_datetime']);
                $postEndDate = $common->humanDateFormat($postEndAry[0])." ".$postEndAry[1];
            } */
            $row = array();
            $row[] = ucwords($aRow['first_name'].' '.$aRow['last_name']);
            $row[] = $preStartDate;
            $row[] = $preEndDate;
            $row[] = $aRow['pre_test_score'];
            $row[] = ucwords($aRow['pre_test_status']);
            // $row[] = $postStartDate;
            // $row[] = $postEndDate;
            // $row[] = $aRow['post_test_score'];
            // $row[] = $aRow['post_test_status'];
            
            if($aRow['user_test_status']=='pass' && $acl->isAllowed($role, 'Certification\Controller\Certification', 'certificate-pdf')){
                $row[] = '<a href="/provider/certificate-pdf/' . base64_encode($aRow['test_id']) . '" target="_blank" class="btn btn-success" style="width: auto;align-content: center;margin: auto;"><i class="fa fa-download">  Download Certificate</i></a>';
            }else if($aRow['pre_test_status'] == 'completed'){
                $row[] = "Fail";
            }else{
                $row[] = "";
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function fetchCertificateFieldDetails($testId)
    {
        // \Zend\Debug\Debug::dump($testId);die;
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //if the user completed his pre test
        $sQuery = $sql->select()->from(array('t' => 'tests'))->columns(array('pre_test_status','test_id','pretest_start_datetime','pre_test_score','certificate_no'))
                                ->join(array('p' => 'provider'), 'p.id=t.user_id', array('first_name','last_name'))
                                ->where(array('t.test_id' => $testId))
                                // ->order('test_id DESC')
                                ->limit(1);
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        // echo $sQueryStr;die;
        $certificateField = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        $testConfigResult = $testConfigDb->fetchTestConfigDetails();
        if($certificateField['certificate_no']=='' || $certificateField['certificate_no']==NULL){
            $strparam = strlen($certificateField['test_id']);
            $zeros = substr("000", $strparam);
            $certificateNo = ' C'.date('ym') . $zeros . $certificateField['test_id'];
            $this->update(array('certificate_no' => $certificateNo), array('test_id' => $certificateField['test_id']));
            $certificateField['certificate_no'] = $certificateNo;
        }
        return array('field'=>$certificateField,'testConfig'=>$testConfigResult);
    }
    public function fetchTestsDetailsbyId(){
        $logincontainer = new Container('credo');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('tests')->columns(array('test_id','pretest_end_datetime','pre_test_score','posttest_end_datetime','post_test_score','certificate_no','user_test_status'))
                                ->where(array('user_id' => $logincontainer->userId));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $certificateField = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $certificateField;
    }
}

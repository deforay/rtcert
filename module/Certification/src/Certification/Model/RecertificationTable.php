<?php

namespace Certification\Model;

use Zend\Session\Container;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;

class RecertificationTable {

    protected $tableGateway;
    public $sm = null;

    public function __construct(TableGateway $tableGateway, Adapter $adapter, $sm = null)
    {
        $this->tableGateway = $tableGateway;
        $this->adapter = $adapter;
        $this->sm = $sm;
    }

    public function fetchAll() {
        $sessionLogin = new Container('credo');
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('recertification_id', 'due_date', 'reminder_type', 'reminder_sent_to', 'name_of_recipient', 'date_reminder_sent', 'provider_id'))
                ->join('provider', 'provider.id=recertification.provider_id', array('id','certification_id', 'last_name', 'first_name', 'middle_name'), 'left');
        if(isset($sessionLogin->district) && count($sessionLogin->district) > 0){
            $sqlSelect->where('provider.district IN('.implode(',',$sessionLogin->district).')');
        }else if(isset($sessionLogin->region) && count($sessionLogin->region) > 0){
            $sqlSelect->where('provider.region IN('.implode(',',$sessionLogin->region).')');
        }
        $sqlSelect->order('recertification_id desc');
        $resultSet = $this->tableGateway->selectWith($sqlSelect);
//        die(print_r($resultSet));
        return $resultSet;
    }

    public function getRecertification($recertification_id) {
        $recertification_id = (int) $recertification_id;
        $rowset = $this->tableGateway->select(array('recertification_id' => $recertification_id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $recertification_id");
        }
        return $row;
    }

    public function saveRecertification(Recertification $recertification) {
        $due_date = $recertification->due_date;
        $date_explode = explode("-", $due_date);
        $newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];

        $date_reminder_sent = $recertification->date_reminder_sent;
        $date_explode2 = explode("-", $date_reminder_sent);
        $newsdate2 = $date_explode2[2] . '-' . $date_explode2[1] . '-' . $date_explode2[0];

        $data = array(
            'due_date' => $newsdate,
            'provider_id' => $recertification->provider_id,
            'reminder_type' => $recertification->reminder_type,
            'reminder_sent_to' => $recertification->reminder_sent_to,
            'name_of_recipient' => strtoupper($recertification->name_of_recipient),
            'date_reminder_sent' => $newsdate2,
        );
//die((print_r($data)));
        $recertification_id = (int) $recertification->recertification_id;
        if ($recertification_id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getRecertification($recertification_id)) {
                $this->tableGateway->update($data, array('recertification_id' => $recertification_id));
            } else {
                throw new \Exception('Recertification id does not exist');
            }
        }
    }

    /**
     * select reminder witch must be  send
     * @return type
     */
    public function fetchAll2() {
        $sessionLogin = new Container('credo');
        $db = $this->tableGateway->getAdapter();
        $sqlSelect = "select  certification.id ,examination, final_decision, certification_issuer, date_certificate_issued, "
                . "date_certificate_sent, certification_type, provider,last_name, first_name, middle_name, certification_id,"
                . " certification_reg_no, professional_reg_no,email,date_certificate_issued,date_end_validity,facility_in_charge_email from certification, examination, provider where "
                . "examination.id = certification.examination and provider.id = examination.provider and final_decision='certified' and certificate_sent = 'yes' and reminder_sent='no' and"
                . " datediff(now(),date_end_validity) >=-60";
        if(isset($sessionLogin->district) && count($sessionLogin->district) > 0){
            $sqlSelect = $sqlSelect.' and provider.district IN('.implode(',',$sessionLogin->district).')';
        }else if(isset($sessionLogin->region) && count($sessionLogin->region) > 0){
            $sqlSelect = $sqlSelect.' and provider.region IN('.implode(',',$sessionLogin->region).')';
        }
        $sqlSelect = $sqlSelect. ' order by certification.id asc';
        $statement = $db->query($sqlSelect);
        $resultSet = $statement->execute();

        return $resultSet;
    }

    public function ReminderSent($certification_id) {
        $db = $this->tableGateway->getAdapter();
        $sql = "UPDATE certification set reminder_sent='yes' where id=" . $certification_id;
        $db->getDriver()->getConnection()->execute($sql);
    }

    public function certificationInfo($certification_id) {
        $db = $this->tableGateway->getAdapter();
        $sql2 = 'SELECT provider.id as provider_id, last_name, first_name, middle_name, certification.date_end_validity as due_date from provider, examination , certification WHERE provider.id=examination.provider AND examination.id=certification.examination and certification.id=' . $certification_id;
        $statement2 = $db->query($sql2);
        $result2 = $statement2->execute();

        $selectData = array();

        foreach ($result2 as $res2) {
            $selectData['name'] = $res2['last_name'] . ' ' . $res2['first_name'] . ' ' . $res2['middle_name'];
            $selectData['id'] = $res2['provider_id'];
            $selectData['due_date'] = $res2['due_date'];
        }

        return $selectData;
    }

    public function report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility, $reminder_type, $reminder_sent_to, $startDate2, $endDate2) {
        // echo "test"; die;
        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;

        $db = $this->tableGateway->getAdapter();
        $sql = 'select certification.certification_issuer,certification.certification_type, certification.date_certificate_issued,certification.date_end_validity, certification.final_decision,provider.certification_reg_no, provider.certification_id, provider.professional_reg_no, provider.first_name, provider.last_name, provider.middle_name,l_d_r.location_name as region_name,l_d_d.location_name as district_name, provider.type_vih_test, provider.phone,provider.email, provider.prefered_contact_method,provider.current_jod, provider.time_worked,provider.test_site_in_charge_name, provider.test_site_in_charge_phone,provider.test_site_in_charge_email, provider.facility_in_charge_name, provider.facility_in_charge_phone, provider.facility_in_charge_email,certification_facilities.facility_name, written_exam.exam_type as written_exam_type,written_exam.exam_admin as written_exam_admin,written_exam.date as written_exam_date,written_exam.qa_point,written_exam.rt_point,written_exam.safety_point,written_exam.specimen_point, written_exam.testing_algo_point, written_exam.report_keeping_point,written_exam.EQA_PT_points, written_exam.ethics_point, written_exam.inventory_point, written_exam.total_points,written_exam.final_score, practical_exam.exam_type as practical_exam_type , practical_exam.exam_admin as practical_exam_admin , practical_exam.Sample_testing_score, practical_exam.direct_observation_score,practical_exam.practical_total_score,practical_exam.date as practical_exam_date, recertification.reminder_type, recertification.reminder_sent_to, recertification.name_of_recipient, recertification.date_reminder_sent from certification,examination,written_exam,practical_exam,provider,location_details as l_d_r, location_details as l_d_d, country as c, certification_facilities, recertification WHERE certification.examination= examination.id and examination.id_written_exam= written_exam.id_written_exam and examination.practical_exam_id= practical_exam.practice_exam_id and provider.id=examination.provider and provider.facility_id=certification_facilities.id and provider.region= l_d_r.location_id and provider.district=l_d_d.location_id and l_d_r.country=c.country_id and provider.id=recertification.provider_id and recertification.due_date=certification.date_end_validity';
        if (!empty($startDate) && !empty($endDate)) {
            $sql = $sql . ' and  recertification.due_date >="' . $startDate . '" and recertification.due_date <="' . $endDate . '"';
        }

        if (!empty($decision)) {
            $sql = $sql . ' and certification.final_decision="' . $decision . '"';
        }

        if (!empty($typeHiv)) {
            $sql = $sql . ' and provider.type_vih_test="' . $typeHiv . '"';
        }
        if (!empty($jobTitle)) {
            $sql = $sql . ' and provider.current_jod="' . $jobTitle . '"';
        }

        if (!empty($country)) {
            $sql = $sql . ' and c.country_id=' . $country;
        }else{
            if(isset($logincontainer->country) && count($logincontainer->country) > 0 && $roleCode!='AD'){
                $sql = $sql .' AND c.country_id IN('.implode(',',$logincontainer->country).')';
            }
        }
        
        if (!empty($region)) {
            $sql = $sql . ' and l_d_r.location_id=' . $region;
        }else{
            if(isset($logincontainer->region) && count($logincontainer->region) > 0 && $roleCode!='AD'){
                $sql = $sql .' AND l_d_r.location_id IN('.implode(',',$logincontainer->region).')';
            }
        }

        if (!empty($district)) {
            $sql = $sql . ' and l_d_d.location_id=' . $district;
        }else{
            if(isset($logincontainer->district) && count($logincontainer->district) > 0 && $roleCode!='AD'){
                $sql = $sql .' AND l_d_d.location_id IN('.implode(',',$logincontainer->district).')';
            }
        }

        if (!empty($facility)) {
            $sql = $sql . ' and certification_facilities.id=' . $facility;
        }

        if (!empty($reminder_type)) {
            $sql = $sql . ' and recertification.reminder_type="' . $reminder_type . '"';
        }

        if (!empty($reminder_sent_to)) {
            $sql = $sql . ' and recertification.reminder_sent_to="' . $reminder_sent_to . '"';
        }

        if (!empty($startDate2) && !empty($endDate2)) {
            $sql = $sql . ' and recertification.date_reminder_sent >="' . $startDate2 . '" and recertification.date_reminder_sent <="' . $endDate2 . '"';
        }
//        die($sql);

        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }

     public function getAllActiveCountries(){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT * FROM country ORDER by country_name asc ';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        $selectData = [];
        foreach ($result as $res) {
            $selectData[$res['country_id']] = ucwords($res['country_name']);
        }
//        die(print_r($selectData));
        return $selectData;
    }
    
    public function getRegions() {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT id, region_name FROM certification_regions  ORDER by region_name asc ';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        $selectData = [];
        foreach ($result as $res) {
            $selectData[$res['id']] = $res['region_name'];
        }
//        die(print_r($selectData));
        return $selectData;
    }


    public function reportData($parameters)
    {
        
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $roleCode = $sessionLogin->roleCode;

        $decision = $parameters['decision'];
        $typeHiv = $parameters['typeHIV'];
        $jobTitle = $parameters['jobTitle'];
        $country = $parameters['country'];
        $region = $parameters['region'];
        $district = $parameters['district'];
        $facility = $parameters['facility'];
        $due_date = $parameters['due_date'];
        $excludeTesterName = $parameters['exclude_tester_name'];
        if (!empty($due_date)) {
            $array = explode(" ", $due_date);
            $startDate = date("Y-m-d", strtotime($array[0]));
            $endDate = date("Y-m-d", strtotime($array[2]));
        } else {
//                
            $startDate = "";
            $endDate = "";
        }
        $reminder_type = $parameters['reminder_type'];
        $reminder_sent_to = $parameters['reminder_sent_to'];
        $date_reminder_sent = $parameters['date_reminder_sent'];
        if (!empty($date_reminder_sent)) {
            $array2 = explode(" ", $date_reminder_sent);
            $startDate2 = date("Y-m-d", strtotime($array2[0]));
            $endDate2 = date("Y-m-d", strtotime($array2[2]));
        } else {
//                
            $startDate2 = "";
            $endDate2 = "";
        }

        
        $aColumns = array('first_name', 'professional_reg_no', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_vih_test', 'current_jod', 'reminder_type', 'reminder_sent_to' ,'date_reminder_sent');
       
        $orderColumns = array('first_name', 'professional_reg_no', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_vih_test', 'current_jod', 'reminder_type', 'reminder_sent_to' ,'date_reminder_sent');


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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        $sQuery = $sql->select()->from(array('c' => 'certification'))
            ->columns(array('certification_issuer', 'certification_type', 'date_certificate_issued', 'date_end_validity', 'final_decision'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('id_written_exam'))
            ->join(array('we' => 'written_exam'), 'we.id_written_exam=e.id_written_exam', array('written_exam_type' => 'exam_type','written_exam_admin' => 'exam_admin','written_exam_date' => 'date','qa_point','rt_point','safety_point','specimen_point','testing_algo_point','report_keeping_point','EQA_PT_points','ethics_point','inventory_point','total_points','final_score'))
            ->join(array('pe' => 'practical_exam'), 'pe.practice_exam_id=e.practical_exam_id', array('practical_exam_type' => 'exam_type','practical_exam_admin' => 'exam_admin','Sample_testing_score','direct_observation_score','practical_total_score','practical_exam_date'=>'date'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('certification_reg_no', 'certification_id', 'professional_reg_no', 'first_name', 'last_name', 'middle_name', 'type_vih_test', 'phone','email','prefered_contact_method','current_jod','time_worked','test_site_in_charge_name','test_site_in_charge_phone','test_site_in_charge_email','facility_in_charge_name','facility_in_charge_phone','facility_in_charge_email'), 'left')
            ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id=p.region', array('region_name' => 'location_name'))
            ->join(array('l_d_d' => 'location_details'), 'l_d_d.location_id=p.district', array('district_name' => 'location_name'))
            ->join(array('country' => 'country'), 'country.country_id=l_d_r.country', array('country_name'))       
            ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'))

            ->join(array('rc' => 'recertification'), 'p.id =rc.provider_id ', array('reminder_type','reminder_sent_to','name_of_recipient','date_reminder_sent'))
            ->join(array('cert' => 'certification'), 'rc.due_date=cert.date_end_validity', array('date_end_validity'))
            
            ;

        if (!empty($startDate) && !empty($endDate)) {
            $sQuery->where('rc.due_date >="' . $startDate . '" and rc.due_date <="' . $endDate . '"');
        }
        if (!empty($decision)) {
            $sQuery->where(array('c.final_decision'=>$decision));
        }
        if (!empty($typeHiv)) {
            $sQuery->where(array('p.type_vih_test'=>$typeHiv));
        }
        if (!empty($jobTitle)) {
            $sQuery->where(array('p.current_jod'=>$jobTitle));
        }
        if (!empty($facility)) {
            $sQuery->where(array('cf.id'=>$facility));
        }
        if (!empty($reminder_sent_to)) {
            $sQuery->where(array('rc.reminder_sent_to'=>$reminder_sent_to));
        }
        if (!empty($reminder_type)) {
            $sQuery->where(array('rc.reminder_type'=>$reminder_type));
        }
        if (!empty($country)) {
            $sQuery->where(array('c.country_id'=>$country));
        }else{
            if (isset($sessionLogin->country) && count($sessionLogin->country) > 0 && $roleCode != 'AD') {
                    $sQuery->where('(country.country_id IN(' . implode(',', $sessionLogin->country) . '))');
            }
        }
        if (!empty($region)) {
            $sQuery->where(array('l_d_r.location_id'=>$region));
        }else{
            if (isset($sessionLogin->region) && count($sessionLogin->region) > 0 && $roleCode != 'AD') {
                    $sQuery->where('(l_d_r.location_id IN(' . implode(',', $sessionLogin->country) . '))');
            }
        }
        if (!empty($district)) {
            $sQuery->where(array('l_d_d.location_id'=>$district));
        }else{
            if (isset($sessionLogin->district) && count($sessionLogin->district) > 0 && $roleCode != 'AD') {
                    $sQuery->where('(l_d_d.location_id IN(' . implode(',', $sessionLogin->district) . '))');
            }
        }

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        } else {
            $sQuery->order('c.last_updated_on DESC');
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
        // echo $sQueryStr; die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('c' => 'certification'))
        ->columns(array('certification_issuer', 'certification_type', 'date_certificate_issued', 'date_end_validity', 'final_decision'))
        ->join(array('e' => 'examination'), 'e.id=c.examination', array('id_written_exam'))
        ->join(array('we' => 'written_exam'), 'we.id_written_exam=e.id_written_exam', array('written_exam_type' => 'exam_type','written_exam_admin' => 'exam_admin','written_exam_date' => 'date','qa_point','rt_point','safety_point','specimen_point','testing_algo_point','report_keeping_point','EQA_PT_points','ethics_point','inventory_point','total_points','final_score'))
        ->join(array('pe' => 'practical_exam'), 'pe.practice_exam_id=e.practical_exam_id', array('practical_exam_type' => 'exam_type','practical_exam_admin' => 'exam_admin','Sample_testing_score','direct_observation_score','practical_total_score','practical_exam_date'=>'date'))
        ->join(array('p' => 'provider'), "p.id=e.provider", array('certification_reg_no', 'certification_id', 'professional_reg_no', 'first_name', 'last_name', 'middle_name', 'type_vih_test', 'phone','email','prefered_contact_method','current_jod','time_worked','test_site_in_charge_name','test_site_in_charge_phone','test_site_in_charge_email','facility_in_charge_name','facility_in_charge_phone','facility_in_charge_email'), 'left')
        ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id=p.region', array('region_name' => 'location_name'))
        ->join(array('l_d_d' => 'location_details'), 'l_d_d.location_id=p.district', array('district_name' => 'location_name'))
        ->join(array('country' => 'country'), 'country.country_id=l_d_r.country', array('country_name'))       
        ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'))
        
        ->join(array('rc' => 'recertification'), 'p.id =rc.provider_id ', array('reminder_type','reminder_sent_to','name_of_recipient','date_reminder_sent'))
        ->join(array('cert' => 'certification'), 'rc.due_date=cert.date_end_validity', array('date_end_validity'));

    if (!empty($startDate) && !empty($endDate)) {
        $sQuery->where('rc.due_date >="' . $startDate . '" and rc.due_date <="' . $endDate . '"');
    }
    if (!empty($decision)) {
        $tQuery->where(array('c.final_decision'=>$decision));
    }
    if (!empty($typeHiv)) {
        $tQuery->where(array('p.type_vih_test'=>$typeHiv));
    }
    if (!empty($jobTitle)) {
        $tQuery->where(array('p.current_jod'=>$jobTitle));
    }
    if (!empty($facility)) {
        $tQuery->where(array('cf.id'=>$facility));
    }
    if (!empty($reminder_sent_to)) {
        $sQuery->where(array('rc.reminder_sent_to'=>$reminder_sent_to));
    }
    if (!empty($reminder_type)) {
        $sQuery->where(array('rc.reminder_type'=>$reminder_type));
    }
    if (!empty($country)) {
        $tQuery->where(array('c.country_id'=>$country));
    }else{
        if (isset($sessionLogin->country) && count($sessionLogin->country) > 0 && $roleCode != 'AD') {
            $tQuery->where('(country.country_id IN(' . implode(',', $sessionLogin->country) . '))');
        }
    }
    if (!empty($region)) {
        $tQuery->where(array('l_d_r.location_id'=>$region));
    }else{
        if (isset($sessionLogin->region) && count($sessionLogin->region) > 0 && $roleCode != 'AD') {
            $tQuery->where('(l_d_r.location_id IN(' . implode(',', $sessionLogin->country) . '))');
        }
    }
    if (!empty($district)) {
        $tQuery->where(array('l_d_d.location_id'=>$district));
    }else{
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0 && $roleCode != 'AD') {
            $tQuery->where('(l_d_d.location_id IN(' . implode(',', $sessionLogin->district) . '))');
        }
    }
        $tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the 
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iTotal = count($tResult);
        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
        $acl = $this->sm->get('AppAcl');
        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = ucwords($aRow['region_name']);
            $row[] = ucwords($aRow['district_name']);
            $row[] = ucwords($aRow['facility_name']);
            $row[] = ucwords($aRow['final_decision']);
            $row[] = $aRow['type_vih_test'];
            $row[] = $aRow['current_jod'];
            $row[] = ucwords($aRow['reminder_type']);
            $row[] = $aRow['reminder_sent_to'];
            $row[] = date("d-m-Y", strtotime($aRow['date_reminder_sent']));
            $output['aaData'][] = $row;
        }
        return $output;
    }



}

<?php

namespace Certification\Model;

use Zend\Session\Container;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Debug\Debug;
use Zend\Db\Sql\Expression;
//use Zend\Db\Sql\Where;
use \Application\Model\GlobalTable;
use \Application\Service\CommonService;

class CertificationTable {

    protected $tableGateway;
    public $sm = null;

    public function __construct(TableGateway $tableGateway, Adapter $adapter, $sm=null) {
        $this->tableGateway = $tableGateway;
        $this->adapter = $adapter;
        $this->sm = $sm;
    }

    public function getQuickStats(){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c'=>'certification'))
                     ->columns(
                            array(  "total" => new Expression('COUNT(*)'),
                                    "sent" => new Expression("SUM(CASE 
                                                                    WHEN ((c.certificate_sent = 'yes')) THEN 1
                                                                    ELSE 0
                                                                    END)"),
                                    "toBeSent" => new Expression("SUM(CASE 
                                                                        WHEN ((c.certificate_sent != 'yes' AND c.final_decision='Certified')) THEN 1
                                                                        ELSE 0
                                                                        END)"),
                                    "certified" => new Expression("SUM(CASE 
                                                                        WHEN ((c.certification_type = 'initial'  AND c.date_certificate_issued >= DATE_FORMAT(NOW(),'%Y-%m-01') 
                                                                        AND c.date_certificate_issued <  DATE_FORMAT(NOW(),'%Y-%m-01') + INTERVAL 1 MONTH)) THEN 1
                                                                        ELSE 0
                                                                        END)"),                                                                        
                                    "recertified" => new Expression("SUM(CASE 
                                                                        WHEN ((c.certification_type !=  'initial' AND c.date_certificate_issued >= DATE_FORMAT(NOW(),'%Y-%m-01') 
                                                                        AND c.date_certificate_issued <  DATE_FORMAT(NOW(),'%Y-%m-01') + INTERVAL 1 MONTH)) THEN 1
                                                                        ELSE 0
                                                                        END)"),                       
                                    "pending" => new Expression("SUM(CASE 
                                                                        WHEN ((c.final_decision = 'Pending')) THEN 1
                                                                        ELSE 0
                                                                        END)"),                                                                        
                            )
                );  
                $queryStr = $sql->getSqlStringForSqlObject($query);
                $res = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                return $res[0];
    }

    public function getCertificationPieChartResults($params){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c'=>'certification'))
                     ->columns(array("total_certification" => new Expression('COUNT(*)')))
                     ->join(array('e'=>'examination'),'e.id=c.examination',array())
                     ->join(array('p'=>'provider'),'p.id=e.provider',array())
                     ->join(array('l_d'=>'location_details'),'l_d.location_id=p.region',array('location_name'))
                     ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND date_certificate_issued > DATE_SUB(NOW(), INTERVAL 12 MONTH) AND date_end_validity > NOW()')
                     ->group('p.region');
        $queryStr = $sql->getSqlStringForSqlObject($query);
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
    
    public function getCertificationBarChartResults($params){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $start = strtotime(date("Y", strtotime("-1 year")).'-'.date('m', strtotime('+1 month', strtotime('-1 year'))));
        $end = strtotime(date('Y').'-'.date('m'));
        $j = 0;
        $certificationResult = array();
        while($start <= $end){
            $month = date('m', $start); $year = date('Y', $start); $monthYearFormat = date("M 'y", $start);
            $query = $sql->select()->from(array('c'=>'certification'))
                         ->columns(
                                   array(
                                        "certifications" => new Expression("SUM(CASE 
                                                                                    WHEN ((c.certification_type IS NOT NULL AND c.certification_type != 'NULL' AND c.certification_type != '' AND (c.certification_type = 'Initial' OR c.certification_type = 'initial'))) THEN 1
                                                                                    ELSE 0
                                                                                    END)"),
                                         "recertifications" => new Expression("SUM(CASE 
                                                                                    WHEN ((c.certification_type IS NOT NULL AND c.certification_type != 'NULL' AND c.certification_type != '' AND (c.certification_type = 'Recertification' OR c.certification_type = 'recertification'))) THEN 1
                                                                                    ELSE 0
                                                                                    END)")
                                    )
                                )
                         ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND Month(date_certificate_issued)="'.$month.'" AND Year(date_certificate_issued)="'.$year.'" AND date_end_validity > NOW()');
            $queryStr = $sql->getSqlStringForSqlObject($query);
            $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            $certificationResult['certification']['Certifications'][$j] = (isset($result[0]["certifications"]))?$result[0]["certifications"]:0;
            $certificationResult['certification']['Recertifications'][$j] = (isset($result[0]["recertifications"]))?$result[0]["recertifications"]:0;
            $certificationResult['month'][$j] = $monthYearFormat;
          $start = strtotime("+1 month", $start);
          $j++;
        }
      return $certificationResult;
    }
    
    /**
     * select all certified tester
     * @return type
     */
    public function fetchAll() {
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'));
        $sqlSelect->join('examination', 'examination.id = certification.examination ', array('provider'), 'left')
                ->join('provider', 'provider.id = examination.provider ', array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'), 'left')
                ->where(array('approval_status' => 'approved'))
                ->where(array('final_decision' => 'certified'));
        $sqlSelect->order('id desc');
        return $this->tableGateway->selectWith($sqlSelect);
    }

    /**
     * select tester who are pending or failed to certification
     * @return type
     */
    public function fetchAll2() {
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'));
        $sqlSelect->join('examination', 'examination.id = certification.examination ', array('provider'), 'left')
                ->join('provider', 'provider.id = examination.provider ', array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no'), 'left')
                ->where(array('approval_status' => 'rejected'))
                ->where(array('final_decision' => 'failed'), \Zend\Db\Sql\Where::OP_OR)
                ->where(array('final_decision' => 'pending'), \Zend\Db\Sql\Where::OP_OR);
        $sqlSelect->order('id desc');

        $resultSet = $this->tableGateway->selectWith($sqlSelect);
        return $resultSet;
    }

    public function getCertification($id) {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }

    public function getProvider($id){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT provider FROM certification, examination WHERE certification.examination=examination.id AND certification.id='.$id;
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $provider_id = $res['provider'];
        }
      return $provider_id;
    }
    
    public function saveCertification(Certification $certification) {
        $dbAdapter = $this->adapter;
        $globalDb = new GlobalTable($dbAdapter);
        $monthValid = $globalDb->getGlobalValue('month-valid');
        $validity_end = (isset($monthValid) && trim($monthValid)!= '')?' + '.$monthValid.' month':' + 2 year';
        if($certification->date_certificate_issued == null || $certification->date_certificate_issued = ''){
           $date_issued = date('d-m-Y');
        }else{
           $date_issued = $certification->date_certificate_issued;
        }
        $date_explode = explode("-", $date_issued);
        $newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];
        if (isset($certification->date_certificate_sent) && $certification->date_certificate_sent!= '' && $certification->date_certificate_sent!= '0000-00-00') {
            $certification->date_certificate_sent = date("Y-m-d", strtotime($certification->date_certificate_sent));
        }
        if($certification->certification_type == 'Recertification' || $certification->certification_type == 'recertification'){
            $db = $this->tableGateway->getAdapter();
            $sql = 'select date_end_validity from certification, examination, provider WHERE certification.examination=examination.id and examination.provider=provider.id and final_decision="certified" and provider=' . $certification->provider.' ORDER BY date_certificate_issued DESC LIMIT 1';
            $statement = $db->query($sql);
            $result = $statement->execute();
            foreach ($result as $res) {
                $certification_validity = $res['date_end_validity'];
            }
        }
        if(isset($certification_validity) && $certification_validity!= null && $certification_validity!= '' && $certification_validity!= '0000-00-00'){
            $date_end = date("Y-m-d", strtotime($certification_validity . $validity_end));
        }else{
            $date_end = date("Y-m-d", strtotime($date_issued . $validity_end));
        }

        $data = array(
            'examination' => $certification->examination,
            'final_decision' => $certification->final_decision,
            'certification_issuer' => strtoupper($certification->certification_issuer),
            'date_certificate_issued' => $newsdate,
            'date_certificate_sent' => $certification->date_certificate_sent,
            'certification_type' => $certification->certification_type
        );
//        die(print_r($data));
        $id = (int) $certification->id;
        if ($id == 0) {
            $data['approval_status'] = 'pending';
            $data['date_end_validity'] = $date_end;
            $this->tableGateway->insert($data);
        } else {
            if ($this->getCertification($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('certification id does not exist');
            }
        }
    }

    public function last_id() {
        $last_id = $this->tableGateway->lastInsertValue;
//        die($last_id);
        return $last_id;
    }

    public function updateExamination($last_id) {
        $db = $this->tableGateway->getAdapter();
        $sql1 = 'select examination from certification where id=' . $last_id;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $examination = $res['examination'];
        }

//        die ($examination);

        $sql = 'UPDATE examination SET add_to_certification= "yes" WHERE id=' . $examination;

        $db->getDriver()->getConnection()->execute($sql);
    }

    public function setToActive($last_id) {
        $db = $this->tableGateway->getAdapter();
        $sql1 = 'SELECT id_written_exam,practical_exam_id,final_decision FROM certification ,examination WHERE certification.examination=examination.id and certification.id=' . $last_id;
        $statement1 = $db->query($sql1);
        $result1 = $statement1->execute();

        foreach ($result1 as $res1) {
            $written = $res1['id_written_exam'];
            $practical = $res1['practical_exam_id'];
            $decision = $res1['final_decision'];
        }
        if ((strcasecmp($decision, 'Certified') == 0) || (strcasecmp($decision, 'failed') == 0)) {
// 
            $sql2 = "UPDATE written_exam SET display='no' WHERE id_written_exam=" . $written;
            $statement2 = $db->query($sql2);
            $result2 = $statement2->execute();

            $sql3 = "UPDATE practical_exam SET display='no' WHERE practice_exam_id=" . $practical;
            $statement3 = $db->query($sql3);
            $result3 = $statement3->execute();
        }
    }

    public function certificationType($provider) {
        $db = $this->tableGateway->getAdapter();
        $sql1 = 'select certification_id, date_end_validity from certification, examination, provider WHERE certification.examination=examination.id and examination.provider=provider.id and final_decision="certified" and provider=' . $provider.' ORDER BY date_certificate_issued DESC LIMIT 1';
        //die($sql1);
        $statement = $db->query($sql1);
        $result = $statement->execute();
        $certification_id = null;
        foreach ($result as $res) {
            $certification_id = $res['certification_id'];
            $date_end_validity = $res['date_end_validity'];
        }
        if(isset($certification_id) && $certification_id!= null && $certification_id!= ''){
            $dbAdapter = $this->adapter;
            $globalDb = new GlobalTable($dbAdapter);
            $monthFlexLimit = $globalDb->getGlobalValue('month-flex-limit');
            $startdate = strtotime(date('Y-m-d'));
            $enddate = strtotime($date_end_validity);
            $startyear = date('Y', $startdate);
            $endyear = date('Y', $enddate);
            $startmonth = date('m', $startdate);
            $endmonth = date('m', $enddate);
            $remmonths = abs((($endyear - $startyear) * 12) + ($endmonth - $startmonth));
            if($remmonths > 0 && $remmonths > $monthFlexLimit){
                $certification_id = null;
            }
        }
        return $certification_id;
    }

    public function certificationId($provider) {
        $db = $this->tableGateway->getAdapter();
        $dbAdapter = $this->adapter;
        $globalDb = new GlobalTable($dbAdapter);
        $sql = 'SELECT MAX(certification_key) as max FROM provider';
        $statement = $db->query($sql);
        $result = $statement->execute();
        $certificationKey = 1;
        foreach ($result as $res) {
            $certificationKey = ($res['max']+1);
        }
        
        $certificatePrefix = ($globalDb->getGlobalValue('certificate-prefix')!= null && $globalDb->getGlobalValue('certificate-prefix')!= '')?$globalDb->getGlobalValue('certificate-prefix'):'';
        $certification_id = $certificatePrefix . sprintf("%04d", $certificationKey);
        $sql2 = "UPDATE provider SET certification_id='" . $certification_id . "', certification_key = '.$certificationKey.' WHERE id=" . $provider;

        $db->getDriver()->getConnection()->execute($sql2);
    }

    /**
     * select certified testers who certificate are not yet sent
     * @return type
     */
    public function fetchAll3() {
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'));
        $sqlSelect->join('examination', 'examination.id = certification.examination ', array('provider'), 'left')
                ->join('provider', 'provider.id = examination.provider ', array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'), 'left')
                ->where(array('approval_status' => 'approved'))
                ->where(array('final_decision' => 'certified'))
                ->where(array('certificate_sent' => 'no'));
        $sqlSelect->order('id desc');
        $resultSet = $this->tableGateway->selectWith($sqlSelect);
        return $resultSet;
    }

    public function countCertificate() {
        $db = $this->tableGateway->getAdapter();
        $sqlSelect = 'select COUNT(*)  as nb from  (select certification.id, examination, final_decision, certification_issuer, date_certificate_issued, date_certificate_sent, certification_type, date_end_validity,examination.provider, last_name, first_name, middle_name, certification_id, certification_reg_no, professional_reg_no, email, facility_in_charge_email from certification,examination,provider             where examination.id = certification.examination and provider.id = examination.provider and final_decision ="certified" and certificate_sent ="no") as tab';
        $statement = $db->query($sqlSelect);

        $result = $statement->execute();
        foreach ($result as $res) {
            $nb = $res['nb'];
        }
        return $nb;
    }

    public function countReminder() {
        $db = $this->tableGateway->getAdapter();
        $sqlSelect = 'select COUNT(*) as nb2 from (select  certification.id ,examination, final_decision, certification_issuer, date_certificate_issued, 
                date_certificate_sent, certification_type, provider,last_name, first_name, middle_name, certification_id,
                certification_reg_no, professional_reg_no,email,date_end_validity,facility_in_charge_email from certification, examination, provider where examination.id = certification.examination and provider.id = examination.provider and final_decision="certified" and certificate_sent = "yes" and reminder_sent="no" and datediff(now(),date_end_validity) >=-60 order by certification.id asc) as tab';
        $statement = $db->query($sqlSelect);

        $result = $statement->execute();
        foreach ($result as $res) {
            $nb2 = $res['nb2'];
        }
        return $nb2;
    }

    public function CertificateSent($provider) {
        $db = $this->tableGateway->getAdapter();
        $sql = "UPDATE certification set certificate_sent='yes' where id=" . $provider;
        $db->getDriver()->getConnection()->execute($sql);
    }

    public function report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility) {
        $db = $this->tableGateway->getAdapter();
        $sql = 'select certification.certification_issuer,certification.certification_type, certification.date_certificate_issued,certification.date_end_validity, certification.final_decision,provider.certification_reg_no, provider.certification_id, provider.professional_reg_no, provider.first_name, provider.last_name, provider.middle_name,l_d_r.location_name as region_name,l_d_d.location_name as district_name,c.country_name, provider.type_vih_test, provider.phone,provider.email, provider.prefered_contact_method,provider.current_jod, provider.time_worked,provider.test_site_in_charge_name, provider.test_site_in_charge_phone,provider.test_site_in_charge_email, provider.facility_in_charge_name, provider.facility_in_charge_phone, provider.facility_in_charge_email,certification_facilities.facility_name, written_exam.exam_type as written_exam_type,written_exam.exam_admin as written_exam_admin,written_exam.date as written_exam_date,written_exam.qa_point,written_exam.rt_point,written_exam.safety_point,written_exam.specimen_point, written_exam.testing_algo_point, written_exam.report_keeping_point,written_exam.EQA_PT_points, written_exam.ethics_point, written_exam.inventory_point, written_exam.total_points,written_exam.final_score, practical_exam.exam_type as practical_exam_type , practical_exam.exam_admin as practical_exam_admin , practical_exam.Sample_testing_score, practical_exam.direct_observation_score,practical_exam.practical_total_score,practical_exam.date as practical_exam_date from certification,examination,written_exam,practical_exam,provider,location_details as l_d_r,location_details as l_d_d, country as c, certification_facilities WHERE certification.examination= examination.id and examination.id_written_exam= written_exam.id_written_exam and examination.practical_exam_id= practical_exam.practice_exam_id and provider.id=examination.provider and provider.facility_id=certification_facilities.id and provider.region= l_d_r.location_id and provider.district=l_d_d.location_id and l_d_r.country=c.country_id';

        if (!empty($startDate) && !empty($endDate)) {
            $sql = $sql . ' and  certification.date_certificate_issued between"' . $startDate . '" and "' . $endDate . '"';
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
        }
        
        if (!empty($region)) {
            $sql = $sql . ' and l_d_r.location_id=' . $region;
        }

        if (!empty($district)) {
            $sql = $sql . ' and l_d_d.location_id=' . $district;
        }

        if (!empty($facility)) {
            $sql = $sql . ' and certification_facilities.id=' . $facility;
        }
//        die($sql);
        //echo $sql;die;
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

    public function SelectTexteHeader() {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT id, header_texte FROM pdf_header_texte';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $header_text = $res['header_texte'];
        }
//        die($header_texte);
        return $header_text;
    }

    public function insertTextHeader($text) {
        $db = $this->tableGateway->getAdapter();

        $sql = 'SELECT count(*) as nombre FROM pdf_header_texte';
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }

        if ($nombre == 0) {

            $sql2 = 'insert into pdf_header_texte (header_texte) values ("' . $text . '")';
            $statement2 = $db->query($sql2);
            $result2 = $statement2->execute();
        } else {
            $sql3 = 'TRUNCATE pdf_header_texte';
            $statement3 = $db->query($sql3);
            $result3 = $statement3->execute();

            $sql2 = 'insert into pdf_header_texte (header_texte) values ("' . $text . '")';
            $statement2 = $db->query($sql2);
            $result2 = $statement2->execute();
        }
    }
    
    public function getCertificationValiditydate($id){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT date_end_validity FROM certification WHERE certification.id = '.$id;
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $date_end_validity = $res['date_end_validity'];
        }
      return $date_end_validity;
    }
    
    public function fetchAllRecommended($parameters){
        $aColumns = array('c.id','professional_reg_no','certification_reg_no','certification_id','first_name','middle_name','last_name','final_decision','certification_issuer',"DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')","DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')",'certification_type');
        $orderColumns = array('c.id','professional_reg_no','certification_reg_no','certification_id','first_name','final_decision','certification_issuer','date_certificate_issued','date_certificate_sent','certification_type');

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
        $sQuery = $sql->select()->from(array('c'=>'certification'))
                               ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email'),'left')
                               ->where('c.approval_status IN("pending","Pending")');

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
        $tQuery =  $sql->select()->from(array('c'=>'certification'))
                                 ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                                 ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                                 ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email'),'left')
                                 ->where('c.approval_status IN("pending","Pending")');
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
         $row = array();
            $row[] = '<input class="approvalRow" type="checkbox" id="'.$aRow['id'].'" onchange="selectForApproval(this);" value="'.$aRow['id'].'"/>';
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued']!= null && $aRow['date_certificate_issued']!= '' && $aRow['date_certificate_issued']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_issued'])):'';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent']!= null && $aRow['date_certificate_sent']!= '' && $aRow['date_certificate_sent']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_sent'])):'';
            $row[] = $aRow['certification_type'];
         $output['aaData'][] = $row;
        }
        return $output;
    }

    public function updateCertficateApproval($params){
        $result = 0;
        if(isset($params['approvalRow']) && count($params['approvalRow']) > 0){
            $db = $this->tableGateway->getAdapter();
            for($i=0;$i<count($params['approvalRow']);$i++){
                $sql = "UPDATE certification SET approval_status='" . $params['status'] . "' WHERE id=" . $params['approvalRow'][$i];

           $result = $db->getDriver()->getConnection()->execute($sql);
            }
        }
      return $result;
    }
    
    public function fetchAllToBeSentCertificate($parameters){
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $aColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','middle_name','last_name','final_decision','certification_issuer',"DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')","DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')",'certification_type');
        $orderColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','final_decision','certification_issuer','date_certificate_issued','date_certificate_sent','certification_type');

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
        $sQuery = $sql->select()->from(array('c'=>'certification'))
                               ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'),'left')
                               ->where('c.approval_status IN("approved","Approved") AND c.final_decision IN("certified","Certified") AND certificate_sent ="no"')
                               ->order('c.id desc');

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
        $tQuery =  $sql->select()->from(array('c'=>'certification'))
                               ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'),'left')
                               ->where('c.approval_status IN("approved","Approved") AND c.final_decision IN("certified","Certified") AND certificate_sent ="no"')
                               ->order('c.id desc');
        $tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
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
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued']!= null && $aRow['date_certificate_issued']!= '' && $aRow['date_certificate_issued']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_issued'])):'';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent']!= null && $aRow['date_certificate_sent']!= '' && $aRow['date_certificate_sent']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_sent'])):'';
            $row[] = $aRow['certification_type'];
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'pdf')) {
                if (strcasecmp($aRow['final_decision'], 'Certified') == 0) {
                   $row[] = "<a href='/certification/pdf?".urlencode(base64_encode('id'))."=".base64_encode($aRow['id'])."&".urlencode(base64_encode('last'))."=".base64_encode($aRow['last_name'])."&".urlencode(base64_encode('first'))."=".base64_encode($aRow['first_name'])."&".urlencode(base64_encode('middle'))."=".base64_encode($aRow['middle_name'])."&".urlencode(base64_encode('professional_reg_no'))."=".base64_encode($aRow['professional_reg_no'])."&".urlencode(base64_encode('certification_id'))."=".base64_encode($aRow['certification_id'])."&".urlencode(base64_encode('date_issued'))."=".base64_encode($aRow['date_certificate_issued'])."' target='_blank'><span class='glyphicon glyphicon-download-alt'>PDF</span></a>";
                }else{
                   $row[] = "<div><span class='glyphicon glyphicon-download-alt'>PDF</span></div>";
                }
            }
            if ($acl->isAllowed($role, 'Certification\Controller\CertificationMail', 'index')) {
                $row[] = "<a href='/certification-mail/index?".urlencode(base64_encode('email'))."=".base64_encode($aRow['email'])."&".urlencode(base64_encode('certification_id'))."=".base64_encode($aRow['id'])."&".urlencode(base64_encode('provider'))."=".base64_encode($aRow['last_name'] . " " . $aRow['first_name'] . " " . $aRow['middle_name'])."&".urlencode(base64_encode('facility_in_charge_email'))."=".base64_encode($aRow['facility_in_charge_email'])."&".urlencode(base64_encode('key2'))."=".base64_encode('key')."'><span class='glyphicon glyphicon-envelope'></span>&nbsp;Send Certificate</a>";
            }
            if ($acl->isAllowed($role, 'Certification\Controller\CertificationMail', 'index')) {
              $row[] = "<div style='width:120px;height:40px;overflow:auto;'><a href='javascript:void(0);' onclick='markAsSent(\"".base64_encode('certification_id')."\",\"". base64_encode($aRow['id'])."\",\"".base64_encode('key')."\",\"". base64_encode('key')."\");'><span class='glyphicon glyphicon-send'></span>&nbsp;Mark as sent</a></div>";
            }
         $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function fetchAllCertifiedTester($parameters){
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $aColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','middle_name','last_name','final_decision','certification_issuer',"DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')","DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')",'certification_type');
        $orderColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','final_decision','certification_issuer','date_certificate_issued','date_certificate_sent','certification_type');

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
        $sQuery = $sql->select()->from(array('c'=>'certification'))
                               ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email','facility_in_charge_email'),'left')
                               ->where('c.approval_status IN("approved","Approved") AND c.final_decision IN("certified","Certified")')
                               ->order('c.id desc');

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
        $tQuery =  $sql->select()->from(array('c'=>'certification'))
                                ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email','facility_in_charge_email'),'left')
                               ->where('c.approval_status IN("approved","Approved") AND c.final_decision IN("certified","Certified")')
                               ->order('c.id desc');
        $tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
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
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued']!= null && $aRow['date_certificate_issued']!= '' && $aRow['date_certificate_issued']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_issued'])):'';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent']!= null && $aRow['date_certificate_sent']!= '' && $aRow['date_certificate_sent']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_sent'])):'';
            $row[] = $aRow['certification_type'];
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'edit')) {
               $row[] = "<a href='/certification/edit/".base64_encode($aRow['id'])."'><span class='glyphicon glyphicon-pencil'>Edit</span></a>";
            }
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'pdf')) {
                if (strcasecmp($aRow['final_decision'], 'Certified') == 0) {
                   $row[] = "<a href='/certification/pdf?".urlencode(base64_encode('id'))."=".base64_encode($aRow['id'])."&".urlencode(base64_encode('last'))."=".base64_encode($aRow['last_name'])."&".urlencode(base64_encode('first'))."=".base64_encode($aRow['first_name'])."&".urlencode(base64_encode('middle'))."=".base64_encode($aRow['middle_name'])."&".urlencode(base64_encode('professional_reg_no'))."=".base64_encode($aRow['professional_reg_no'])."&".urlencode(base64_encode('certification_id'))."=".base64_encode($aRow['certification_id'])."&".urlencode(base64_encode('date_issued'))."=".base64_encode($aRow['date_certificate_issued'])."' target='_blank'><span class='glyphicon glyphicon-download-alt'>PDF</span></a>";
                }else{
                   $row[] = "<div><span class='glyphicon glyphicon-download-alt'>PDF</span></div>";
                }
            }
         $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function fetchAllFailedTester($parameters){
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $aColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','middle_name','last_name','final_decision','certification_type');
        $orderColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','final_decision','certification_type');

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
        $sQuery = $sql->select()->from(array('c'=>'certification'))
                               ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email','facility_in_charge_email'),'left')
                               ->where('(c.approval_status IN("rejected","Rejected")) OR (c.final_decision IN("pending","Pending")) OR (c.final_decision IN("failed","Failed"))')
                               ->order('c.id desc');

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
        $tQuery =  $sql->select()->from(array('c'=>'certification'))
                               ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
                               ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                               ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email','facility_in_charge_email'),'left')
                               ->where('(c.approval_status IN("rejected","Rejected")) OR (c.final_decision IN("pending","Pending")) OR (c.final_decision IN("failed","Failed"))')
                               ->order('c.id desc');
        $tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
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
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_type'];
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'edit')) {
               $row[] = "<a href='/certification/edit/".base64_encode($aRow['id'])."'><span class='glyphicon glyphicon-pencil'>Edit</span></a>";
            }
         $output['aaData'][] = $row;
        }
        return $output;
    }
    
    public function getCertificationMapResults($params){
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c'=>'certification'))->columns(array('examination','date_certificate_issued','final_decision'))
                        ->join(array('e'=>'examination'),'e.id=c.examination',array('provider'))
                        ->join(array('p'=>'provider'),'p.id=e.id',array('region'))
                        ->join(array('l'=>'location_details'),'l.location_id=p.region',array('location_name','longitude','latitude','locCount' => new \Zend\Db\Sql\Expression("COUNT(l.location_id)")))
                        ->where(array('final_decision'=>'Certified'))
                        ->group('l.location_name');
        $queryStr = $sql->getSqlStringForSqlObject($query);
        $res = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $res;
    }
}

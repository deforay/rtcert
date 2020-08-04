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

class CertificationTable
{

    protected $tableGateway;
    public $sm = null;

    public function __construct(TableGateway $tableGateway, Adapter $adapter, $sm = null)
    {
        $this->tableGateway = $tableGateway;
        $this->adapter = $adapter;
        $this->sm = $sm;
    }

    public function getQuickStats()
    {
        $sessionLogin = new Container('credo');
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $adapter = $this->adapter;
        $globalDb = new GlobalTable($adapter);
        $monthFlexLimit = $globalDb->getGlobalValue('month-flex-limit');
        $monthFlexLimit =  (trim($monthFlexLimit) != '') ? (int) $monthFlexLimit : 6;
        $query = $sql->select()->from(array('c' => 'certification'))
            ->columns(array(
                "total" => new Expression('COUNT(*)'),
                "certified" => new Expression("SUM(CASE 
                    WHEN (c.date_certificate_issued >= DATE_SUB(NOW(),INTERVAL 24 MONTH) AND c.date_end_validity >= CURDATE() AND c.final_decision = 'Certified') THEN 1
                    ELSE 0
                    END)"),
                "upForCertification" => new Expression("SUM(CASE 
                    WHEN (c.date_end_validity < CURDATE() AND CURDATE() <= DATE_ADD(c.date_end_validity, INTERVAL $monthFlexLimit MONTH) AND c.final_decision = 'Certified') THEN 1
                    ELSE 0
                    END)"),
                "expired" => new Expression("SUM(CASE 
                    WHEN (CURDATE() > DATE_ADD(c.date_end_validity, INTERVAL $monthFlexLimit MONTH) AND c.final_decision = 'Certified') THEN 1
                    ELSE 0
                    END)"),
                "pending" => new Expression("SUM(CASE 
                    WHEN ((c.final_decision = 'Pending' OR c.final_decision = 'Failed')) THEN 1
                    ELSE 0
                    END)")
            ))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.provider', array());
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $query = $query->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $query = $query->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        $queryStr = $sql->getSqlStringForSqlObject($query);
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function getCertificationPieChartResults($params)
    {
        $sessionLogin = new Container('credo');
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c' => 'certification'))
            ->columns(array("total_certification" => new Expression('COUNT(*)')))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.provider', array('region'))
            ->join(array('l_d' => 'location_details'), 'l_d.location_id=p.region', array('location_name'))
            ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND date_end_validity >= NOW()')
            ->group('p.region');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $query = $query->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $query = $query->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        $queryStr = $sql->getSqlStringForSqlObject($query);
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getCertifiedProvinceChartResults($params)
    {

        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c' => 'certification'))
            ->columns(array("total_certification" => new Expression('COUNT(*)')))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.provider', array('region'))
            ->join(array('l_d' => 'location_details'), 'l_d.location_id=p.region', array('location_name'))
            ->where('(c.final_decision = "Certified" OR c.final_decision = "certified")')
            ->group('p.region');

        $queryStr = $sql->getSqlStringForSqlObject($query);
        //    echo $queryStr; die;
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getCertifiedistrictChartResults($params)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c' => 'certification'))
            ->columns(array("total_certification" => new Expression('COUNT(*)')))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.provider', array('district'))
            ->join(array('l_d' => 'location_details'), 'l_d.location_id=p.district', array('location_name'))
            ->where('(c.final_decision = "Certified" OR c.final_decision = "certified")')
            ->group('p.district');
        $queryStr = $sql->getSqlStringForSqlObject($query);
        //   echo $queryStr; die;
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getCertificationBarChartResults($params)
    {
        $sessionLogin = new Container('credo');
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $start = strtotime(date("Y", strtotime("-2 year")) . '-' . date('m', strtotime('+1 month', strtotime('-2 year'))));
        $end = strtotime(date('Y') . '-' . date('m'));
        $j = 0;
        $certificationResult = array();
        while ($start <= $end) {
            $month = date('m', $start);
            $year = date('Y', $start);
            $monthYearFormat = date("M 'y", $start);
            $query = $sql->select()->from(array('c' => 'certification'))
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
                ->join(array('e' => 'examination'), 'e.id=c.examination', array())
                ->join(array('p' => 'provider'), 'p.id=e.provider', array())
                ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND Month(date_certificate_issued)="' . $month . '" AND Year(date_certificate_issued)="' . $year . '" AND date_end_validity > NOW()');
            if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
                $query = $query->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
            } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
                $query = $query->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
            }
            $queryStr = $sql->getSqlStringForSqlObject($query);
            $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            $certificationResult['certification']['Certifications'][$j] = (isset($result[0]["certifications"])) ? $result[0]["certifications"] : 0;
            $certificationResult['certification']['Recertifications'][$j] = (isset($result[0]["recertifications"])) ? $result[0]["recertifications"] : 0;
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
    public function fetchAll()
    {
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
    public function fetchAll2()
    {
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

    public function getCertification($id)
    {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }

    public function getProvider($id)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT provider FROM certification, examination WHERE certification.examination=examination.id AND certification.id=' . $id;
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $provider_id = $res['provider'];
        }
        return $provider_id;
    }

    public function saveCertification(Certification $certification)
    {
        $sessionLogin = new Container('credo');
        $common = new CommonService($this->sm);
        $dbAdapter = $this->adapter;
        $globalDb = new GlobalTable($dbAdapter);
        $monthValid = $globalDb->getGlobalValue('month-valid');
        $validity_end = (isset($monthValid) && trim($monthValid) != '') ? ' + ' . $monthValid . ' month' : ' + 2 year';
        if ($certification->date_certificate_issued == null || $certification->date_certificate_issued = '') {
            $date_issued = date('d-m-Y');
        } else {
            $date_issued = $certification->date_certificate_issued;
        }
        $date_explode = explode("-", $date_issued);
        $newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];
        if (isset($certification->date_certificate_sent) && $certification->date_certificate_sent != '' && $certification->date_certificate_sent != '0000-00-00') {
            $certification->date_certificate_sent = date("Y-m-d", strtotime($certification->date_certificate_sent));
        }
        if ($certification->certification_type == 'Recertification' || $certification->certification_type == 'recertification') {
            $db = $this->tableGateway->getAdapter();
            $sql = 'select date_end_validity from certification, examination, provider WHERE certification.examination=examination.id and examination.provider=provider.id and final_decision="certified" and provider=' . $certification->provider . ' ORDER BY date_certificate_issued DESC LIMIT 1';
            $statement = $db->query($sql);
            $result = $statement->execute();
            foreach ($result as $res) {
                $certification_validity = $res['date_end_validity'];
            }
        }
        if (isset($certification_validity) && $certification_validity != null && $certification_validity != '' && $certification_validity != '0000-00-00') {
            $date_end = date("Y-m-d", strtotime($certification_validity . $validity_end));
        } else {
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
        //die(print_r($data));
        $id = (int) $certification->id;
        if ($id == 0) {
            $data['approval_status'] = 'pending';
            $data['date_end_validity'] = $date_end;
            $data['added_on'] = $common->getDateTime();
            $data['added_by'] = $sessionLogin->userId;
            $data['last_updated_on'] = $common->getDateTime();
            $data['last_updated_by'] = $sessionLogin->userId;
            $this->tableGateway->insert($data);
        } else {
            if ($this->getCertification($id)) {
                $data['last_updated_on'] = $common->getDateTime();
                $data['last_updated_by'] = $sessionLogin->userId;
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('certification id does not exist');
            }
        }
    }

    public function last_id()
    {
        $last_id = $this->tableGateway->lastInsertValue;
        //        die($last_id);
        return $last_id;
    }

    public function updateExamination($last_id)
    {
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

    public function setToActive($last_id)
    {
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

    public function certificationType($provider)
    {
        $db = $this->tableGateway->getAdapter();
        $sql1 = 'select certification_id, date_end_validity from certification, examination, provider WHERE certification.examination=examination.id and examination.provider=provider.id and final_decision="certified" and provider=' . $provider . ' ORDER BY date_certificate_issued DESC LIMIT 1';
        //die($sql1);
        $statement = $db->query($sql1);
        $result = $statement->execute();
        $certification_id = null;
        foreach ($result as $res) {
            $certification_id = $res['certification_id'];
            $date_end_validity = $res['date_end_validity'];
        }
        if (isset($certification_id) && $certification_id != null && $certification_id != '') {
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
            if ($remmonths > 0 && $remmonths > $monthFlexLimit) {
                $certification_id = null;
            }
        }
        // \Zend\Debug\Debug::dump($certification_id);die;
        return $certification_id;
    }

    public function certificationId($provider)
    {
        $db = $this->tableGateway->getAdapter();
        $dbAdapter = $this->adapter;
        $globalDb = new GlobalTable($dbAdapter);
        $sql = 'SELECT MAX(certification_key) as max FROM provider';
        $statement = $db->query($sql);
        $result = $statement->execute();
        $certificationKey = 1;
        foreach ($result as $res) {
            $certificationKey = ($res['max'] + 1);
        }

        $certificatePrefix = ($globalDb->getGlobalValue('certificate-prefix') != null && $globalDb->getGlobalValue('certificate-prefix') != '') ? $globalDb->getGlobalValue('certificate-prefix') : '';
        $certification_id = $certificatePrefix . sprintf("%04d", $certificationKey);
        $sql2 = "UPDATE provider SET certification_id='" . $certification_id . "', certification_key = '.$certificationKey.' WHERE id=" . $provider;

        $db->getDriver()->getConnection()->execute($sql2);
    }

    /**
     * select certified testers who certificate are not yet sent
     * @return type
     */
    public function fetchAll3()
    {
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

    //public function countCertificate() {
    //    $db = $this->tableGateway->getAdapter();
    //    $sqlSelect = 'select COUNT(*)  as nb from  (select certification.id, examination, final_decision, certification_issuer, date_certificate_issued, date_certificate_sent, certification_type, date_end_validity,examination.provider, last_name, first_name, middle_name, certification_id, certification_reg_no, professional_reg_no, email, facility_in_charge_email from certification,examination,provider where examination.id = certification.examination and provider.id = examination.provider and final_decision ="certified" and certificate_sent ="no") as tab';
    //    $statement = $db->query($sqlSelect);
    //    $result = $statement->execute();
    //    foreach ($result as $res) {
    //        $nb = $res['nb'];
    //    }
    //    return $nb;
    //}
    //
    //public function countReminder() {
    //    $db = $this->tableGateway->getAdapter();
    //    $sqlSelect = 'select COUNT(*) as nb2 from (select  certification.id ,examination, final_decision, certification_issuer, date_certificate_issued, 
    //            date_certificate_sent, certification_type, provider,last_name, first_name, middle_name, certification_id,
    //            certification_reg_no, professional_reg_no,email,date_end_validity,facility_in_charge_email from certification, examination, provider where examination.id = certification.examination and provider.id = examination.provider and final_decision="certified" and certificate_sent = "yes" and reminder_sent="no" and datediff(now(),date_end_validity) >=-60 order by certification.id asc) as tab';
    //    $statement = $db->query($sqlSelect);
    //
    //    $result = $statement->execute();
    //    foreach ($result as $res) {
    //        $nb2 = $res['nb2'];
    //    }
    //    return $nb2;
    //}

    public function getNotificationCount()
    {
        $sessionLogin = new Container('credo');
        $where = '';
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $where = ' and provider.district IN(' . implode(',', $sessionLogin->district) . ')';
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $where = ' and provider.region IN(' . implode(',', $sessionLogin->region) . ')';
        }
        $db = $this->tableGateway->getAdapter();
        $sqlSelect = 'select COUNT(*)  as nb from  (select certification.id, examination, final_decision, certification_issuer, date_certificate_issued, date_certificate_sent, certification_type, date_end_validity,examination.provider, last_name, first_name, middle_name, certification_id, certification_reg_no, professional_reg_no, email, facility_in_charge_email from certification,examination,provider where examination.id = certification.examination and provider.id = examination.provider and approval_status IN("approved","Approved") AND final_decision IN("certified","Certified") and certificate_sent ="no"' . $where . ') as tab';
        //echo $sqlSelect;die;
        $statement = $db->query($sqlSelect);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nb = $res['nb'];
        }

        $sqlSelect = 'select COUNT(*) as nb2 from (select  certification.id ,examination, final_decision, certification_issuer, date_certificate_issued, 
                date_certificate_sent, certification_type, provider,last_name, first_name, middle_name, certification_id,
                certification_reg_no, professional_reg_no,email,date_end_validity,facility_in_charge_email from certification, examination, provider where examination.id = certification.examination and provider.id = examination.provider and final_decision="certified" and certificate_sent = "yes" and reminder_sent="no" and datediff(now(),date_end_validity) >=-60' . $where . ' order by certification.id asc) as tab';
        $statement = $db->query($sqlSelect);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nb2 = $res['nb2'];
        }

        return array('nb' => $nb, 'nb2' => $nb2);
    }

    public function CertificateSent($provider)
    {
        $db = $this->tableGateway->getAdapter();
        $sql = "UPDATE certification set certificate_sent='yes' where id=" . $provider;
        $db->getDriver()->getConnection()->execute($sql);
    }

    public function report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility)
    {
        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;

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
        } else {
            if (isset($logincontainer->country) && count($logincontainer->country) > 0 && $roleCode != 'AD') {
                $sql = $sql . ' AND c.country_id IN(' . implode(',', $logincontainer->country) . ')';
            }
        }

        if (!empty($region)) {
            $sql = $sql . ' and l_d_r.location_id=' . $region;
        } else {
            if (isset($logincontainer->region) && count($logincontainer->region) > 0 && $roleCode != 'AD') {
                $sql = $sql . ' AND l_d_r.location_id IN(' . implode(',', $logincontainer->region) . ')';
            }
        }

        if (!empty($district)) {
            $sql = $sql . ' and l_d_d.location_id=' . $district;
        } else {
            if (isset($logincontainer->district) && count($logincontainer->district) > 0 && $roleCode != 'AD') {
                $sql = $sql . ' AND l_d_d.location_id IN(' . implode(',', $logincontainer->district) . ')';
            }
        }

        if (!empty($facility)) {
            $sql = $sql . ' and certification_facilities.id=' . $facility;
        }
        //die($sql);
        //echo $sql;die;
        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getAllActiveCountries()
    {
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

    public function getRegions()
    {
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

    public function SelectTexteHeader()
    {
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

    public function SelectHeaderTextFontSize()
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT header_font_size FROM pdf_header_texte';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $headerTextFontSize = $res['header_font_size'];
        }
        //        die($header_texte);
        return $headerTextFontSize;
    }

    public function insertTextHeader($text, $header_text_size = "")
    {
        $db = $this->tableGateway->getAdapter();

        $sql = 'SELECT count(*) as nombre,id FROM pdf_header_texte';
        $statement = $db->query($sql);
        $result = $statement->execute();
        //var_dump($result);die;
        $id = 0;
        foreach ($result as $res) {
            $nombre = $res['nombre'];
            $id = $res['id'];
        }

        if ($nombre == 0) {
            if (trim($text) != "" && trim($header_text_size) != "") {
                $sql2 = 'insert into pdf_header_texte (header_texte,header_font_size) values ("' . $text . '","' . $header_text_size . '")';
            } elseif (trim($text) != "" && trim($header_text_size) == "") {
                $sql2 = 'insert into pdf_header_texte (header_texte) values ("' . $text . '")';
            } elseif (trim($text) == "" && trim($header_text_size) != "") {
                $sql2 = 'insert into pdf_header_texte (header_font_size) values ("' . $header_text_size . '")';
            }

            $statement2 = $db->query($sql2);
            $result2 = $statement2->execute();
        } else {
            // $sql3 = 'TRUNCATE pdf_header_texte';
            // $statement3 = $db->query($sql3);
            // $result3 = $statement3->execute();

            //$sql2 = 'insert into pdf_header_texte (header_texte,header_font_size) values ("' . $text . '","'.$header_text_size.'")';
            if (trim($text) != "" && trim($header_text_size) != "") {
                $sql2 = 'UPDATE `pdf_header_texte` SET `header_texte`="' . $text . '",`header_font_size`="' . $header_text_size . '" WHERE `id` = ' . $id;
            } elseif (trim($text) != "" && trim($header_text_size) == "") {
                $sql2 = 'UPDATE `pdf_header_texte` SET `header_texte`="' . $text . '" WHERE `id` = ' . $id;
            } elseif (trim($text) == "" && trim($header_text_size) != "") {
                $sql2 = 'UPDATE `pdf_header_texte` SET `header_font_size`="' . $header_text_size . '" WHERE `id` = ' . $id;
            }
            //echo $sql2;die;
            $statement2 = $db->query($sql2);
            $result2 = $statement2->execute();
        }
    }

    public function getCertificationValiditydate($id)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = 'SELECT date_end_validity FROM certification WHERE certification.id = ' . $id;
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $date_end_validity = $res['date_end_validity'];
        }
        return $date_end_validity;
    }

    public function fetchAllRecommended($parameters)
    {
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $acl = $this->sm->get('AppAcl');
        if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'approval')) {
            $aColumns = array('c.id', 'professional_reg_no', 'certification_reg_no', 'certification_id', 'first_name', 'middle_name', 'last_name', 'final_decision', 'certification_issuer', "DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')", "DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')", 'certification_type');
            $orderColumns = array('c.id', 'professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type');
        } else {
            $aColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'first_name', 'middle_name', 'last_name', 'final_decision', 'certification_issuer', "DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')", "DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')", 'certification_type');
            $orderColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type');
        }
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
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email'))
            ->where('c.approval_status IN("pending","Pending")');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $sQuery->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $sQuery->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('c' => 'certification'))
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email'))
            ->where('c.approval_status IN("pending","Pending")');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $tQuery->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $tQuery->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
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
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'approval')) {
                $row[] = '<input class="approvalRow" type="checkbox" id="' . $aRow['id'] . '" onchange="selectForApproval(this);" value="' . $aRow['id'] . '"/>';
            }
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];

            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued'] != null && $aRow['date_certificate_issued'] != '' && $aRow['date_certificate_issued'] != '0000-00-00') ? date("d-M-Y", strtotime($aRow['date_certificate_issued'])) : '';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent'] != null && $aRow['date_certificate_sent'] != '' && $aRow['date_certificate_sent'] != '0000-00-00') ? date("d-M-Y", strtotime($aRow['date_certificate_sent'])) : '';
            $row[] = $aRow['certification_type'];
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function updateCertficateApproval($params)
    {
        $sessionLogin = new Container('credo');
        $common = new CommonService($this->sm);
        $result = false;
        if (isset($params['approvalRow']) && count($params['approvalRow']) > 0) {
            $result = true;
            $db = $this->tableGateway->getAdapter();
            for ($i = 0; $i < count($params['approvalRow']); $i++) {
                $sql = "UPDATE certification SET approval_status='" . $params['status'] . "' AND last_updated_on ='" . $common->getDateTime() . "' AND last_updated_by = '" . $sessionLogin->userId . "' WHERE id = " . $params['approvalRow'][$i];
                $db->getDriver()->getConnection()->execute($sql);
            }
        }
        return $result;
    }

    public function fetchAllToBeSentCertificate($parameters)
    {
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $aColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'first_name', 'middle_name', 'last_name', 'final_decision', 'certification_issuer', "DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')", "DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')", 'certification_type');
        $orderColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type');

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
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_end_validity', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email', 'test_site_in_charge_email'), 'left')
            ->where('c.final_decision IN("certified","Certified") AND certificate_sent ="no"');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $sQuery->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $sQuery->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('c' => 'certification'))
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_end_validity', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email', 'test_site_in_charge_email'), 'left')
            ->where('c.final_decision IN("certified","Certified") AND certificate_sent ="no"');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $tQuery->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $tQuery->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
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
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];

            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued'] != null && $aRow['date_certificate_issued'] != '' && $aRow['date_certificate_issued'] != '0000-00-00') ? date("d-M-Y", strtotime($aRow['date_certificate_issued'])) : '';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent'] != null && $aRow['date_certificate_sent'] != '' && $aRow['date_certificate_sent'] != '0000-00-00') ? date("d-M-Y", strtotime($aRow['date_certificate_sent'])) : '';
            $row[] = $aRow['certification_type'];
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'pdf')) {
                if (strcasecmp($aRow['final_decision'], 'Certified') == 0) {
                    $row[] = "<a href='/certification/pdf?" . urlencode(base64_encode('id')) . "=" . base64_encode($aRow['id']) . "&" . urlencode(base64_encode('last')) . "=" . base64_encode($aRow['last_name']) . "&" . urlencode(base64_encode('first')) . "=" . base64_encode($aRow['first_name']) . "&" . urlencode(base64_encode('middle')) . "=" . base64_encode($aRow['middle_name']) . "&" . urlencode(base64_encode('professional_reg_no')) . "=" . base64_encode($aRow['professional_reg_no']) . "&" . urlencode(base64_encode('certification_id')) . "=" . base64_encode($aRow['certification_id']) . "&" . urlencode(base64_encode('date_issued')) . "=" . base64_encode($aRow['date_certificate_issued']) . "' target='_blank'><span class='glyphicon glyphicon-download-alt'>PDF</span></a>";
                } else {
                    $row[] = "<div></div>";
                }
            }
            if ($acl->isAllowed($role, 'Certification\Controller\CertificationMail', 'index')) {
                $row[] = "<a href='/certification-mail/index?" . urlencode(base64_encode('id')) . "=" . base64_encode($aRow['id']) . "&" . urlencode(base64_encode('provider_id')) . "=" . base64_encode($aRow['provider']) . "&" . urlencode(base64_encode('email')) . "=" . base64_encode($aRow['email']) . "&" . urlencode(base64_encode('certification_id')) . "=" . base64_encode($aRow['certification_id']) . "&" . urlencode(base64_encode('professional_reg_no')) . "=" . base64_encode($aRow['professional_reg_no']) . "&" . urlencode(base64_encode('provider_name')) . "=" . base64_encode($aRow['last_name'] . " " . $aRow['first_name'] . " " . $aRow['middle_name']) . "&" . urlencode(base64_encode('facility_in_charge_email')) . "=" . base64_encode($aRow['facility_in_charge_email']) . "&" . urlencode(base64_encode('test_site_in_charge_email')) . "=" . base64_encode($aRow['test_site_in_charge_email']) . "&" . urlencode(base64_encode('date_certificate_issued')) . "=" . base64_encode($aRow['date_certificate_issued']) . "&" . urlencode(base64_encode('date_end_validity')) . "=" . base64_encode($aRow['date_end_validity']) . "&" . urlencode(base64_encode('key2')) . "=" . base64_encode('key') . "'><span class='glyphicon glyphicon-envelope'></span>&nbsp;Send Certificate</a>";
            }
            if ($acl->isAllowed($role, 'Certification\Controller\CertificationMail', 'index')) {
                $row[] = "<div style='width:120px;height:40px;overflow:auto;'><a href='javascript:void(0);' onclick='markAsSent(\"" . urlencode(base64_encode('certification_id')) . "\",\"" . base64_encode($aRow['id']) . "\",\"" . urlencode(base64_encode('key')) . "\",\"" . base64_encode('key') . "\");'><span class='glyphicon glyphicon-send'></span>&nbsp;Mark as sent</a></div>";
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchAllCertifiedTester($parameters)
    {
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $aColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'first_name', 'middle_name', 'last_name', 'final_decision', 'certification_issuer', "DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')", "DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')", 'certification_type');
        $orderColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type');

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
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'), 'left')
            ->where('c.final_decision IN("certified","Certified")');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $sQuery->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $sQuery->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('c' => 'certification'))
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'), 'left')
            ->where('c.final_decision IN("certified","Certified")');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $tQuery->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $tQuery->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
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
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];

            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued'] != null && $aRow['date_certificate_issued'] != '' && $aRow['date_certificate_issued'] != '0000-00-00') ? date("d-M-Y", strtotime($aRow['date_certificate_issued'])) : '';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent'] != null && $aRow['date_certificate_sent'] != '' && $aRow['date_certificate_sent'] != '0000-00-00') ? date("d-M-Y", strtotime($aRow['date_certificate_sent'])) : '';
            $row[] = $aRow['certification_type'];
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'edit')) {
                $row[] = "<a href='/certification/edit/" . base64_encode($aRow['id']) . "'><span class='glyphicon glyphicon-pencil'>Edit</span></a>";
            }
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'pdf')) {
                if (strcasecmp($aRow['final_decision'], 'Certified') == 0) {
                    $row[] = "<a href='/certification/pdf?" . urlencode(base64_encode('id')) . "=" . base64_encode($aRow['id']) . "&" . urlencode(base64_encode('last')) . "=" . base64_encode($aRow['last_name']) . "&" . urlencode(base64_encode('first')) . "=" . base64_encode($aRow['first_name']) . "&" . urlencode(base64_encode('middle')) . "=" . base64_encode($aRow['middle_name']) . "&" . urlencode(base64_encode('professional_reg_no')) . "=" . base64_encode($aRow['professional_reg_no']) . "&" . urlencode(base64_encode('certification_id')) . "=" . base64_encode($aRow['certification_id']) . "&" . urlencode(base64_encode('date_issued')) . "=" . base64_encode($aRow['date_certificate_issued']) . "' target='_blank'><span class='glyphicon glyphicon-download-alt'>PDF</span></a>";
                } else {
                    $row[] = "<div></div>";
                }
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchAllFailedTester($parameters)
    {
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $aColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'first_name', 'middle_name', 'last_name', 'final_decision', 'certification_type');
        $orderColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_type');

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
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'), 'left');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $sQuery->where('(c.final_decision IN("pending","Pending") AND p.district IN(' . implode(',', $sessionLogin->district) . '))');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $sQuery->where('(c.final_decision IN("pending","Pending") AND p.region IN(' . implode(',', $sessionLogin->region) . '))');
        } else {
            $sQuery->where('c.final_decision IN("pending","Pending")');
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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('c' => 'certification'))
            ->columns(array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_certificate_sent', 'certification_type'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('provider'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email'), 'left');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $tQuery->where('(c.final_decision IN("pending","Pending") AND p.district IN(' . implode(',', $sessionLogin->district) . '))');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $tQuery->where('(c.final_decision IN("pending","Pending") AND p.region IN(' . implode(',', $sessionLogin->region) . '))');
        } else {
            $tQuery->where('c.final_decision IN("pending","Pending")');
        }
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
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['certification_type'];
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'edit')) {
                $row[] = "<a href='/certification/edit/" . base64_encode($aRow['id']) . "'><span class='glyphicon glyphicon-pencil'>Edit</span></a>";
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function getCertificationMapResults($params)
    {
        $sessionLogin = new Container('credo');
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c' => 'certification'))
            ->columns(array('locCount' => new \Zend\Db\Sql\Expression("COUNT(*)")))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.id', array())
            ->join(array('c_f' => 'certification_facilities'), 'c_f.id=p.facility_id', array('facility_name', 'longitude', 'latitude'))
            ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND date_end_validity >= NOW()')
            ->group('p.facility_id');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $query->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $query->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        $queryStr = $sql->getSqlStringForSqlObject($query);
        $facilityResult = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $query = $sql->select()->from(array('c' => 'certification'))
            ->columns(array('regCount' => new \Zend\Db\Sql\Expression("COUNT(*)")))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.id', array())
            ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id=p.region', array('location_name', 'longitude', 'latitude'))
            ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND date_end_validity >= NOW()')
            ->group('p.region');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $query->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $query->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        $queryStr = $sql->getSqlStringForSqlObject($query);
        $provinceResult = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $query = $sql->select()->from(array('c' => 'certification'))->columns(array())
            ->columns(array('districtCount' => new \Zend\Db\Sql\Expression("COUNT(*)")))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array())
            ->join(array('p' => 'provider'), 'p.id=e.id', array())
            ->join(array('l_d_d' => 'location_details'), 'l_d_d.location_id=p.district', array('location_name', 'longitude', 'latitude'))
            ->where('(c.final_decision = "Certified" OR c.final_decision = "certified") AND date_end_validity >= NOW()')
            ->group('p.district');
        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $query->where('p.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $query->where('p.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        $queryStr = $sql->getSqlStringForSqlObject($query);
        $districtResult = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return array('facilityResult' => $facilityResult, 'provinceResult' => $provinceResult, 'districtResult' => $districtResult);
    }

    public function fetchCertificationConfig()
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $adapter = $this->adapter;
        $globalDb = new GlobalTable($adapter);
        $monthValid = $globalDb->getGlobalValue('month-valid');
        $registrarName = $globalDb->getGlobalValue('registrar-name');
        $registrarTitle = $globalDb->getGlobalValue('registrar-title');
        $digitalSignature = $globalDb->getGlobalValue('registrar-digital-signature');
        $translateRegistrate = $globalDb->getGlobalValue('translate-register-title');

        $configDetails = array(
            'month-valid' => $monthValid,
            'registrar-name' => $registrarName,
            'registrar-title' => $registrarTitle,
            'registrar-digital-signature' => $digitalSignature,
            'translate-register-title' => $translateRegistrate,

        );

        return $configDetails;
    }


    public function expiryReport($expirydata, $country, $region, $district)
    {
             
        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;

        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $adapter = $this->adapter;
        $globalDb = new GlobalTable($adapter);
        $monthValid = $globalDb->getGlobalValue('month-valid');
        $registrarName = $globalDb->getGlobalValue('month-flex-limit');
        $upForRecertificationdate=$monthValid-2;
        $didNotRecertifydate=$monthValid+$registrarName;

        $db = $this->tableGateway->getAdapter();

        $sql = 'select certification.certification_issuer,certification.certification_type, certification.date_certificate_issued,certification.date_end_validity, certification.final_decision,provider.certification_reg_no, provider.certification_id, provider.professional_reg_no, provider.first_name, provider.last_name, provider.middle_name,l_d_r.location_name as region_name,l_d_d.location_name as district_name,c.country_name, provider.type_vih_test, provider.phone,provider.email, provider.prefered_contact_method,provider.current_jod, provider.time_worked,provider.test_site_in_charge_name, provider.test_site_in_charge_phone,provider.test_site_in_charge_email, provider.facility_in_charge_name, provider.facility_in_charge_phone, provider.facility_in_charge_email,certification_facilities.facility_name, written_exam.exam_type as written_exam_type,written_exam.exam_admin as written_exam_admin,written_exam.date as written_exam_date,written_exam.qa_point,written_exam.rt_point,written_exam.safety_point,written_exam.specimen_point, written_exam.testing_algo_point, written_exam.report_keeping_point,written_exam.EQA_PT_points, written_exam.ethics_point, written_exam.inventory_point, written_exam.total_points,written_exam.final_score, practical_exam.exam_type as practical_exam_type , practical_exam.exam_admin as practical_exam_admin , practical_exam.Sample_testing_score, practical_exam.direct_observation_score,practical_exam.practical_total_score,practical_exam.date as practical_exam_date from certification,examination,written_exam,practical_exam,provider,location_details as l_d_r,location_details as l_d_d, country as c, certification_facilities WHERE certification.examination= examination.id and examination.id_written_exam= written_exam.id_written_exam and examination.practical_exam_id= practical_exam.practice_exam_id and provider.id=examination.provider and provider.facility_id=certification_facilities.id and provider.region= l_d_r.location_id and provider.district=l_d_d.location_id and l_d_r.country=c.country_id';

        if ($expirydata=='upForRecertification') {
            $syearmonth = date('Y-m', strtotime('first day of -'.$upForRecertificationdate.' month'));
            
            $startDate= $syearmonth.'-01';
            $endDate= $syearmonth.'-'.date('d');
            // $sql = $sql . ' and  certification.date_end_validity between"' . $startDate . '" and "' . $endDate . '"';
            $sql = $sql . ' and certification.date_end_validity<="' . $endDate . '"';
                       
        }
        if ($expirydata=='remindersSent') {
            $sql = $sql . ' and certification.reminder_sent="yes"';
        }

        if ($expirydata=='didNotRecertify') {
            $syearmonth = date('Y-m', strtotime('first day of -'.$didNotRecertifydate.' month'));            
            $startDate= $syearmonth.'-01';
            $endDate= $syearmonth.'-'.date('d');
            // $sql = $sql . ' and  certification.date_end_validity between"' . $startDate . '" and "' . $endDate . '"';
            $sql = $sql . ' and certification.date_end_validity<="' . $endDate . '"';
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
        //  echo $sql; die;
        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }


    public function reportData($parameters)
    {
        
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $roleCode = $sessionLogin->roleCode;

        $decision = $parameters['decision'];
        $typeHiv = $parameters['typeHIV'];
        $jobTitle = $parameters['jobTitle'];
        $dateRange = $parameters['dateRange'];
         if (!empty($dateRange)) {
            $array = explode(" ", $dateRange);
            $startDate = date("Y-m-d", strtotime($array[0]));
            $endDate = date("Y-m-d", strtotime($array[2]));
        } else {
            $startDate = "";
            $endDate = "";
        }
        $country = $parameters['country'];
        $region = $parameters['region'];
        $district = $parameters['district'];
        $excludeTesterName = $parameters['exclude_tester_name'];
        $facility = $parameters['facility'];

        $aColumns = array('first_name', 'professional_reg_no', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_vih_test','current_jod');
       
        $orderColumns = array('first_name', 'professional_reg_no', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_vih_test','current_jod');


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
            ->group('p.id');

        if (!empty($startDate) && !empty($endDate)) {
            $sQuery->where('c.date_certificate_issued >="' . $startDate . '" and c.date_certificate_issued <="' . $endDate . '"');
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
        ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'));

    if (!empty($startDate) && !empty($endDate)) {
        $tQuery->where('c.date_certificate_issued >="' . $startDate . '" and c.date_certificate_issued <="' . $endDate . '"');
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
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['region_name'];
            $row[] = $aRow['district_name'];
            $row[] = $aRow['facility_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['type_vih_test'];
            $row[] = $aRow['current_jod'];
            $output['aaData'][] = $row;
        }
        return $output;
    }




    public function expiryReports($parameters)
    {
        $queryContainer = new Container('query');
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $roleCode = $sessionLogin->roleCode;

        $decision = $parameters['decision'];
        $typeHiv = $parameters['typeHIV'];
        $jobTitle = $parameters['jobTitle'];
        $dateRange = $parameters['dateRange'];
        if (!empty($dateRange)) {
            $array = explode(" ", $dateRange);
            $startDate = date("Y-m-d", strtotime($array[0]));
            $endDate = date("Y-m-d", strtotime($array[2]));
        } else {
            $startDate = "";
            $endDate = "";
        }
        $country = $parameters['country'];
        $region = $parameters['region'];
        $district = $parameters['district'];
        $excludeTesterName = $parameters['exclude_tester_name'];
        $facility = $parameters['facility'];

        $aColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_type','type_vih_test','current_jod');
       
        $orderColumns = array('professional_reg_no', 'certification_reg_no', 'certification_id', 'last_name', 'final_decision', 'certification_type','type_vih_test','current_jod');


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
            ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'));

        if (!empty($startDate) && !empty($endDate)) {
            $sQuery->where('c.date_certificate_issued >="' . $startDate . '" and c.date_certificate_issued <="' . $endDate . '"');
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
        $queryContainer->exportAllEvents = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
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
        ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'));

    if (!empty($startDate) && !empty($endDate)) {
        $tQuery->where('c.date_certificate_issued >="' . $startDate . '" and c.date_certificate_issued <="' . $endDate . '"');
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
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['region_name'];
            $row[] = $aRow['district_name'];
            $row[] = $aRow['facility_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['type_vih_test'];
            $row[] = $aRow['current_jod'];
            $output['aaData'][] = $row;
        }
        return $output;
    }



    public function expiryReportData($parameters)
    {
        
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $roleCode = $sessionLogin->roleCode;

        $country = $parameters['country_id'];
        $region = $parameters['region'];
        $district = $parameters['district'];
        $expirydata = $parameters['expirycertification'];

        $aColumns = array('first_name', 'final_decision', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_vih_test','current_jod');
       
        $orderColumns = array('first_name', 'final_decision', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_vih_test','current_jod');


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
        $globalDb = new GlobalTable($dbAdapter);
        $monthValid = $globalDb->getGlobalValue('month-valid');
        $registrarName = $globalDb->getGlobalValue('month-flex-limit');
        $upForRecertificationdate=$monthValid-2;
        $didNotRecertifydate=$monthValid+$registrarName;
        $sQuery = $sql->select()->from(array('c' => 'certification'))
            ->columns(array('certification_issuer', 'certification_type', 'date_certificate_issued', 'date_end_validity', 'final_decision'))
            ->join(array('e' => 'examination'), 'e.id=c.examination', array('id_written_exam'))
            ->join(array('we' => 'written_exam'), 'we.id_written_exam=e.id_written_exam', array('written_exam_type' => 'exam_type','written_exam_admin' => 'exam_admin','written_exam_date' => 'date','qa_point','rt_point','safety_point','specimen_point','testing_algo_point','report_keeping_point','EQA_PT_points','ethics_point','inventory_point','total_points','final_score'))
            ->join(array('pe' => 'practical_exam'), 'pe.practice_exam_id=e.practical_exam_id', array('practical_exam_type' => 'exam_type','practical_exam_admin' => 'exam_admin','Sample_testing_score','direct_observation_score','practical_total_score','practical_exam_date'=>'date'))
            ->join(array('p' => 'provider'), "p.id=e.provider", array('certification_reg_no', 'certification_id', 'professional_reg_no', 'first_name', 'last_name', 'middle_name', 'type_vih_test', 'phone','email','prefered_contact_method','current_jod','time_worked','test_site_in_charge_name','test_site_in_charge_phone','test_site_in_charge_email','facility_in_charge_name','facility_in_charge_phone','facility_in_charge_email'), 'left')
            ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id=p.region', array('region_name' => 'location_name'))
            ->join(array('l_d_d' => 'location_details'), 'l_d_d.location_id=p.district', array('district_name' => 'location_name'))
            ->join(array('country' => 'country'), 'country.country_id=l_d_r.country', array('country_name'))       
            ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'));

            if ($expirydata=='upForRecertification') {
                $syearmonth = date('Y-m', strtotime('first day of -'.$upForRecertificationdate.' month'));
                $startDate= $syearmonth.'-01';
                $endDate= $syearmonth.'-'.date('d');
                // $sql = $sql . ' and certification.date_end_validity<="' . $endDate . '"';
                $sQuery->where('c.date_end_validity<="'.$endDate.'"');
                           
            }
            if ($expirydata=='remindersSent') {
                $sQuery->where(array('c.reminder_sent'=>'yes'));
            }
    
            if ($expirydata=='didNotRecertify') {
                $syearmonth = date('Y-m', strtotime('first day of -'.$didNotRecertifydate.' month'));            
                $startDate= $syearmonth.'-01';
                $endDate= $syearmonth.'-'.date('d');
                $sQuery->where('c.date_end_validity<="'.$endDate.'"');
            }
          
        if (!empty($parameters['country'])) {
            $sQuery->where(array('country.country_id'=>$parameters['country']));
        }else{
            if (isset($sessionLogin->country) && count($sessionLogin->country) > 0 && $roleCode != 'AD') {
                    $sQuery->where('(country.country_id IN(' . implode(',', $sessionLogin->country) . '))');
            }
        }
        if (!empty($parameters['region'])) {
            $sQuery->where(array('l_d_r.location_id'=>$parameters['region']));
        }else{
            if (isset($sessionLogin->region) && count($sessionLogin->region) > 0 && $roleCode != 'AD') {
                    $sQuery->where('(l_d_r.location_id IN(' . implode(',', $sessionLogin->country) . '))');
            }
        }
        if (!empty($parameters['district'])) {
            $sQuery->where(array('l_d_d.location_id'=>$parameters['district']));
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
        ->join(array('cf' => 'certification_facilities'), 'cf.id=p.facility_id', array('facility_name'));

        if ($expirydata=='upForRecertification') {
            $syearmonth = date('Y-m', strtotime('first day of -'.$upForRecertificationdate.' month'));
            $startDate= $syearmonth.'-01';
            $endDate= $syearmonth.'-'.date('d');
            // $sql = $sql . ' and certification.date_end_validity<="' . $endDate . '"';
            $tQuery->where('c.date_end_validity<="'.$endDate.'"');
                       
        }
        if ($expirydata=='remindersSent') {
            $tQuery->where(array('c.reminder_sent'=>'yes'));
        }

        if ($expirydata=='didNotRecertify') {
            $syearmonth = date('Y-m', strtotime('first day of -'.$didNotRecertifydate.' month'));            
            $startDate= $syearmonth.'-01';
            $endDate= $syearmonth.'-'.date('d');
            $tQuery->where('c.date_end_validity<="'.$endDate.'"');
        }
        if (!empty($parameters['country'])) {
            $tQuery->where(array('country.country_id'=>$parameters['country']));
        }else{
            if (isset($sessionLogin->country) && count($sessionLogin->country) > 0 && $roleCode != 'AD') {
                $tQuery->where('(country.country_id IN(' . implode(',', $sessionLogin->country) . '))');
            }
        }
        if (!empty($parameters['region'])) {
            $tQuery->where(array('l_d_r.location_id'=>$parameters['region']));
        }else{
            if (isset($sessionLogin->region) && count($sessionLogin->region) > 0 && $roleCode != 'AD') {
                $tQuery->where('(l_d_r.location_id IN(' . implode(',', $sessionLogin->country) . '))');
            }
        }
        if (!empty($parameters['district'])) {
            $tQuery->where(array('l_d_d.location_id'=>$parameters['district']));
        }else{
            if (isset($sessionLogin->district) && count($sessionLogin->district) > 0 && $roleCode != 'AD') {
                $tQuery->where('(l_d_d.location_id IN(' . implode(',', $sessionLogin->district) . '))');
            }
        }
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
            $row[] = $aRow['first_name'] . ' ' . $aRow['last_name'];
            $row[] = $aRow['final_decision'];
            $row[] = $aRow['region_name'];
            $row[] = $aRow['district_name'];
            $row[] = $aRow['facility_name'];
            $row[] = $aRow['type_vih_test'];
            $row[] = $aRow['current_jod'];
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function getProviderDetailsByCertifyId($id)
    {
        $common = new CommonService($this->sm);
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('c'=>'certification'))->columns(array('certifyId'=>'id','date_certificate_issued','date_end_validity'))
        ->join(array('e' => 'examination'),'c.examination=e.id',array('add_to_certification'))
        ->join(array('p' => 'provider'),'e.provider=p.id',array('providerId'=>'id','first_name','middle_name','last_name','email','test_link_send', 'link_send_count','certification_id','profile_picture'))
        ->where(array('c.id' =>$id))
        ->group('p.id');
        $queryStr = $sql->getSqlStringForSqlObject($query);
        // die($queryStr);
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
}

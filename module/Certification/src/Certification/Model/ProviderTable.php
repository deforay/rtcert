<?php

namespace Certification\Model;

use Zend\Session\Container;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Select;
use \Application\Model\GlobalTable;
use \Application\Model\TestConfigTable;
use \Application\Model\MailTemplateTable;
use \Application\Service\CommonService;
use Zend\Db\Sql\Ddl\Column\Datetime;
use Zend\Debug\Debug;
use Zend\Db\Sql\Expression;
use PHPExcel;

class ProviderTable extends AbstractTableGateway {

    private $tableGateway;
    public $sm = null;

    public function __construct(TableGateway $tableGateway, Adapter $adapter, $sm=null) {
        $this->tableGateway = $tableGateway;
        $this->adapter = $adapter;
        $this->sm = $sm;
    }

    public function fetchAll() {
        $logincontainer = new Container('credo');
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('id', 'certification_reg_no', 'certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'region', 'district', 'type_vih_test', 'phone', 'email', 'prefered_contact_method', 'current_jod', 'time_worked', 'username', 'password', 'test_site_in_charge_name', 'test_site_in_charge_phone', 'test_site_in_charge_email', 'facility_in_charge_name', 'facility_in_charge_phone', 'facility_in_charge_email', 'facility_id','link_send_count'));
        $sqlSelect->join('certification_facilities', ' certification_facilities.id = provider.facility_id ', array('facility_name', 'facility_address'))
                  ->join(array('l_d_r'=>'location_details'), 'l_d_r.location_id = provider.region', array('region_name'=>'location_name'))
                  ->join(array('l_d_d'=>'location_details'), 'l_d_d.location_id = provider.district', array('district_name'=>'location_name'))
                  ->join(array('e'=>'examination'), 'e.provider = provider.id ', array('examid'=>'id'), 'left')
                  ->join(array('c'=>'certification'), 'c.examination = e.id', array('certid'=>'id','final_decision','date_certificate_issued','date_end_validity'),'left');
                  //->group('e.provider');
        $sqlSelect->order('provider.added_on desc')
                  ->order('c.date_certificate_issued desc');
        if(isset($logincontainer->district) && count($logincontainer->district) > 0){
            $sqlSelect->where('provider.district IN('.implode(',',$logincontainer->district).')');
        }else if(isset($logincontainer->region) && count($logincontainer->region) > 0){
            $sqlSelect->where('provider.region IN('.implode(',',$logincontainer->region).')');
        }else if(isset($logincontainer->country) && count($logincontainer->country) > 0){
            $sqlSelect->where('l_d_r.country IN('.implode(',',$logincontainer->country).')');
        }
        $resultSet = $this->tableGateway->selectWith($sqlSelect);
        return $resultSet;
    }

    public function getProvider($id) {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }

    public function saveProvider(\Certification\Model\Provider $provider) {
        // Debug::dump($provider);die;
        $sessionLogin = new Container('credo');
        $common = new CommonService($this->sm);
        $last_name = strtoupper($provider->last_name);
        $first_name = strtoupper($provider->first_name);
        $middle_name = strtoupper($provider->middle_name);
        $region = ucfirst($provider->region);
        $district = ucfirst($provider->district);
        $test_site_in_charge_name = strtoupper($provider->test_site_in_charge_name);
        $facility_in_charge_name = strtoupper($provider->facility_in_charge_name);
        $password = '';
        if(isset($provider->password) && $provider->password != ''){
            $config = new \Zend\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $password = sha1($provider->password . $configResult["password"]["salt"]);
        }

        $db = $this->tableGateway->getAdapter();
        $sql = 'SELECT MAX(certification_reg_no) as max FROM provider';
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $max = $res['max'];
        }
        $array = explode("R", $max);
        $array2 = explode("-", $max);

        if (date('Y') > $array2[0]) {
            $certification_reg_no = date('Y') . '-R' . substr_replace("0000", 1, -strlen(1));
        } else {
            $certification_reg_no = $array2[0] . '-R' . substr_replace("0000", ($array[1] + 1), -strlen(($array[1] + 1)));
        }
        $data = array(
            'professional_reg_no' => $provider->professional_reg_no,
            'last_name' => $last_name,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'region' => $region,
            'district' => $district,
            'type_vih_test' => $provider->type_vih_test,
            'phone' => $provider->phone,
            'email' => $provider->email,
            'prefered_contact_method' => $provider->prefered_contact_method,
            'current_jod' => $provider->current_jod,
            'time_worked' => $provider->time_worked,
            'username' => $provider->username,
            'password' => $password,
            'test_site_in_charge_name' => $test_site_in_charge_name,
            'test_site_in_charge_phone' => $provider->test_site_in_charge_phone,
            'test_site_in_charge_email' => $provider->test_site_in_charge_email,
            'facility_in_charge_name' => $facility_in_charge_name,
            'facility_in_charge_phone' => $provider->facility_in_charge_phone,
            'facility_in_charge_email' => $provider->facility_in_charge_email,
            'facility_id' => $provider->facility_id
        );
        $id = (int) $provider->id;
        $certification_id = $provider->certification_id;

        if ($id == 0 && !$certification_id) {
            $data['certification_reg_no'] = $certification_reg_no;
            $data['added_on'] = $common->getDateTime();
            $data['added_by'] = $sessionLogin->userId;
            $data['last_updated_on'] = $common->getDateTime();
            $data['last_updated_by'] = $sessionLogin->userId;
            $this->tableGateway->insert($data);
            $id = $this->tableGateway->lastInsertValue;
        } else {
            if ($this->getProvider($id)) {
                $data['certification_id'] = $provider->certification_id;
                $data['last_updated_on'] = $common->getDateTime();
                $data['last_updated_by'] = $sessionLogin->userId;
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('Provider id does not exist');
            }
        }
        if (isset($provider->profile_picture['name']) && $provider->profile_picture['name'] != '') {
            $pathname = UPLOAD_PATH . DIRECTORY_SEPARATOR . "tester-proile" . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'pic';
            if (!file_exists($pathname) && !is_dir($pathname)) {
                mkdir($pathname, 0777, true);
            }
            $extension = strtolower(pathinfo(UPLOAD_PATH . DIRECTORY_SEPARATOR . $provider->profile_picture['name'], PATHINFO_EXTENSION));
            $imageName = $common->generateRandomString(4, 'alphanum') . "." . $extension;

            if (move_uploaded_file($provider->profile_picture['tmp_name'], $pathname . DIRECTORY_SEPARATOR . $imageName)) {
                $this->tableGateway->update(array('profile_picture' => $imageName), "id=" . $id);
            }
        }
    }

    /**
     * to get facilities list
     * @param type $q (district id)
     * @return type array
     */
     public function getRegion($params) {
        $logincontainer = new Container('credo');
        $regionWhere = '';
        if(isset($logincontainer->region) && count($logincontainer->region) > 0){
            $regionWhere = ' AND location_id IN('.implode(',',$logincontainer->region).')';
        }
        $db = $this->tableGateway->getAdapter();
        $sql = "SELECT location_id, location_name FROM location_details WHERE parent_location = 0 AND country ='" . $params['q'] . "'".$regionWhere;
        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }
    
     public function getDistrict($q) {
        $logincontainer = new Container('credo');
        $districtWhere = '';
        if(isset($logincontainer->district) && count($logincontainer->district) > 0){
            $districtWhere = ' AND location_id IN('.implode(',',$logincontainer->district).')';
        }
        $db = $this->tableGateway->getAdapter();
        $sql = "SELECT location_id, location_name FROM location_details WHERE parent_location = '" . $q . "'".$districtWhere;
        $statement = $db->query($sql);
        $result = $statement->execute();
//        print_r($result);
        return $result;
    }
    
    public function getFacility($q) {
        $db = $this->tableGateway->getAdapter();
        $sql = "SELECT id, facility_name, district FROM certification_facilities where district='" . $q . "'";
        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getCountryIdbyRegion($location) {
        $db = $this->tableGateway->getAdapter();
        $sql = "SELECT country FROM location_details WHERE location_id ='" . $location . "'";
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $country_id = $res['country'];
        }
//       die(print_r($id));
        return array('country_id' => $country_id);
    }

    public function getAllActiveCountries(){
        $logincontainer = new Container('credo');
        $countryWhere = 'WHERE country_status = "active"';
        if(isset($logincontainer->country) && count($logincontainer->country) > 0){
            $countryWhere = 'WHERE country_id IN('.implode(',',$logincontainer->country).') AND country_status = "active"';
        }
        $db = $this->tableGateway->getAdapter();
        $sql = 'SELECT country_id, country_name FROM country '.$countryWhere.' ORDER by country_name asc';
        $statement = $db->query($sql);
        $countryResult = $statement->execute();
        return $countryResult;
    }
    
    public function deleteProvider($id) {
        $this->tableGateway->delete(array('id' => (int) $id));
    }

    public function foreigne_key($provider_id) {
        $db = $this->tableGateway->getAdapter();
        $sql = 'SELECT COUNT(provider_id) as nombre from written_exam  WHERE provider_id=' . $provider_id;
        $sql2 = 'SELECT COUNT(provider_id) as nombre from practical_exam  WHERE provider_id=' . $provider_id;
        $sql3 = 'SELECT COUNT(provider_id) as nombre from training  WHERE provider_id=' . $provider_id;
        $statement = $db->query($sql);
        $statement2 = $db->query($sql2);
        $statement3 = $db->query($sql3);

        $result = $statement->execute();
        $result2 = $statement2->execute();
        $result3 = $statement3->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        foreach ($result2 as $res2) {
            $nombre2 = $res2['nombre'];
        }
        foreach ($result3 as $res3) {
            $nombre3 = $res3['nombre'];
        }
        return array(
            'nombre' => $nombre,
            'nombre2' => $nombre2,
            'nombre3' => $nombre3
        );
    }

    public function report($country, $region, $district, $facility, $typeHiv, $contact_method, $jobTitle) {
        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;

        $db = $this->tableGateway->getAdapter();
        $sql = 'select provider.certification_reg_no, provider.certification_id, provider.professional_reg_no, provider.first_name, provider.last_name, provider.middle_name, l_d_r.location_name as region_name, l_d_d.location_name as district_name, c.country_name, provider.type_vih_test, provider.phone,provider.email, provider.prefered_contact_method,provider.current_jod, provider.time_worked,provider.username,provider.password,provider.test_site_in_charge_name, provider.test_site_in_charge_phone,provider.test_site_in_charge_email, provider.facility_in_charge_name, provider.facility_in_charge_phone, provider.facility_in_charge_email,certification_facilities.facility_name FROM provider, certification_facilities, country as c, location_details as l_d_r, location_details as l_d_d WHERE provider.facility_id=certification_facilities.id and provider.region= l_d_r.location_id and provider.district=l_d_d.location_id and l_d_r.country=c.country_id';
        
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

        if (!empty($typeHiv)) {
            $sql = $sql . ' and provider.type_vih_test="' . $typeHiv . '"';
        }
        
        if (!empty($contact_method)) {
            $sql = $sql . ' and prefered_contact_method="' . $contact_method . '"';
        }
        
        if (!empty($jobTitle)) {
            $sql = $sql . ' and provider.current_jod="' . $jobTitle . '"';
        }

//die($sql);
        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }
    
    public function fetchTesters($parameters){
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        
        $aColumns = array('professional_reg_no','certification_reg_no','certification_id','first_name','middle_name','last_name','facility_name','certification_issuer',"DATE_FORMAT(date_certificate_issued,'%d-%b-%Y')","DATE_FORMAT(date_certificate_sent,'%d-%b-%Y')",'certification_type');
        $orderColumns = array('professional_reg_no','certification_reg_no','certification_id','last_name','facility_name','certification_issuer','date_certificate_issued','date_certificate_sent','certification_type');

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
        $globalDb = new GlobalTable($dbAdapter);
        $monthFlexLimit = $globalDb->getGlobalValue('month-flex-limit');
        $monthFlexLimit =  (trim($monthFlexLimit)!= '')?(int)$monthFlexLimit:6;
        $sQuery = $tQuery = $sql->select()->from(array('p'=>'provider'))
                                ->columns(array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email','test_site_in_charge_email'))
                                ->join(array('e'=>'examination'), ' e.provider = p.id ', array('provider'))
                                ->join(array('c'=>'certification'), ' c.examination = e.id',array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_end_validity', 'date_certificate_sent', 'certification_type'))
                                ->join(array('c_f'=>'certification_facilities'), ' c_f.id = p.facility_id ', array('facility_name'));
                                //->join(array('l_d_r'=>'location_details'), 'l_d_r.location_id = p.region', array('region_name'=>'location_name'))
                                //->join(array('l_d_d'=>'location_details'), 'l_d_d.location_id = p.district', array('district_name'=>'location_name'));
        if(isset($sessionLogin->district) && count($sessionLogin->district) > 0){
            $sQuery->where('p.district IN('.implode(',',$sessionLogin->district).')');
        }else if(isset($sessionLogin->region) && count($sessionLogin->region) > 0){
            $sQuery->where('p.region IN('.implode(',',$sessionLogin->region).')');
        }
        
        if(isset($parameters['fromSource']) && trim($parameters['fromSource']) == 'up-for-recertificate'){
            $sQuery->where("c.date_end_validity < CURDATE() AND CURDATE() <= DATE_ADD(c.date_end_validity, INTERVAL $monthFlexLimit MONTH) AND c.final_decision = 'Certified'");
        }else{
           $sQuery->where("c.date_certificate_issued >= DATE_SUB(NOW(),INTERVAL 24 MONTH) AND c.date_end_validity >= CURDATE() AND c.final_decision = 'Certified'"); 
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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $sql = new Sql($dbAdapter);
        $tQuery = $sql->select()->from(array('p'=>'provider'))
                                ->columns(array('last_name', 'first_name', 'middle_name', 'certification_id', 'certification_reg_no', 'professional_reg_no', 'email', 'facility_in_charge_email','test_site_in_charge_email'))
                                ->join(array('e'=>'examination'), ' e.provider = p.id ', array('provider'))
                                ->join(array('c'=>'certification'), ' c.examination = e.id',array('id', 'examination', 'final_decision', 'certification_issuer', 'date_certificate_issued', 'date_end_validity', 'date_certificate_sent', 'certification_type'))
                                ->join(array('c_f'=>'certification_facilities'), ' c_f.id = p.facility_id ', array('facility_name'));
                                //->join(array('l_d_r'=>'location_details'), 'l_d_r.location_id = p.region', array('region_name'=>'location_name'))
                                //->join(array('l_d_d'=>'location_details'), 'l_d_d.location_id = p.district', array('district_name'=>'location_name'));
        if(isset($sessionLogin->district) && count($sessionLogin->district) > 0){
            $tQuery->where('p.district IN('.implode(',',$sessionLogin->district).')');
        }else if(isset($sessionLogin->region) && count($sessionLogin->region) > 0){
            $tQuery->where('p.region IN('.implode(',',$sessionLogin->region).')');
        }
        if(isset($parameters['fromSource']) && trim($parameters['fromSource']) == 'up-for-recertificate'){
            $tQuery->where("c.date_end_validity < CURDATE() AND CURDATE() <= DATE_ADD(c.date_end_validity, INTERVAL $monthFlexLimit MONTH) AND c.final_decision = 'Certified'");
        }else{
            $tQuery->where("c.date_certificate_issued >= DATE_SUB(NOW(),INTERVAL 24 MONTH) AND c.date_end_validity >= CURDATE() AND c.final_decision = 'Certified'"); 
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
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = ucwords($aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name']);
            $row[] = ucwords($aRow['facility_name']);
            $row[] = $aRow['certification_issuer'];
            $row[] = (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued']!= null && $aRow['date_certificate_issued']!= '' && $aRow['date_certificate_issued']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_issued'])):'';
            $row[] = (isset($aRow['date_certificate_sent']) && $aRow['date_certificate_sent']!= null && $aRow['date_certificate_sent']!= '' && $aRow['date_certificate_sent']!= '0000-00-00')?date("d-M-Y", strtotime($aRow['date_certificate_sent'])):'';
            $row[] = $aRow['certification_type'];
            if(isset($parameters['fromSource']) && trim($parameters['fromSource']) == 'up-for-recertificate'){
                if ($acl->isAllowed($role, 'Certification\Controller\CertificationMail', 'index')) {
                    $row[] = "<a href='/certification-mail/index?".urlencode(base64_encode('id'))."=".base64_encode($aRow['id'])."&".urlencode(base64_encode('provider_id'))."=".base64_encode($aRow['provider'])."&".urlencode(base64_encode('email'))."=".base64_encode($aRow['email'])."&".urlencode(base64_encode('certification_id'))."=".base64_encode($aRow['certification_id'])."&".urlencode(base64_encode('provider_name'))."=".base64_encode($aRow['last_name'] . " " . $aRow['first_name'] . " " . $aRow['middle_name'])."&".urlencode(base64_encode('date_certificate_issued'))."=".base64_encode($aRow['date_certificate_issued'])."&".urlencode(base64_encode('date_end_validity'))."=".base64_encode($aRow['date_end_validity'])."&".urlencode(base64_encode('facility_in_charge_email'))."=".base64_encode($aRow['facility_in_charge_email'])."'><span class='glyphicon glyphicon-envelope'></span>&nbsp; Send Reminder </a>";
                }
            }else{
                if ($acl->isAllowed($role, 'Certification\Controller\CertificationMail', 'index')) {
                    $row[] = "<a href='/certification-mail/index?".urlencode(base64_encode('id'))."=".base64_encode($aRow['id'])."&".urlencode(base64_encode('provider_id'))."=".base64_encode($aRow['provider'])."&".urlencode(base64_encode('email'))."=".base64_encode($aRow['email'])."&".urlencode(base64_encode('certification_id'))."=".base64_encode($aRow['certification_id'])."&".urlencode(base64_encode('professional_reg_no'))."=".base64_encode($aRow['professional_reg_no'])."&".urlencode(base64_encode('provider_name'))."=".base64_encode($aRow['last_name'] . " " . $aRow['first_name'] . " " . $aRow['middle_name'])."&".urlencode(base64_encode('facility_in_charge_email'))."=".base64_encode($aRow['facility_in_charge_email'])."&".urlencode(base64_encode('test_site_in_charge_email'))."=".base64_encode($aRow['test_site_in_charge_email'])."&".urlencode(base64_encode('date_certificate_issued'))."=".base64_encode($aRow['date_certificate_issued'])."&".urlencode(base64_encode('date_end_validity'))."=".base64_encode($aRow['date_end_validity'])."&".urlencode(base64_encode('key2'))."=".base64_encode('key')."'><span class='glyphicon glyphicon-envelope'></span>&nbsp;Send Certificate</a>";
                }
            }
            
         $output['aaData'][] = $row;
        }
        return $output;
    }

    public function getTesterTestHistoryDetails($tester){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //fetching written exam list
        $writtenExamQuery = $sql->select()->from(array('w_ex'=>'written_exam'))
                                ->columns(array('id_written_exam', 'exam_type', 'provider_id', 'exam_admin', 'date', 'qa_point', 'rt_point',
            'safety_point', 'specimen_point', 'testing_algo_point', 'report_keeping_point', 'EQA_PT_points', 'ethics_point', 'inventory_point', 'total_points', 'final_score'))
                                ->where(array('w_ex.provider_id'=>$tester));
        $writtenExamQueryStr = $sql->getSqlStringForSqlObject($writtenExamQuery);
        $writtenExamResult = $dbAdapter->query($writtenExamQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        //fetching practical exam list
        $practicalExamQuery = $sql->select()->from(array('p_ex'=>'practical_exam'))
                                  ->columns(array('practice_exam_id', 'exam_type', 'exam_admin', 'provider_id', 'Sample_testing_score', 'direct_observation_score', 'practical_total_score', 'date'))
                                  ->where(array('p_ex.provider_id'=>$tester));
        $practicalExamQueryStr = $sql->getSqlStringForSqlObject($practicalExamQuery);
        $practicalExamResult = $dbAdapter->query($practicalExamQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return array('writtenExamResult'=>$writtenExamResult,'practicalExamResult'=>$practicalExamResult);
    }

    public function loginProviderDetails($params,$type=""){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');
        if($params['username'] == '' || $params['password'] == ''){
            $container = new Container('alert');
            $container->alertMsg = 'Please enter username and password to login';
            return '/provider/logout';
        }
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if($type == 'token'){
            $password = $params['password'];
        }else{
            $password = sha1($params['password'] . $configResult["password"]["salt"]);
        }
        $loginQuery = $sql->select()->from('provider')->where(array('username' => $params['username'], 'password' => $password));
        $loginStr = $sql->getSqlStringForSqlObject($loginQuery);
        $response = $dbAdapter->query($loginStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if($response){
            $logincontainer->roleName = 'RT Providers';
            $logincontainer->userId = $response['id'];
            $logincontainer->login = $response['username'];
            $logincontainer->roleId = 6;
            $logincontainer->roleCode = 'provider';
            /* $logincontainer->district = 0;
            $logincontainer->region = 0;
            $logincontainer->country = 0; */
            return '/test/intro';
        }else{
            $container = new Container('alert');
            if($type == 'token'){
                $container->alertMsg = "You don't have a login credetnail kindly check RT Certification admin";
            }else{
                $container->alertMsg = 'Username or password incorrect. Please try again';
            }
            return '/provider/logout';
        }
    }

    public function saveLinkSend($params){
        $globalDb = new GlobalTable($this->adapter);
        $countryName = $globalDb->getGlobalValue('country-name');
        $sessionLogin = new Container('credo');
        $common = new CommonService($this->sm);
        $provider = $this->getProvider(base64_decode($params['providerId']));
        $update = 0;
        $token = $common->generateRandomString(8);
        if ($provider) {
            $data['link_token']     = $token;
            $data['link_send_count']= (isset($provider->link_send_count) && $provider->link_send_count != '')?($provider->link_send_count+1):1;
            $data['link_send_on']   = $common->getDateTime();
            $data['link_send_by']   = $sessionLogin->userId;
            $update = $this->tableGateway->update($data, array('id' => base64_decode($params['providerId'])));
            if($update > 0){
                $result['countryName'] = $countryName;
                $result['provider'] = $this->getProvider(base64_decode($params['providerId']));
                return $result;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function getProviderByToken($tester){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $checkedRow = array();
        
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $loginQuery = $sql->select()->from('provider')->where('link_token != "" AND link_token IS NOT NULL');
        $loginStr = $sql->getSqlStringForSqlObject($loginQuery);
        $result = $dbAdapter->query($loginStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        /* Cehck the tester token */
        foreach($result as $row){
            // Debug::dump($tester);
            $linkEncode = $row['link_token'] . $configResult["password"]["salt"];
            // Debug::dump(hash('sha256', $linkEncode) .' -> '.$row['email']);
            if($tester == hash('sha256', $linkEncode)){
                $checkedRow = $row;
            }
        }
        // die;
        $testConfigDb = new TestConfigTable($dbAdapter);
        $linkExpire = $testConfigDb->fetchTestValue('link-expire');
        /* To cehck the config hour */
        if(isset($checkedRow) && count($checkedRow) > 0){
            $hour = abs(strtotime(date('Y-m-d H:i:s')) - strtotime($checkedRow['link_send_on']))/(60*60);
            if($linkExpire < round($hour)){
                return false;
            }
            return $checkedRow;
        }else{
            return false;
        }
    }

    public function sendAutoTestLink()
    {
        $common = new CommonService($this->sm);
        $dbAdapter = $this->tableGateway->getAdapter();
        $sql = new Sql($dbAdapter);
        $certifyId = array();
        /* Get global value */
        $globalDb = new GlobalTable($this->adapter);
        $testConfigDb = new TestConfigTable($this->adapter);
        $mailTemplateDb = new MailTemplateTable($this->adapter);
        $countryName = $globalDb->getGlobalValue('country-name');
        $days = $globalDb->getGlobalValue('certificate-alert-days');
        $expire = $testConfigDb->fetchTestValue('link-expire');
        $expire = (isset($expire) && $expire > 0)?$expire:24;
        /* Compare expiry days */
        $compareDate = date('Y-m-d',strtotime('-'.$days.' DAYS'));

        $query = $sql->select()->from(array('c'=>'certification'))->columns(array('certifyId'=>'id','date_certificate_issued','date_end_validity'))
        ->join(array('e' => 'examination'),'c.examination=e.id',array('add_to_certification'))
        ->join(array('p' => 'provider'),'e.provider=p.id',array('providerId'=>'id','first_name','middle_name','last_name','email','test_link_send', 'link_send_count','certification_id'))
        ->where(array('p.link_token like ""', 'c.date_end_validity >= "'.$compareDate.'"','p.test_link_send'=>'no','p.certification_id not like ""',))
        ->group('p.id');
        $queryStr = $sql->getSqlStringForSqlObject($query);
        // die($queryStr);
        $providerResult = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(count($providerResult) > 0){
            /* Mail services start */
            $config = new \Zend\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $mainSearch = array('##USER##', '##CERTIFICATE_NUMBER##', '##CERTIFICATE_EXPIRY_DATE##', '##URLWITHOUTLINK##', '##EXPIRY_HOURS##' ,'##COUNTRY##');
            $mailTemplatesDetals = $mailTemplateDb->fetchMailTemplateByPurpose('certificate-reminder');
            $fromMail   = $mailTemplatesDetals['mail_from'];
            $fromName   = $mailTemplatesDetals['from_name'];
            $subject    = $mailTemplatesDetals['mail_subject'];
            $cc         = $configResult['provider']['to']['cc'];
            $bcc        = "";
            
            foreach($providerResult as $provider){
                $token = $common->generateRandomString(8);
                $data['link_token']     = $token;
                $data['test_link_send'] = 'yes';
                $data['link_send_count']= (isset($provider['link_send_count']) && $provider['link_send_count'] != '')?($provider['link_send_count']+1):1;;
                $data['link_send_on']   = $common->getDateTime();
                $data['link_send_by']   = 0;
                $this->tableGateway->update($data, array('id' => $provider['providerId']));
                /* Insert content to temp mail */
                $to = $provider['email'];
                
                $linkEncode = $token . $configResult["password"]["salt"];
                $key = hash('sha256', $linkEncode);
                $mailReplace = array(
                    $provider['first_name'].' '.$provider['last_name'], 
                    $provider['certification_id'],
                    $provider['date_end_validity'],
                    "".$configResult['domain']."/provider/login?u=".$key."",
                    $expire,
                    $countryName
                );

                $mailContent = trim($mailTemplatesDetals['mail_content']);
                
                $message = str_replace($mainSearch, $mailReplace, $mailContent);
                $message = str_replace("&nbsp;", "", strval($message));
                $message = str_replace("&amp;nbsp;", "", strval($message));
                $message = html_entity_decode($message . $mailTemplatesDetals['mail_footer'], ENT_QUOTES, 'UTF-8');
                $common->insertTempMail($to, $subject, $message, $fromMail, $fromName, $cc, $bcc);
                $certifyId[] = $provider['certifyId'];
            }
        }
        return $certifyId;
    }

    public function updateTestMailSendStatus($id = '')
    {
        if(isset($id) && $id != ''){
            $id = $id;
        } else{
            $logincontainer = new Container('credo');
            $id = $logincontainer->userId;
        }
        $this->tableGateway->update(array('test_mail_send' => 'yes'), array('id' => $id));
    }

    public function getFacilityByName($name){
        $dbAdapter         = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql               = new Sql($dbAdapter);
        $query = $sql->select()->from('certification_facilities')->where(array('facility_name LIKE "%'.$name.'%"'));
        $queryStr = $sql->getSqlStringForSqlObject($query);
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
    
    public function getLocationByName($name){
        $dbAdapter         = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql               = new Sql($dbAdapter);
        $query = $sql->select()->from('location_details')->where(array('location_name LIKE "%'.$name.'%"'));
        $queryStr = $sql->getSqlStringForSqlObject($query);
        return $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function uploadTesterExcel($params)
    {
        $loginContainer    = new Container('credo');
        $dbAdapter         = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql               = new Sql($dbAdapter);
        $status = false;
        $allowedExtensions = array('xls', 'xlsx', 'csv');
        $fileName          = preg_replace('/[^A-Za-z0-9.]/', '-', $_FILES['tester_excel']['name']);
        $fileName          = str_replace(" ", "-", $fileName);
        $ranNumber         = str_pad(rand(0, pow(10, 6)-1), 6, '0', STR_PAD_LEFT);
        $extension         = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileName          = $ranNumber.".".$extension;

        $fileName = preg_replace('/[^A-Za-z0-9.]/', '-', $_FILES['tester_excel']['name']);
        $fileName = str_replace(" ", "-", $fileName);
        $ranNumber = str_pad(rand(0, pow(10, 6)-1), 6, '0', STR_PAD_LEFT);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileName =$ranNumber.".".$extension;
        $response = array();
        if (in_array($extension, $allowedExtensions)) {
            $uploadPath=UPLOAD_PATH . DIRECTORY_SEPARATOR .'tester';
            if (!file_exists($uploadPath) && !is_dir($uploadPath)) {
                mkdir(UPLOAD_PATH.DIRECTORY_SEPARATOR ."tester");            
            }
            
            if (!file_exists($uploadPath . DIRECTORY_SEPARATOR . $fileName)) {
                
                if (move_uploaded_file($_FILES['tester_excel']['tmp_name'], $uploadPath.DIRECTORY_SEPARATOR. $fileName)) {
                    
                    $objPHPExcel = \PHPExcel_IOFactory::load($uploadPath . DIRECTORY_SEPARATOR . $fileName);
                    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                    // Debug::dump($sheetData);die;
                    $count = count($sheetData);
                    $common = new CommonService();
                    $j=0;
                    for ($i = 2; $i <= $count; ++$i) 
                    {
                        $rowset = $this->tableGateway->select(array('email' => $sheetData[$i]['I']))->current();
                        $regrowset = $this->tableGateway->select(array('professional_reg_no' => $sheetData[$i]['A']))->current();
                        $facility = $this->getFacilityByName($sheetData[$i]['K']);
                        if($sheetData[$i]['A'] == '' || $sheetData[$i]['I'] == '' || $sheetData[$i]['K'] == ''){
                            $response['data']['mandatory'][]  = array(
                                'professional_reg_no'       => $sheetData[$i]['A'],
                                'first_name'                => $sheetData[$i]['B'],
                                'middle_name'               => $sheetData[$i]['C'],
                                'last_name'                 => $sheetData[$i]['D'],
                                'region'                    => $sheetData[$i]['E'],
                                'district'                  => $sheetData[$i]['F'],
                                'type_vih_test'             => strtoupper($sheetData[$i]['G']),
                                'phone'                     => $sheetData[$i]['H'],
                                'email'                     => $sheetData[$i]['I'],
                                'current_jod'               => $sheetData[$i]['J'],
                                'facility_id'               => $sheetData[$i]['K'],
                                'time_worked'               => $sheetData[$i]['L'],
                                'username'                  => $sheetData[$i]['M'],
                                'password'                  => $sheetData[$i]['N'],
                                'test_site_in_charge_name'  => strtoupper($sheetData[$i]['O']),
                                'test_site_in_charge_phone' => $sheetData[$i]['P'],
                                'test_site_in_charge_email' => $sheetData[$i]['Q'],
                                'facility_in_charge_name'   => strtoupper($sheetData[$i]['R']),
                                'facility_in_charge_phone'  => $sheetData[$i]['S'],
                                'facility_in_charge_email'  => $sheetData[$i]['T'],
                            );
                        } else if(!$rowset && !$regrowset && isset($facility) && $facility != '' && ($sheetData[$i]['A'] != '' && $sheetData[$i]['I'] != '' && $sheetData[$i]['K'] != '')){
                            $password = '';
                            if(isset($sheetData[$i]['N']) && $sheetData[$i]['N'] != ''){
                                $config = new \Zend\Config\Reader\Ini();
                                $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                                $password = sha1($sheetData[$i]['N'] . $configResult["password"]["salt"]);
                            }

                            $sql = 'SELECT MAX(certification_reg_no) as max FROM provider';
                            $statement = $dbAdapter->query($sql);
                            $result = $statement->execute();
                            foreach ($result as $res) {
                                $max = $res['max'];
                            }
                            $array = explode("R", $max);
                            $array2 = explode("-", $max);

                            if (date('Y') > $array2[0]) {
                                $certification_reg_no = date('Y') . '-R' . substr_replace("0000", 1, -strlen(1));
                            } else {
                                $certification_reg_no = $array2[0] . '-R' . substr_replace("0000", ($array[1] + 1), -strlen(($array[1] + 1)));
                            }
                            $region = $this->getLocationByName($sheetData[$i]['E']);
                            $district = $this->getLocationByName($sheetData[$i]['F']);

                            $data = array(
                                'professional_reg_no'       => $sheetData[$i]['A'],
                                'first_name'                => $sheetData[$i]['B'],
                                'middle_name'               => $sheetData[$i]['C'],
                                'last_name'                 => $sheetData[$i]['D'],
                                'region'                    => (isset($region['location_id']) && $region)?$region['location_id']:'',
                                'district'                  => (isset($district['location_id']) && $district)?$district['location_id']:'',
                                'type_vih_test'             => strtoupper($sheetData[$i]['G']),
                                'phone'                     => $sheetData[$i]['H'],
                                'email'                     => $sheetData[$i]['I'],
                                'prefered_contact_method'   => 'Phone',
                                'current_jod'               => $sheetData[$i]['J'],
                                'facility_id'               => $facility['id'],
                                'time_worked'               => $sheetData[$i]['L'],
                                'username'                  => $sheetData[$i]['M'],
                                'password'                  => $password,
                                'test_site_in_charge_name'  => strtoupper($sheetData[$i]['O']),
                                'test_site_in_charge_phone' => $sheetData[$i]['P'],
                                'test_site_in_charge_email' => $sheetData[$i]['Q'],
                                'facility_in_charge_name'   => strtoupper($sheetData[$i]['R']),
                                'facility_in_charge_phone'  => $sheetData[$i]['S'],
                                'facility_in_charge_email'  => $sheetData[$i]['T'],
                                'certification_reg_no'      => $certification_reg_no,
                                'added_on'                  => $common->getDateTime(),
                                'added_by'                  => $loginContainer->userId,
                                'last_updated_on'           => $common->getDateTime(),
                                'last_updated_by'           => $loginContainer->userId,
                            );
                            $response['data']['imported'][$j] = $data;
                            $response['data']['imported'][$j]['region'] = $sheetData[$i]['E'];
                            $response['data']['imported'][$j]['district'] = $sheetData[$i]['F'];
                            $response['data']['imported'][$j]['facility_id'] = $sheetData[$i]['K'];
                            $this->tableGateway->insert($data);
                            $status = true;
                        } else{
                            $response['data']['duplicate'][] = array(
                                'professional_reg_no'       => $sheetData[$i]['A'],
                                'first_name'                => $sheetData[$i]['B'],
                                'middle_name'               => $sheetData[$i]['C'],
                                'last_name'                 => $sheetData[$i]['D'],
                                'region'                    => $sheetData[$i]['E'],
                                'district'                  => $sheetData[$i]['F'],
                                'type_vih_test'             => strtoupper($sheetData[$i]['G']),
                                'phone'                     => $sheetData[$i]['H'],
                                'email'                     => $sheetData[$i]['I'],
                                'current_jod'               => $sheetData[$i]['J'],
                                'facility_id'               => $sheetData[$i]['K'],
                                'time_worked'               => $sheetData[$i]['L'],
                                'username'                  => $sheetData[$i]['M'],
                                'password'                  => $sheetData[$i]['N'],
                                'test_site_in_charge_name'  => strtoupper($sheetData[$i]['O']),
                                'test_site_in_charge_phone' => $sheetData[$i]['P'],
                                'test_site_in_charge_email' => $sheetData[$i]['Q'],
                                'facility_in_charge_name'   => strtoupper($sheetData[$i]['R']),
                                'facility_in_charge_phone'  => $sheetData[$i]['S'],
                                'facility_in_charge_email'  => $sheetData[$i]['T'],
                            );
                        }
                        $j++;
                    } 
                    unlink($uploadPath . DIRECTORY_SEPARATOR . 'tester' . DIRECTORY_SEPARATOR . $fileName);
                }
            }
        }
        if(count($response['data']) > 0){
            $container = new Container('alert');
            $container->alertMsg = 'Some testers from the excel file were not imported. Please check the highlighted fields below to ensure the Tester Profession number is not duplicated.';
            return $response;
        }else if($status){
            $container = new Container('alert');
            $container->alertMsg = 'Tester details imported successfully';
            return $response;
        }
    }

    public function importManuallyData($params)
    {
        $loginContainer    = new Container('credo');
        $dbAdapter         = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql               = new Sql($dbAdapter);
        $common = new CommonService();
        
        $rowset = $this->tableGateway->select(array('email' => $params['email'], 'professional_reg_no' => $params['regNo']))->current();
        $facility = $this->getFacilityByName($params['facility']);

        if(!$rowset && $facility){
            $password = '';
            if(isset($params['password']) && $params['password'] != ''){
                $config = new \Zend\Config\Reader\Ini();
                $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                $password = sha1($params['password'] . $configResult["password"]["salt"]);
            }

            $sql = 'SELECT MAX(certification_reg_no) as max FROM provider';
            $statement = $dbAdapter->query($sql);
            $result = $statement->execute();
            foreach ($result as $res) {
                $max = $res['max'];
            }
            $array = explode("R", $max);
            $array2 = explode("-", $max);

            if (date('Y') > $array2[0]) {
                $certification_reg_no = date('Y') . '-R' . substr_replace("0000", 1, -strlen(1));
            } else {
                $certification_reg_no = $array2[0] . '-R' . substr_replace("0000", ($array[1] + 1), -strlen(($array[1] + 1)));
            }
            $region = $this->getLocationByName($params['region']);
            $district = $this->getLocationByName($params['district']);

            $data = array(
                'professional_reg_no'       => $params['regNo'],
                'middle_name'               => $params['middle'],
                'first_name'                => $params['first'],
                'last_name'                 => $params['last'],
                'region'                    => (isset($region['location_id']) && $region)?$region['location_id']:'',
                'district'                  => (isset($district['location_id']) && $district)?$district['location_id']:'',
                'type_vih_test'             => strtoupper($params['vih']),
                'phone'                     => $params['phone'],
                'email'                     => $params['email'],
                'prefered_contact_method'   => 'Phone',
                'current_jod'               => $params['job'],
                'facility_id'               => $facility['id'],
                'time_worked'               => $params['time'],
                'username'                  => $params['username'],
                'password'                  => $password,
                'test_site_in_charge_name'  => strtoupper($params['testName']),
                'test_site_in_charge_phone' => $params['testPhone'],
                'test_site_in_charge_email' => $params['testEmail'],
                'facility_in_charge_name'   => strtoupper($params['facilityName']),
                'facility_in_charge_phone'  => $params['facilityPhone'],
                'facility_in_charge_email'  => $params['facilityEmail'],
                'certification_reg_no'      => $certification_reg_no,
                'added_on'                  => $common->getDateTime(),
                'added_by'                  => $loginContainer->userId,
                'last_updated_on'           => $common->getDateTime(),
                'last_updated_by'           => $loginContainer->userId,
            );
            $this->tableGateway->insert($data);
            return $this->tableGateway->lastInsertValue;
        }
        return false;
    }

    public function fetchAllData() {
        $logincontainer = new Container('credo');
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('id', 'certification_reg_no', 'certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'region', 'district', 'type_vih_test', 'phone', 'email', 'prefered_contact_method', 'current_jod', 'time_worked', 'username', 'password', 'test_site_in_charge_name', 'test_site_in_charge_phone', 'test_site_in_charge_email', 'facility_in_charge_name', 'facility_in_charge_phone', 'facility_in_charge_email', 'facility_id','link_send_count'));
        $sqlSelect->join('certification_facilities', ' certification_facilities.id = provider.facility_id ', array('facility_name', 'facility_address'))
                  ->join(array('l_d_r'=>'location_details'), 'l_d_r.location_id = provider.region', array('region_name'=>'location_name'))
                  ->join(array('l_d_d'=>'location_details'), 'l_d_d.location_id = provider.district', array('district_name'=>'location_name'))
                  ->join(array('e'=>'examination'), 'e.provider = provider.id ', array('examid'=>'id'), 'left')
                  ->join(array('c'=>'certification'), 'c.examination = e.id', array('certid'=>'id','final_decision','date_certificate_issued','date_end_validity'),'left');
                  //->group('e.provider');
        $sqlSelect->order('provider.added_on desc')
                  ->order('c.date_certificate_issued desc');
        if(isset($logincontainer->district) && count($logincontainer->district) > 0){
            $sqlSelect->where('provider.district IN('.implode(',',$logincontainer->district).')');
        }else if(isset($logincontainer->region) && count($logincontainer->region) > 0){
            $sqlSelect->where('provider.region IN('.implode(',',$logincontainer->region).')');
        }else if(isset($logincontainer->country) && count($logincontainer->country) > 0){
            $sqlSelect->where('l_d_r.country IN('.implode(',',$logincontainer->country).')');
        }
        $resultSet = $this->tableGateway->selectWith($sqlSelect);
        return $resultSet;
    }


    public function fetachProviderData($parameters)
    {
      
        $logincontainer = new Container('credo');
        $role = $logincontainer->roleCode;
        $roleCode = $logincontainer->roleCode;
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
        $sQuery = $sql->select()->from(array('p' => 'provider'))
        ->columns(array('id', 'certification_reg_no', 'certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'region', 'district', 'type_vih_test', 'phone', 'email', 'prefered_contact_method', 'current_jod', 'time_worked', 'username', 'password', 'test_site_in_charge_name', 'test_site_in_charge_phone', 'test_site_in_charge_email', 'facility_in_charge_name', 'facility_in_charge_phone', 'facility_in_charge_email', 'facility_id','link_send_count'))
        ->join('certification_facilities', ' certification_facilities.id = p.facility_id ', array('facility_name', 'facility_address'))
        ->join(array('l_d_r'=>'location_details'), 'l_d_r.location_id = p.region', array('region_name'=>'location_name'))
        ->join(array('l_d_d'=>'location_details'), 'l_d_d.location_id = p.district', array('district_name'=>'location_name'))
        ->join(array('e'=>'examination'), 'e.provider = p.id ', array('examid'=>'id'), 'left')
        ->join(array('c'=>'certification'), 'c.examination = e.id', array('certid'=>'id','final_decision','date_certificate_issued','date_end_validity'),'left');

        $sQuery->order('c.last_updated_on DESC');
        $sQuery->order('c.date_certificate_issued desc');

        if(isset($logincontainer->district) && count($logincontainer->district) > 0){
            $sQuery->where('provider.district IN('.implode(',',$logincontainer->district).')');
        }else if(isset($logincontainer->region) && count($logincontainer->region) > 0){
            $sQuery->where('provider.region IN('.implode(',',$logincontainer->region).')');
        }else if(isset($logincontainer->country) && count($logincontainer->country) > 0){
            $sQuery->where('l_d_r.country IN('.implode(',',$logincontainer->country).')');
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
        $tQuery = $sql->select()->from(array('p' => 'provider'))
        ->columns(array('id', 'certification_reg_no', 'certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'region', 'district', 'type_vih_test', 'phone', 'email', 'prefered_contact_method', 'current_jod', 'time_worked', 'username', 'password', 'test_site_in_charge_name', 'test_site_in_charge_phone', 'test_site_in_charge_email', 'facility_in_charge_name', 'facility_in_charge_phone', 'facility_in_charge_email', 'facility_id','link_send_count'))
        ->join('certification_facilities', ' certification_facilities.id = p.facility_id ', array('facility_name', 'facility_address'))
        ->join(array('l_d_r'=>'location_details'), 'l_d_r.location_id = p.region', array('region_name'=>'location_name'))
        ->join(array('l_d_d'=>'location_details'), 'l_d_d.location_id = p.district', array('district_name'=>'location_name'))
        ->join(array('e'=>'examination'), 'e.provider = p.id ', array('examid'=>'id'), 'left')
        ->join(array('c'=>'certification'), 'c.examination = e.id', array('certid'=>'id','final_decision','date_certificate_issued','date_end_validity'),'left');

        $tQuery->order('c.last_updated_on DESC');
        $tQuery->order('c.date_certificate_issued desc');
        if(isset($logincontainer->district) && count($logincontainer->district) > 0){
            $tQuery->where('provider.district IN('.implode(',',$logincontainer->district).')');
        }else if(isset($logincontainer->region) && count($logincontainer->region) > 0){
            $tQuery->where('provider.region IN('.implode(',',$logincontainer->region).')');
        }else if(isset($logincontainer->country) && count($logincontainer->country) > 0){
            $tQuery->where('l_d_r.country IN('.implode(',',$logincontainer->country).')');
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
            $providerID=base64_encode($aRow['id']);
            $providerName=$aRow['last_name'] . ' ' . $aRow['first_name']. ' ' . $aRow['middle_name'];
            $link='<a href="javascript:void(0);" style="cursor:pointer;text-decoration:underline;" onclick="getTestHistory('.$providerID.');">'.$providerName.'</a>';

            $certificationTime = '';
            $startDate = '';
            $endDate = '';
            if (isset($aRow['date_certificate_issued']) && $aRow['date_certificate_issued'] != null && trim($aRow['date_certificate_issued']) != '' && $aRow['date_certificate_issued'] != '0000-00-00' && $aRow['date_certificate_issued'] != '1970-01-01') {
                $startDate = date('M Y', strtotime($aRow['date_certificate_issued']));
            }
            if (isset($aRow['date_end_validity']) && $aRow['date_end_validity'] != null && trim($aRow['date_end_validity']) != '' && $aRow['date_end_validity'] != '0000-00-00' && $aRow['date_end_validity'] != '1970-01-01') {
                $endDate = date('M Y', strtotime($aRow['date_end_validity']));
            }
            if (trim($startDate) != '' && trim($endDate) != '') {
                $certificationTime = $startDate . ' - ' . $endDate;
            }
            $EditId = '';
            if ($acl->isAllowed($role, 'Certification\Controller\Provider', 'edit')) {
                $EditId = '<a href="/provider/edit/' . $providerID . '" class="btn btn-outline-primary btn-sm" title="Edit"><span class="glyphicon glyphicon-pencil">Edit</span</a>';
            }
            $deleteconfirm="if(!confirm('Do you really want to remove ' + '.$providerName.' + ' ?')) {
                alert('Canceled!');
                return false;
            }
            ;";
            $DeleteId = '';
            if ($acl->isAllowed($role, 'Certification\Controller\Provider', 'delete')) {
                if (!isset($aRow['examid'])) {
                    $DeleteId = '<a class="btn btn-primary"  onclick="'.$deleteconfirm.'" href="/provider/delete/' . $aRow['id'] . '"> <span class="glyphicon glyphicon-trash">&nbsp;Delete</span></a>';

                }
                
            }
            $PDFId = '';
            if ($acl->isAllowed($role, 'Certification\Controller\Certification', 'pdf')) { 
                if (isset($aRow['final_decision']) && $aRow['final_decision'] != null && trim($aRow['final_decision']) != '') {
                    if (strcasecmp($aRow['final_decision'], 'Certified') == 0) { 
                    /* $val = array(
                        base64_encode('id') => base64_encode($aRow['certid']), 
                        base64_encode('last') => base64_encode($aRow['last_name']), 
                        base64_encode('first') => base64_encode($aRow['first_name']), 
                        base64_encode('middle') => base64_encode($aRow['middle_name']), 
                        base64_encode('professional_reg_no') => base64_encode($aRow['professional_reg_no']), 
                        base64_encode('certification_id') => base64_encode($aRow['certification_id']), 
                        base64_encode('date_issued') => base64_encode($aRow['date_certificate_issued'])
                    );
                    $arrayVal=$this->url('certification', array('action' => 'pdf'), array(
                        'query' => $val
                        )
                    ); */
                    $val = base64_encode('id') .'='.base64_encode($aRow['certid']).'&'.base64_encode('last').'='.base64_encode($aRow['last_name']).'&'.base64_encode('first').'='.base64_encode($aRow['first_name']).'&'.base64_encode('middle').'='.base64_encode($aRow['middle_name']).'&'.base64_encode('professional_reg_no').'='.base64_encode($aRow['professional_reg_no']).'&'.base64_encode('certification_id').'='.base64_encode($aRow['certification_id']).'&'.base64_encode('date_issued').'='.base64_encode($aRow['date_certificate_issued']);
                    $PDFId = '<a class="btn btn-primary" href="/certification/pdf?query='.$val.'" target="_blank"><span class="glyphicon glyphicon-download-alt"></span>&nbsp;PDF</a>';
                    }
                }
            }
            $sendLink = '';
            if ($acl->isAllowed($role, 'Certification\Controller\Provider', 'send-test-link')) { 
                $link_send_count=(isset($aRow['link_send_count']) && $aRow['link_send_count'] > 0) ? $aRow['link_send_count'] : 0;
                $sendLink='<a href="javascript:void(0);" class="btn btn-primary" onclick="sendTestLink('.base64_encode($aRow['id']).','.$aRow['email'].');"><span class="glyphicon glyphicon-envelope"></span>&nbsp;Send Test Link('.$link_send_count.';)</a>';
            }

            $row = array();
            
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $certificationTime;
            if($parameters['addproviders']==''){
            $row[] = $link;
            }else{
            $row[] = $aRow['last_name']. ' '.$aRow['first_name']. ' '.$aRow['middle_name']; 
            }
            $row[] = $aRow['region_name'];
            $row[] = $aRow['district_name'];
            $row[] = $aRow['type_vih_test'];
            $row[] = $aRow['phone'];
            $row[] = $aRow['email'];
            $row[] = $aRow['prefered_contact_method'];
            $row[] = $aRow['current_jod'];
            $row[] = $aRow['time_worked'];
            if($parameters['addproviders']!=''){
            $row[] = $aRow['username'];
            }
            $row[] = $aRow['facility_name'];
            $row[] = $aRow['facility_address'];
            $row[] = $aRow['test_site_in_charge_name'];
            $row[] = $aRow['test_site_in_charge_phone'];
            $row[] = $aRow['test_site_in_charge_email'];
            $row[] = $aRow['facility_in_charge_name'];
            $row[] = $aRow['facility_in_charge_phone'];
            $row[] = $aRow['facility_in_charge_email'];
            if($parameters['addproviders']==''){
                $row[] = $EditId;
                $row[] = $DeleteId;
                $row[] = $PDFId;
                $row[] = $sendLink;
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }
    
}
<?php

namespace Certification\Model;

use Zend\Db\TableGateway\AbstractTableGateway;
use Certification\Model\Training;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class TrainingTable extends AbstractTableGateway {

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
        $sqlSelect->columns(array('training_id', 'Provider_id', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'training_organization_id', 'facilitator', 'training_certificate', 'date_certificate_issued', 'Comments'));
        $sqlSelect->join('provider', 'provider.id = training.Provider_id', array('last_name', 'first_name', 'middle_name', 'professional_reg_no', 'certification_id', 'certification_reg_no'), 'left')
                ->join('training_organization', 'training_organization.training_organization_id = training.training_organization_id ', array('training_organization_name', 'type_organization'), 'left');
        $sqlSelect->order('training_id desc');
        if(isset($sessionLogin->district) && count($sessionLogin->district) > 0){
            $sqlSelect->where('provider.district IN('.implode(',',$sessionLogin->district).')');
        }else if(isset($sessionLogin->region) && count($sessionLogin->region) > 0){
            $sqlSelect->where('provider.region IN('.implode(',',$sessionLogin->region).')');
        }
        $resultSet = $this->tableGateway->selectWith($sqlSelect);
        return $resultSet;
    }

    public function getTraining($training_id) {
        $training_id = (int) $training_id;
        $rowset = $this->tableGateway->select(array('training_id' => $training_id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $training_id");
        }
        return $row;
    }

    public function saveTraining(Training $Training) {
        //$date = $Training->last_training_date;
        //$date_explode = explode("-", $date);
        //die(print_r($date_explode));
        //$newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];
        $newsdate2=NULL;
        if (isset($Training->date_certificate_issued) && trim($Training->date_certificate_issued)!="") {
            $date2 = $Training->date_certificate_issued;
            $date_explode2 = explode("-", $date2);
            $newsdate2 = $date_explode2[2] . '-' . $date_explode2[1] . '-' . $date_explode2[0];
        }
        //\Zend\Debug\Debug::dump($Training); die;
        $training_id = (int) $Training->training_id;
        if ($training_id == 0) {
            foreach($Training->Provider_id as $val){
                $data = array(
                   'Provider_id' => $val,
                   'type_of_competency' => $Training->type_of_competency,
                   //'last_training_date' => $newsdate,
                   'type_of_training' => $Training->type_of_training,
                   'length_of_training' => $Training->length_of_training,
                   'training_organization_id' => $Training->training_organization_id,
                   'facilitator' => strtoupper($Training->facilitator),
                   'training_certificate' => $Training->training_certificate,
                   'date_certificate_issued' => $newsdate2,
                   'Comments' => $Training->Comments,
               );
                $this->tableGateway->insert($data);
            }
        } else {
            if ($this->getTraining($training_id)) {
                $data = array(
                    'Provider_id' => $Training->Provider_id,
                    'type_of_competency' => $Training->type_of_competency,
                    //'last_training_date' => $newsdate,
                    'type_of_training' => $Training->type_of_training,
                    'length_of_training' => $Training->length_of_training,
                    'training_organization_id' => $Training->training_organization_id,
                    'facilitator' => strtoupper($Training->facilitator),
                    'training_certificate' => $Training->training_certificate,
                    'date_certificate_issued' => $newsdate2,
                    'Comments' => $Training->Comments,
                );
                $this->tableGateway->update($data, array('training_id' => $training_id));
            } else {
                throw new \Exception('Training  id does not exist');
            }
        }
    }
    
     public function deleteTraining($training_id)
     {
         $this->tableGateway->delete(array('training_id' => (int) $training_id));
     }

     public function report($type_of_competency,$type_of_training,$training_organization_id,$training_certificate,$typeHiv,$jobTitle,$country,$region,$district,$facility) {
        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;

        $db = $this->tableGateway->getAdapter();
        $sql = 'SELECT provider.certification_reg_no, provider.certification_id, provider.professional_reg_no, provider.first_name, provider.last_name, provider.middle_name,l_d_r.location_name as region_name,l_d_d.location_name as district_name,c.country_name, provider.type_vih_test, provider.phone,provider.email, provider.prefered_contact_method,provider.current_jod, provider.time_worked,provider.test_site_in_charge_name, provider.test_site_in_charge_phone,provider.test_site_in_charge_email, provider.facility_in_charge_name, provider.facility_in_charge_phone, provider.facility_in_charge_email,certification_facilities.facility_name, type_of_competency, last_training_date, type_of_training, length_of_training, facilitator, training_certificate, date_certificate_issued, Comments, training_organization_name, type_organization FROM provider,training, location_details as l_d_r,location_details as l_d_d,country as c,certification_facilities, training_organization where provider.id=training.Provider_id and provider.region=l_d_r.location_id and provider.district=l_d_d.location_id and l_d_r.country=c.country_id and provider.facility_id=certification_facilities.id and training.training_organization_id=training_organization.training_organization_id';
       
        if (!empty($type_of_competency)) {
            $sql = $sql . ' and type_of_competency="' . $type_of_competency . '"';
        }
        
         if (!empty($type_of_training)) {
            $sql = $sql . ' and type_of_training="' . $type_of_training . '"';
        }
        
         if (!empty($training_organization_id)) {
            $sql = $sql . ' and training_organization.training_organization_id=' . $training_organization_id;
        }
        
         if (!empty($training_certificate)) {
            $sql = $sql . ' and training_certificate="' . $training_certificate.'"';
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
            $selectData[$res['id']] = ucwords($res['region_name']);
        }
//        die(print_r($selectData));
        return $selectData;
    }




    public function fetchAllTraining($parameters){

        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $acl = $this->sm->get('AppAcl');

        // echo "test"; die;
        
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
	
        $aColumns = array('certification_reg_no','professional_reg_no','certification_id','last_name','type_of_competency','type_of_training','length_of_training','training_organization_name','type_organization','facilitator');
        $orderColumns = array('certification_reg_no','professional_reg_no','certification_id','last_name','type_of_competency','type_of_training','length_of_training','training_organization_name','type_organization','facilitator');

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
        $sQuery = $sql->select()->from(array('training'=>'training'))
                    ->columns(array('training_id', 'Provider_id', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'training_organization_id', 'facilitator', 'training_certificate', 'date_certificate_issued', 'Comments'))
                    ->join('provider', 'provider.id = training.Provider_id', array('last_name', 'first_name', 'middle_name', 'professional_reg_no', 'certification_id', 'certification_reg_no'), 'left')
                    ->join('training_organization', 'training_organization.training_organization_id = training.training_organization_id ', array('training_organization_name', 'type_organization'), 'left');
        
        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
 
        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }else{
            $sQuery->order('training_id desc');
        }
 
        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance 
        // echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->getSqlStringForSqlObject($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery = $sql->select()->from(array('training'=>'training'))
                ->columns(array('training_id', 'Provider_id', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'training_organization_id', 'facilitator', 'training_certificate', 'date_certificate_issued', 'Comments'))
                ->join('provider', 'provider.id = training.Provider_id', array('last_name', 'first_name', 'middle_name', 'professional_reg_no', 'certification_id', 'certification_reg_no'), 'left')
                ->join('training_organization', 'training_organization.training_organization_id = training.training_organization_id ', array('training_organization_name', 'type_organization'), 'left');
        $tQueryStr = $sql->getSqlStringForSqlObject($tQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iTotal = count($tResult);
        $output = array(
           "sEcho" => intval($parameters['sEcho']),
           "iTotalRecords" => $iTotal,
           "iTotalDisplayRecords" => $iFilteredTotal,
           "aaData" => array()
        );
        
        $loginContainer = new Container('credo');
        $role = $loginContainer->roleCode;
     
        
        foreach ($rResult as $aRow) {
        
                
        $trainingCertificate='';

        if (strcasecmp($aRow['training_certificate'], 'yes') == 0) {
            $trainingCertificate="<span style='color: green;' class='glyphicon glyphicon glyphicon-ok' >Yes</span>";
        } else if (strcasecmp($aRow['training_certificate'], 'no') == 0) {
            $trainingCertificate="<span style='color: red' class='glyphicon glyphicon glyphicon-remove'>No</span>";
        } else {
            $trainingCertificate='';
        }
        if (isset($aRow['date_certificate_issued'])) {
            $date_certificate_issued="<div style='width:100px;height:40px;overflow:auto;'>".date("d-m-Y", strtotime($aRow['date_certificate_issued']))." </div>";
        } else {
            
            $date_certificate_issued="<div style='width:100px;height:40px;overflow:auto;'>".$aRow['date_certificate_issued']." </div>";
        }

      
    $editVal="<a href='/training/edit/" . base64_encode($aRow['training_id']) . "'><span class='glyphicon glyphicon-pencil'>Edit</span></a>";

    $deleteconfirm="if('!confirm('Do you really want to remove this training?')) {training_id
        alert('Canceled!');
        return false;
    }
    ;";

    $DeleteId = '';
    if ($acl->isAllowed($role, 'Certification\Controller\Provider', 'delete')) {
            $DeleteId = '<a class="btn btn-primary"  onclick="'.$deleteconfirm.'" href="/training/delete/' . $aRow['training_id'] . '"> <span class="glyphicon glyphicon-trash">&nbsp;Delete</span></a>';
    }

          $row = array();
          $row[] = $aRow['certification_reg_no'];
          $row[] = $aRow['professional_reg_no'];
          $row[] = $aRow['certification_id'];
          $row[] = $aRow['last_name'].' '.$aRow['first_name'].' '.$aRow['middle_name'];
          $row[] = $aRow['type_of_competency'];
          $row[] = $aRow['type_of_training'];
          $row[] =  date("d-m-Y", strtotime($aRow['last_training_date']));
          $row[] = $aRow['length_of_training'];
          $row[] = $aRow['training_organization_name'];
          $row[] = $aRow['type_organization'];
          $row[] = $aRow['facilitator'];
          $row[] = $trainingCertificate;
          $row[] = $date_certificate_issued;
          
          if ($acl->isAllowed($role, 'Certification\Controller\Training', 'edit')) {
              $row[] = $editVal;
            }
            if ($acl->isAllowed($role, 'Certification\Controller\Training', 'delete')) {
            $row[] = $DeleteId;
            }

         $output['aaData'][] = $row;
        }
        return $output;
    }
    

}

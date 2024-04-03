<?php

namespace Certification\Model;

use Laminas\Db\TableGateway\AbstractTableGateway;
use Certification\Model\Training;
use Laminas\Db\ResultSet\ResultSet;

use Laminas\Db\Sql\Select;
use Laminas\Paginator\Adapter\DbSelect;
use Laminas\Paginator\Paginator;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class TrainingTable extends AbstractTableGateway
{

    protected $tableGateway;
    protected $adapter;
    protected $table = 'training';
    public $sm = null;

    public function __construct(Adapter $adapter, $sm = null)
    {
        $this->adapter = $adapter;
        $this->sm = $sm;

        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Training());
        $this->tableGateway = new TableGateway($this->table, $this->adapter, null, $resultSetPrototype);
    }

    public function fetchAll()
    {
        $sessionLogin = new Container('credo');
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array('training_id', 'Provider_id', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'training_organization_id', 'facilitator', 'training_certificate', 'date_certificate_issued', 'Comments'));
        $sqlSelect->join('provider', 'provider.id = training.Provider_id', array('last_name', 'first_name', 'middle_name', 'professional_reg_no', 'certification_id', 'certification_reg_no'), 'left')
            ->join('training_organization', 'training_organization.training_organization_id = training.training_organization_id ', array('training_organization_name', 'type_organization'), 'left');
        $sqlSelect->order('training_id desc');
        if (property_exists($sessionLogin, 'district') && $sessionLogin->district !== null && count($sessionLogin->district) > 0) {
            $sqlSelect->where('provider.district IN(' . implode(',', $sessionLogin->district) . ')');
        } elseif (property_exists($sessionLogin, 'region') && $sessionLogin->region !== null && count($sessionLogin->region) > 0) {
            $sqlSelect->where('provider.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        return $this->tableGateway->selectWith($sqlSelect);
    }

    public function getTraining($training_id)
    {
        $training_id = (int) $training_id;
        $rowset = $this->tableGateway->select(array('training_id' => $training_id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $training_id");
        }
        return $row;
    }

    public function saveTraining(Training $Training)
    {
        $newsdate = NULL;
        $newsdate2 = NULL;
        if ($Training->last_training_date !== null && trim($Training->last_training_date) != "") {
            $date = $Training->last_training_date;
            $date_explode = explode("-", $date);
            $newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];
        }
        if ($Training->date_certificate_issued !== null && trim($Training->date_certificate_issued) != "") {
            $date2 = $Training->date_certificate_issued;
            $date_explode2 = explode("-", $date2);
            $newsdate2 = $date_explode2[2] . '-' . $date_explode2[1] . '-' . $date_explode2[0];
        }
        //\Zend\Debug\Debug::dump($Training); die;
        $training_id = (int) $Training->training_id;
        if ($training_id == 0) {
            foreach ($Training->Provider_id as $val) {
                $data = array(
                    'Provider_id' => $val,
                    'type_of_competency' => $Training->type_of_competency,
                    'last_training_date' => $newsdate,
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
        } elseif ($this->getTraining($training_id)) {
            $data = array(
                'Provider_id' => $Training->Provider_id,
                'type_of_competency' => $Training->type_of_competency,
                'last_training_date' => $newsdate,
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

    public function deleteTraining($training_id)
    {
        $this->tableGateway->delete(array('training_id' => (int) $training_id));
    }

    public function report($type_of_competency, $type_of_training, $training_organization_id, $training_certificate, $typeHiv, $jobTitle, $country, $region, $district, $facility)
    {
        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;

        $db = $this->adapter;
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
            $sql = $sql . ' and training_certificate="' . $training_certificate . '"';
        }

        if (!empty($typeHiv)) {
            $sql = $sql . ' and provider.type_vih_test="' . $typeHiv . '"';
        }
        if (!empty($jobTitle)) {
            $sql = $sql . ' and provider.current_jod="' . $jobTitle . '"';
        }

        if (!empty($country)) {
            $sql = $sql . ' and c.country_id=' . $country;
        } elseif (property_exists($logincontainer, 'country') && $logincontainer->country !== null && count($logincontainer->country) > 0 && $roleCode != 'AD') {
            $sql = $sql . ' AND c.country_id IN(' . implode(',', $logincontainer->country) . ')';
        }

        if (!empty($region)) {
            $sql = $sql . ' and l_d_r.location_id=' . $region;
        } elseif (property_exists($logincontainer, 'region') && $logincontainer->region !== null && count($logincontainer->region) > 0 && $roleCode != 'AD') {
            $sql = $sql . ' AND l_d_r.location_id IN(' . implode(',', $logincontainer->region) . ')';
        }

        if (!empty($district)) {
            $sql = $sql . ' and l_d_d.location_id=' . $district;
        } elseif (property_exists($logincontainer, 'district') && $logincontainer->district !== null && count($logincontainer->district) > 0 && $roleCode != 'AD') {
            $sql = $sql . ' AND l_d_d.location_id IN(' . implode(',', $logincontainer->district) . ')';
        }

        if (!empty($facility)) {
            $sql = $sql . ' and certification_facilities.id=' . $facility;
        }
        //        die($sql);

        $statement = $db->query($sql);
        return $statement->execute();
    }

    public function getAllActiveCountries()
    {
        $dbAdapter = $this->adapter;
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
        $dbAdapter = $this->adapter;
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




    public function fetchAllTraining($parameters)
    {

        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleCode;
        $acl = $this->sm->get('AppAcl');

        // echo "test"; die;

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */

        $aColumns = array('certification_reg_no', 'professional_reg_no', 'certification_id', 'last_name', 'type_of_competency', 'type_of_training', 'length_of_training', 'training_organization_name', 'type_organization', 'facilitator');
        $orderColumns = array('certification_reg_no', 'professional_reg_no', 'certification_id', 'last_name', 'type_of_competency', 'type_of_training', 'length_of_training', 'training_organization_name', 'type_organization', 'facilitator');

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
                    $sOrder .= $orderColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        $sQuery = $sql->select()->from(array('training' => 'training'))
            ->columns(array('training_id', 'Provider_id', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'training_organization_id', 'facilitator', 'training_certificate', 'date_certificate_issued', 'Comments'))
            ->join('provider', 'provider.id = training.Provider_id', array('last_name', 'first_name', 'middle_name', 'professional_reg_no', 'certification_id', 'certification_reg_no'), 'left')
            ->join('training_organization', 'training_organization.training_organization_id = training.training_organization_id ', array('training_organization_name', 'type_organization'), 'left');

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        } else {
            $sQuery->order('training_id desc');
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance 
        // echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->buildSqlString($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery = $sql->select()->from(array('training' => 'training'))
            ->columns(array('training_id', 'Provider_id', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'training_organization_id', 'facilitator', 'training_certificate', 'date_certificate_issued', 'Comments'))
            ->join('provider', 'provider.id = training.Provider_id', array('last_name', 'first_name', 'middle_name', 'professional_reg_no', 'certification_id', 'certification_reg_no'), 'left')
            ->join('training_organization', 'training_organization.training_organization_id = training.training_organization_id ', array('training_organization_name', 'type_organization'), 'left');
        $tQueryStr = $sql->buildSqlString($tQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iTotal = count($tResult);
        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        $loginContainer = new Container('credo');
        $role = $loginContainer->roleCode;


        foreach ($rResult as $aRow) {


            $trainingCertificate = '';

            if (strcasecmp($aRow['training_certificate'], 'yes') == 0) {
                $trainingCertificate = "<span style='color: green;' class='glyphicon glyphicon glyphicon-ok' >Yes</span>";
            } elseif (strcasecmp($aRow['training_certificate'], 'no') == 0) {
                $trainingCertificate = "<span style='color: red' class='glyphicon glyphicon glyphicon-remove'>No</span>";
            } else {
                $trainingCertificate = '';
            }
            if (isset($aRow['date_certificate_issued'])) {
                $date_certificate_issued = "<div style='width:100px;height:40px;overflow:auto;'>" . date("d-m-Y", strtotime($aRow['date_certificate_issued'])) . " </div>";
            } else {

                $date_certificate_issued = "<div style='width:100px;height:40px;overflow:auto;'>" . $aRow['date_certificate_issued'] . " </div>";
            }


            $editVal = "<a href='/training/edit/" . base64_encode($aRow['training_id']) . "'><span class='glyphicon glyphicon-pencil'>Edit</span></a>";

            $deleteconfirm = "if('!confirm('Do you really want to remove this training?')) {training_id
        alert('Canceled!');
        return false;
    }
    ;";

            $DeleteId = '';
            if ($acl->isAllowed($role, 'Certification\Controller\ProviderController', 'delete')) {
                $DeleteId = '<a class="btn btn-primary"  onclick="' . $deleteconfirm . '" href="/training/delete/' . $aRow['training_id'] . '"> <span class="glyphicon glyphicon-trash">&nbsp;Delete</span></a>';
            }
            $row = array();
            $row[] = $aRow['certification_reg_no'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['certification_id'];
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['type_of_competency'];
            $row[] = $aRow['type_of_training'];
            $row[] =  isset($aRow['last_training_date']) ? date("d-m-Y", strtotime($aRow['last_training_date'])) : '';
            $row[] = $aRow['length_of_training'];
            $row[] = $aRow['training_organization_name'];
            $row[] = $aRow['type_organization'];
            $row[] = $aRow['facilitator'];
            $row[] = $trainingCertificate;
            $row[] = $date_certificate_issued;

            if ($acl->isAllowed($role, 'Certification\Controller\TrainingController', 'edit')) {
                $row[] = $editVal;
            }
            if ($acl->isAllowed($role, 'Certification\Controller\TrainingController', 'delete')) {
                $row[] = $DeleteId;
            }

            $output['aaData'][] = $row;
        }
        return $output;
    }



    public function reportData($parameters)
    {
        // \Zend\Debug\Debug::dump($parameters);

        $logincontainer = new Container('credo');
        $roleCode = $logincontainer->roleCode;
        $acl = $this->sm->get('AppAcl');

        // echo "test"; die;

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */

        $aColumns =  array('first_name', 'last_name', 'professional_reg_no', 'l_d_r.location_name', 'l_d_d.location_name', 'facility_name', 'type_of_competency', 'training_organization_name', 'type_of_training', 'training_certificate', 'type_vih_test', 'current_jod');

        $orderColumns = array('first_name', 'last_name', 'professional_reg_no', 'region_name', 'district_name', 'facility_name', 'type_of_competency', 'training_organization_name', 'type_of_training', 'training_certificate', 'type_vih_test', 'current_jod');

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
                    $sOrder .= $orderColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        $sQuery =  $sql->select()->from(array('p' => 'provider'))
            ->columns(array('id', 'certification_reg_no', 'certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'region', 'district', 'type_vih_test', 'phone', 'email', 'prefered_contact_method', 'current_jod', 'time_worked', 'username', 'password', 'test_site_in_charge_name', 'test_site_in_charge_phone', 'test_site_in_charge_email', 'facility_in_charge_name', 'facility_in_charge_phone', 'facility_in_charge_email', 'facility_id', 'link_send_count'))
            ->join('training', ' training.Provider_id = p.id ', array('Comments', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'facilitator', 'training_certificate', 'date_certificate_issued'))
            ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id = p.region', array('region_name' => 'location_name'))
            ->join(array('l_d_d' => 'location_details'), 'l_d_d.location_id = p.district', array('district_name' => 'location_name'))
            ->join(array('c' => 'country'), 'l_d_r.country = c.country_id ', array('country_name'), 'left')
            ->join('certification_facilities', ' certification_facilities.id = p.facility_id ', array('facility_name', 'facility_address'))
            ->join('training_organization', 'training.training_organization_id = training_organization.training_organization_id ', array('training_organization_name', 'type_organization'));

        if (!empty($parameters['type_of_competency'])) {
            $sQuery->where(array('training.type_of_competency' => $parameters['type_of_competency']));
        }
        if (!empty($parameters['type_of_training'])) {
            $sQuery->where(array('training.type_of_training' => $parameters['type_of_training']));
        }
        if (!empty($parameters['training_organization_id'])) {
            $sQuery->where(array('training_organization.training_organization_id' => $parameters['training_organization_id']));
        }
        if (!empty($parameters['training_certificate'])) {
            $sQuery->where(array('training.training_certificate' => $parameters['training_certificate']));
        }
        if (!empty($parameters['typeHiv'])) {
            $sQuery->where(array('p.type_vih_test' => $parameters['typeHiv']));
        }
        if (!empty($parameters['jobTitle'])) {
            $sQuery->where(array('p.current_jod' => $parameters['jobTitle']));
        }
        if (!empty($parameters['country'])) {
            $sQuery->where(array('c.country_id' => $parameters['country']));
        } elseif (property_exists($logincontainer, 'country') && $logincontainer->country !== null && count($logincontainer->country) > 0 && $roleCode != 'AD') {
            $sQuery->where('(c.country_id IN(' . implode(',', $logincontainer->country) . '))');
        }
        if (!empty($parameters['region'])) {
            $sQuery->where(array('l_d_r.location_id' => $parameters['region']));
        } elseif (property_exists($logincontainer, 'region') && $logincontainer->region !== null && count($logincontainer->region) > 0 && $roleCode != 'AD') {
            $sQuery->where('(l_d_r.location_id IN(' . implode(',', $logincontainer->region) . '))');
        }
        if (!empty($parameters['district'])) {
            $sQuery->where(array('l_d_d.location_id' => $parameters['district']));
        } elseif (property_exists($logincontainer, 'district') && $logincontainer->district !== null && count($logincontainer->district) > 0 && $roleCode != 'AD') {
            $sQuery->where('(l_d_d.location_id IN(' . implode(',', $logincontainer->district) . '))');
        }
        if (!empty($parameters['facility'])) {
            $sQuery->where(array('certification_facilities.id' => $parameters['facility']));
        }

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        } else {
            $sQuery->order('training_id desc');
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance 
        // echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->buildSqlString($sQuery);
        // echo $fQuery; die;
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery = $sql->select()->from(array('p' => 'provider'))
            ->columns(array('id', 'certification_reg_no', 'certification_id', 'professional_reg_no', 'last_name', 'first_name', 'middle_name', 'region', 'district', 'type_vih_test', 'phone', 'email', 'prefered_contact_method', 'current_jod', 'time_worked', 'username', 'password', 'test_site_in_charge_name', 'test_site_in_charge_phone', 'test_site_in_charge_email', 'facility_in_charge_name', 'facility_in_charge_phone', 'facility_in_charge_email', 'facility_id', 'link_send_count'))
            ->join('training', ' training.Provider_id = p.id ', array('Comments', 'type_of_competency', 'last_training_date', 'type_of_training', 'length_of_training', 'facilitator', 'training_certificate', 'date_certificate_issued'))
            ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id = p.region', array('region_name' => 'location_name'))
            ->join(array('l_d_d' => 'location_details'), 'l_d_d.location_id = p.district', array('district_name' => 'location_name'))
            ->join(array('c' => 'country'), 'l_d_r.country = c.country_id ', array('country_name'), 'left')
            ->join('certification_facilities', ' certification_facilities.id = p.facility_id ', array('facility_name', 'facility_address'))
            ->join('training_organization', 'training.training_organization_id = training_organization.training_organization_id ', array('training_organization_name', 'type_organization'));


        if (!empty($parameters['type_of_competency'])) {
            $tQuery->where(array('training.type_of_competency' => $parameters['type_of_competency']));
        }
        if (!empty($parameters['type_of_training'])) {
            $tQuery->where(array('training.type_of_training' => $parameters['type_of_training']));
        }
        if (!empty($parameters['training_organization_id'])) {
            $tQuery->where(array('training_organization.training_organization_id' => $parameters['training_organization_id']));
        }
        if (!empty($parameters['training_certificate'])) {
            $tQuery->where(array('training.training_certificate' => $parameters['training_certificate']));
        }
        if (!empty($parameters['typeHiv'])) {
            $tQuery->where(array('p.type_vih_test' => $parameters['typeHiv']));
        }
        if (!empty($parameters['jobTitle'])) {
            $tQuery->where(array('p.current_jod' => $parameters['jobTitle']));
        }
        if (!empty($parameters['country'])) {
            $tQuery->where(array('c.country_id' => $parameters['country']));
        } elseif (property_exists($logincontainer, 'country') && $logincontainer->country !== null && count($logincontainer->country) > 0 && $roleCode != 'AD') {
            $tQuery->where('(c.country_id IN(' . implode(',', $logincontainer->country) . '))');
        }
        if (!empty($parameters['region'])) {
            $tQuery->where(array('l_d_r.location_id' => $parameters['region']));
        } elseif (property_exists($logincontainer, 'region') && $logincontainer->region !== null && count($logincontainer->region) > 0 && $roleCode != 'AD') {
            $tQuery->where('(l_d_r.location_id IN(' . implode(',', $logincontainer->region) . '))');
        }
        if (!empty($parameters['district'])) {
            $tQuery->where(array('l_d_d.location_id' => $parameters['district']));
        } elseif (property_exists($logincontainer, 'district') && $logincontainer->district !== null && count($logincontainer->district) > 0 && $roleCode != 'AD') {
            $tQuery->where('(l_d_d.location_id IN(' . implode(',', $logincontainer->district) . '))');
        }
        if (!empty($parameters['facility'])) {
            $tQuery->where(array('certification_facilities.id' => $parameters['facility']));
        }


        $tQueryStr = $sql->buildSqlString($tQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iTotal = count($tResult);
        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => $iFilteredTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        $loginContainer = new Container('credo');
        $role = $loginContainer->roleCode;


        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $aRow['last_name'] . ' ' . $aRow['first_name'] . ' ' . $aRow['middle_name'];
            $row[] = $aRow['professional_reg_no'];
            $row[] = $aRow['region_name'];
            $row[] = $aRow['district_name'];
            $row[] = $aRow['facility_name'];
            $row[] = $aRow['type_of_competency'];
            $row[] = $aRow['training_organization_name'];
            $row[] = $aRow['type_of_training'];
            $row[] = $aRow['training_certificate'];
            $row[] = $aRow['type_vih_test'];
            $row[] = $aRow['current_jod'];
            $output['aaData'][] = $row;
        }
        return $output;
    }
}

<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Sql;
use Application\Service\CommonService;
use Zend\Debug\Debug;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author amit
 */
class GlobalTable extends AbstractTableGateway
{

    protected $table = 'global_config';
    protected $certificationTable = null;

    public function __construct(Adapter $adapter, $certificationTable = '')
    {
        $this->adapter = $adapter;
        $this->certificationTable = $certificationTable;
    }

    public function fetchAllConfig($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('display_name', 'global_value');
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
        $sQuery = $sql->select()->from('global_config');
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
        //error_log($sQueryForm);
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
            $row[] = ucwords($aRow['global_value']);
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function getGlobalConfig()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('global_config');
        $sQueryStr = $sql->buildSqlString($sQuery);
        $configValues = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $size = count($configValues);
        $arr = array();
        // now we create an associative array so that we can easily create view variables
        for ($i = 0; $i < $size; $i++) {
            $arr[$configValues[$i]['global_name']] = $configValues[$i]['global_value'];
        }
        // using assign to automatically create view variables
        // the column names will now become view variables
        return $arr;
    }

    public function updateConfigDetails($params)
    {
        
        $result = 0;
        if (isset($_POST['removedLogoImage']) && trim($_POST['removedLogoImage']) != "" && file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo" . DIRECTORY_SEPARATOR . $_POST['removedLogoImage'])) {
            unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo" . DIRECTORY_SEPARATOR . $_POST['removedLogoImage']);
            $this->update(array('global_value' => ''), array('global_name' => 'logo'));
        }

        if (isset($_POST['removedSignatureImage']) && trim($_POST['removedSignatureImage']) != "" && file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "digital_signature" . DIRECTORY_SEPARATOR . $_POST['removedSignatureImage'])) {
            unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo" . DIRECTORY_SEPARATOR . $_POST['removedLogoImage']);
            $this->update(array('global_value' => ''), array('global_name' => 'logo'));
        }

        if (isset($_FILES['logo']['name']) && $_FILES['logo']['name'] != "") {
            if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo") && !is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo")) {
                mkdir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo");
            }
            $extension = strtolower(pathinfo(UPLOAD_PATH . DIRECTORY_SEPARATOR . $_FILES['logo']['name'], PATHINFO_EXTENSION));
            $string = \Application\Service\CommonService::generateRandomString(6) . ".";
            $imageName = "logo" . $string . $extension;
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo" . DIRECTORY_SEPARATOR . $imageName)) {
                $this->update(array('global_value' => $imageName), array('global_name' => 'logo'));
            }
        }

        if (isset($_FILES['digitalSignature']['name']) && $_FILES['digitalSignature']['name'] != "") {
            if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "digital_signature") && !is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "digital_signature")) {
                mkdir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "digital_signature");
            }
            $extension = strtolower(pathinfo(UPLOAD_PATH . DIRECTORY_SEPARATOR . $_FILES['digitalSignature']['name'], PATHINFO_EXTENSION));
            $string = \Application\Service\CommonService::generateRandomString(6) . ".";
            $imageName = "ds_" . $string . $extension;
            if (move_uploaded_file($_FILES["digitalSignature"]["tmp_name"], UPLOAD_PATH . DIRECTORY_SEPARATOR . "digital_signature" . DIRECTORY_SEPARATOR . $imageName)) {
                $this->update(array('global_value' => $imageName), array('global_name' => 'registrar-digital-signature'));
            }
        }

        foreach ($params as $fieldName => $fieldValue) {
            if ($fieldName != 'removedLogoImage' && $fieldName != 'removedSignatureImage') {
                $result = $this->update(array('global_value' => $fieldValue), array('global_name' => $fieldName));
            }
        }
        return $result;
    }

    public function getGlobalValue($globalName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('global_config')->where(array('global_name' => $globalName));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $configValues = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $configValues[0]['global_value'];
    }

    public function fetchHeaderText()
    {
        return array('textHeader' => $this->certificationTable->SelectTexteHeader(), 'HeaderTextFont' => $this->certificationTable->SelectHeaderTextFontSize());
    }
    
    public function getConfigByGlobalName($globalName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('global_config')->where(array('global_name' => $globalName));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $configResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $configResult[0];
    }
    

    public function updateDashboardContent($params)
    {
        $dashboardContent = $params['dashboardContent'];
        //$dashboardContent = str_replace("&nbsp;"," ",(string) $params['dashboardContent']);
        if(isset($params['configId']) && $params['configId'] != ''){
            return $this->update(array('global_value' => $dashboardContent), "config_id=".base64_decode($params['configId']));
        }
        return 0;
    }
}

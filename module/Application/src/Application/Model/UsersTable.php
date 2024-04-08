<?php

namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Application\Service\CommonService;
use Application\Model\UserRoleMapTable;
use Application\Model\UserTokenMapTable;

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
class UsersTable extends AbstractTableGateway
{

    protected $table = 'users';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }


    public function login($params)
    {
        $container = new Container('alert');
        $logincontainer = new Container('credo');
        $username = $params['username'];
        $config = new \Laminas\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $password = sha1($params['password'] . $configResult["password"]["salt"]);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('u' => 'users'))
            ->join(array('urm' => 'user_role_map'), 'urm.user_id=u.id', array('role_id'))
            ->join(array('r' => 'roles'), 'r.role_id=urm.role_id', array('role_name', 'role_code', 'access_level'))
            ->where(array('login' => $username, 'password' => $password, 'u.status' => 'active'));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if ($sResult) {
            $token = array();
            $userTokenQuery = $sql->select()->from(array('u_t_map' => 'user_token_map'))
                ->columns(array('token'))
                ->where(array('user_id' => $sResult->id))
                ->order("token ASC");
            $userTokenQueryStr = $sql->buildSqlString($userTokenQuery);
            $userTokenResult = $dbAdapter->query($userTokenQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            foreach ($userTokenResult as $userToken) {
                $token[] = $userToken['token'];
            }
            $country = array();
            $region = array();
            $district = array();
            if (isset($sResult->access_level) && $sResult->access_level != null && trim($sResult->access_level) != '' && (int)$sResult->access_level == 4) {
                $userDistrictQuery = $sql->select()->from(array('u_d_map' => 'user_district_map'))
                    ->join(array('l_d' => 'location_details'), 'l_d.location_id=u_d_map.location_id', array('location_id', 'parent_location', 'country'))
                    ->where(array('u_d_map.user_id' => $sResult->id))
                    ->order("location_name ASC");
                $userDistrictQueryStr = $sql->buildSqlString($userDistrictQuery);
                $userDistrictResult = $dbAdapter->query($userDistrictQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if (isset($userDistrictResult) && count($userDistrictResult) > 0) {
                    foreach ($userDistrictResult as $userDistrict) {
                        $district[] = $userDistrict['location_id'];
                        if (!in_array($userDistrict['parent_location'], $region)) {
                            $region[] = $userDistrict['parent_location'];
                        }
                        if (!in_array($userDistrict['country'], $country)) {
                            $country[] = $userDistrict['country'];
                        }
                    }
                }
            } elseif (isset($sResult->access_level) && $sResult->access_level != null && trim($sResult->access_level) != '' && (int)$sResult->access_level == 3) {
                $userProvinceQuery = $sql->select()->from(array('u_p_map' => 'user_province_map'))
                    ->join(array('l_d' => 'location_details'), 'l_d.location_id=u_p_map.location_id', array('location_id', 'country'))
                    ->where(array('u_p_map.user_id' => $sResult->id))
                    ->order("location_name ASC");
                $userProvinceQueryStr = $sql->buildSqlString($userProvinceQuery);
                $userProvinceResult = $dbAdapter->query($userProvinceQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if (isset($userProvinceResult) && count($userProvinceResult) > 0) {
                    foreach ($userProvinceResult as $userProvince) {
                        $region[] = $userProvince['location_id'];
                        if (!in_array($userProvince['country'], $country)) {
                            $country[] = $userProvince['country'];
                        }
                    }
                }
            } elseif (isset($sResult->access_level) && $sResult->access_level != null && trim($sResult->access_level) != '' && (int)$sResult->access_level == 2) {
                $userCountryQuery = $sql->select()->from(array('u_c_map' => 'user_country_map'))
                    ->where(array('u_c_map.user_id' => $sResult->id));
                $userCountryQueryStr = $sql->buildSqlString($userCountryQuery);
                $userCountryResult = $dbAdapter->query($userCountryQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if (isset($userCountryResult) && count($userCountryResult) > 0) {
                    foreach ($userCountryResult as $userCountry) {
                        $country[] = $userCountry['country_id'];
                    }
                }
            }

            $logincontainer->userId = $sResult->id;
            $logincontainer->login = $sResult->login;
            $logincontainer->roleId = $sResult->role_id;
            $logincontainer->roleName = $sResult->role_name;
            $logincontainer->roleCode = $sResult->role_code;
            $logincontainer->token = $token;
            $logincontainer->district = $district;
            $logincontainer->region = $region;
            $logincontainer->country = $country;

            return 'dashboard';
        } else {
            $container->alertMsg = 'Please check your login credentials';
            return 'login';
        }
    }

    public function addUserDetails($params)
    {
        $dbAdapter = $this->adapter;
        $userRoleMap = new UserRoleMapTable($dbAdapter);
        $userTokenMap = new UserTokenMapTable($dbAdapter);
        $userDistrictMap = new UserDistrictMapTable($dbAdapter);
        $userProvinceMap = new UserProvinceMapTable($dbAdapter);
        $userCountryMap = new UserCountryMapTable($dbAdapter);
        $config = new \Laminas\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $password = sha1($params['password'] . $configResult["password"]["salt"]);
        $lastInsertId = 0;
        if (isset($params['userName']) && trim($params['userName']) != "") {
            $data = array(
                'first_name' => $params['firstName'],
                'last_name' => $params['lastName'],
                'login' => $params['userName'],
                'password' => $password,
                'email' => $params['email'],
                'status' => $params['status'],
                'created_on' => \Application\Service\CommonService::getDateTime()
            );
            $this->insert($data);
            $lastInsertId = $this->lastInsertValue;
            if ($lastInsertId > 0) {
                $userRoleMap->insert(array('user_id' => $lastInsertId, 'role_id' => $params['roleId']));
                //Add User-Token
                if (isset($params['token']) && trim($params['token']) != '') {
                    $splitToken = explode(",", $params['token']);
                    $counter = count($splitToken);
                    for ($t = 0; $t < $counter; $t++) {
                        $userTokenMap->insert(array('user_id' => $lastInsertId, 'token' => trim($splitToken[$t])));
                    }
                }
                //Add User-District, User-Province, User-Country
                if (isset($params['district']) && !empty($params['district'])) {
                    $counter = count($params['district']);
                    for ($i = 0; $i < $counter; $i++) {
                        $userDistrictMap->insert(array('user_id' => $lastInsertId, 'location_id' => $params['district'][$i]));
                    }
                } elseif (isset($params['province']) && !empty($params['province'])) {
                    $counter = count($params['province']);
                    for ($i = 0; $i < $counter; $i++) {
                        $userProvinceMap->insert(array('user_id' => $lastInsertId, 'location_id' => $params['province'][$i]));
                    }
                } elseif (isset($params['country']) && !empty($params['country'])) {
                    $counter = count($params['country']);
                    for ($i = 0; $i < $counter; $i++) {
                        $userCountryMap->insert(array('user_id' => $lastInsertId, 'country_id' => $params['country'][$i]));
                    }
                }
            }
            return $lastInsertId;
        }
    }

    public function updateUserDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $userRoleMap = new UserRoleMapTable($dbAdapter);
        $userTokenMap = new UserTokenMapTable($dbAdapter);
        $userDistrictMap = new UserDistrictMapTable($dbAdapter);
        $userProvinceMap = new UserProvinceMapTable($dbAdapter);
        $userCountryMap = new UserCountryMapTable($dbAdapter);
        $userId = base64_decode($params['userId']);
        if (isset($params['password']) && $params['password'] != '') {
            $config = new \Laminas\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $password = sha1($params['password'] . $configResult["password"]["salt"]);
            $data = array('password' => $password);
            $this->update($data, array('id' => $userId));
        }
        if (isset($params['userName']) && trim($params['userName']) != "") {
            $data = array(
                'first_name' => $params['firstName'],
                'last_name' => $params['lastName'],
                'login' => $params['userName'],
                'email' => $params['email'],
                'status' => $params['status']
            );
            $this->update($data, array('id' => $userId));
            if ($userId > 0) {
                $userRoleMap->update(array('role_id' => $params['roleId']), array('user_id' => $userId));
                //Update User-Token
                $userTokenMap->delete(array('user_id' => $userId));
                if (isset($params['token']) && trim($params['token']) != '') {
                    $splitToken = explode(",", $params['token']);
                    $counter = count($splitToken);
                    for ($t = 0; $t < $counter; $t++) {
                        $userTokenMap->insert(array('user_id' => $userId, 'token' => trim($splitToken[$t])));
                    }
                }
                //Remove User-District, User-Province, User-Country
                $userDistrictMap->delete(array('user_id' => $userId));
                $userProvinceMap->delete(array('user_id' => $userId));
                $userCountryMap->delete(array('user_id' => $userId));
                //Add User-District, User-Province, User-Country
                if (isset($params['districtReferecne']) && trim($params['districtReferecne']) != '') {
                    $districtArray = explode(',', $params['districtReferecne']);
                    $counter = count($districtArray);
                    for ($i = 0; $i < $counter; $i++) {
                        $userDistrictMap->insert(array('user_id' => $userId, 'location_id' => $districtArray[$i]));
                    }
                } elseif (isset($params['provinceReferecne']) && !empty($params['provinceReferecne'])) {
                    $provinceArray = explode(',', $params['provinceReferecne']);
                    $counter = count($provinceArray);
                    for ($i = 0; $i < $counter; $i++) {
                        $userProvinceMap->insert(array('user_id' => $userId, 'location_id' => $provinceArray[$i]));
                    }
                } elseif (isset($params['country']) && !empty($params['country'])) {
                    $counter = count($params['country']);
                    for ($i = 0; $i < $counter; $i++) {
                        $userCountryMap->insert(array('user_id' => $userId, 'country_id' => $params['country'][$i]));
                    }
                }
            }
            return $userId;
        }
    }

    public function fetchAllUsers($parameters, $acl)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */

        $aColumns = array('first_name', 'last_name', 'email', 'role_name', 'access_level', 'u.status');
        $orderColumns = array('first_name', 'email', 'role_name', 'access_level', 'u.status');

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
        $sQuery = $sql->select()->from(array('u' => 'users'))
            ->join(array('urm' => 'user_role_map'), "urm.user_id=u.id", array())
            ->join(array('r' => 'roles'), "r.role_id=urm.role_id", array('access_level', 'role_name'));

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
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->buildSqlString($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $tQuery =  $sql->select()->from(array('u' => 'users'))
            ->join(array('urm' => 'user_role_map'), "urm.user_id=u.id", array())
            ->join(array('r' => 'roles'), "r.role_id=urm.role_id", array('access_level', 'role_name'));
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
        $update = (bool) $acl->isAllowed($role, 'Application\Controller\UsersController', 'edit');

        foreach ($rResult as $aRow) {
            $accessLevel = '';
            if ($aRow['access_level'] == 1) {
                $accessLevel = 'Global';
            } elseif ($aRow['access_level'] == 2) {
                $accessLevel = 'Country';
                $userCountryQuery = $sql->select()->from(array('u_c_map' => 'user_country_map'))
                    ->join(array('c' => 'country'), "c.country_id=u_c_map.country_id", array('countryMaps' => new Expression("GROUP_CONCAT(country_name)")))
                    ->where(array('u_c_map.user_id' => $aRow['id']));
                $userCountryQueryStr = $sql->buildSqlString($userCountryQuery);
                $userCountryResult = $dbAdapter->query($userCountryQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                if (isset($userCountryResult) && trim($userCountryResult->countryMaps) != '') {
                    $accessLevel .= ' (' . $userCountryResult->countryMaps . ')';
                }
            } elseif ($aRow['access_level'] == 3) {
                $accessLevel = 'Province/State';
                $userProvinceQuery = $sql->select()->from(array('u_p_map' => 'user_province_map'))
                    ->join(array('l_d' => 'location_details'), "l_d.location_id=u_p_map.location_id", array('provinceMaps' => new Expression("GROUP_CONCAT(location_name)")))
                    ->where(array('u_p_map.user_id' => $aRow['id']));
                $userProvinceQueryStr = $sql->buildSqlString($userProvinceQuery);
                $userProvinceResult = $dbAdapter->query($userProvinceQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                if (isset($userProvinceResult) && trim($userProvinceResult->provinceMaps) != '') {
                    $accessLevel .= ' (' . $userProvinceResult->provinceMaps . ')';
                }
            } elseif ($aRow['access_level'] == 4) {
                $accessLevel = 'District/City';
                $userDistrictQuery = $sql->select()->from(array('u_d_map' => 'user_district_map'))
                    ->join(array('l_d' => 'location_details'), "l_d.location_id=u_d_map.location_id", array('districtMaps' => new Expression("GROUP_CONCAT(location_name)")))
                    ->where(array('u_d_map.user_id' => $aRow['id']));
                $userDistrictQueryStr = $sql->buildSqlString($userDistrictQuery);
                $userDistrictResult = $dbAdapter->query($userDistrictQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                if (isset($userDistrictResult) && trim($userDistrictResult->districtMaps) != '') {
                    $accessLevel .= ' (' . $userDistrictResult->districtMaps . ')';
                }
            }

            $row = array();
            $row[] = ucwords($aRow['first_name'] . " " . $aRow['last_name']);
            $row[] = $aRow['email'];
            $row[] = ucwords($aRow['role_name']);
            $row[] = $accessLevel;
            $row[] = ucwords($aRow['status']);
            if ($update) {
                $edit = '<a href="/users/edit/' . base64_encode($aRow['id']) . '" title="Edit"><i class="fa fa-pencil"></i> Edit</a>';
                $row[] = $edit;
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchUser($id)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('u' => 'users'))
            ->join(array('urm' => 'user_role_map'), "urm.user_id=u.id", array('role_id'))
            ->join(array('r' => 'roles'), "r.role_id=urm.role_id", array('access_level'))
            ->where(array('id' => $id));
        $queryStr = $sql->buildSqlString($query);
        $queryResult = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if ($queryResult) {
            //User-Token
            $userTokenQuery = $sql->select()->from(array('u_t_map' => 'user_token_map'))
                ->columns(array('token'))
                ->where(array('user_id' => $id))
                ->order("token ASC");
            $userTokenQueryStr = $sql->buildSqlString($userTokenQuery);
            $queryResult['userToken'] = $dbAdapter->query($userTokenQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            //User-District
            $userDistrictQuery = $sql->select()->from(array('u_d_map' => 'user_district_map'))
                ->columns(array('location_id'))
                ->join(array('l_d' => 'location_details'), 'l_d.location_id=u_d_map.location_id', array('parent_location', 'country'))
                ->where(array('user_id' => $id));
            $userDistrictQueryStr = $sql->buildSqlString($userDistrictQuery);
            $queryResult['userDistricts'] = $dbAdapter->query($userDistrictQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (isset($queryResult['userDistricts']) && count($queryResult['userDistricts']) > 0) {
                $provinces = array();
                $countries = array();
                foreach ($queryResult['userDistricts'] as $district) {
                    if (!in_array($district['parent_location'], $provinces)) {
                        $provinces[] = $district['parent_location'];
                    }
                    if (!in_array($district['country'], $countries)) {
                        $countries[] = $district['country'];
                    }
                }
                $queryResult['selectedProvinces'] = $provinces;
                $queryResult['selectedCountries'] = $countries;
            }
            //User-Province
            $userProvinceQuery = $sql->select()->from(array('u_p_map' => 'user_province_map'))
                ->columns(array('location_id'))
                ->join(array('l_d' => 'location_details'), 'l_d.location_id=u_p_map.location_id', array('country'))
                ->where(array('user_id' => $id));
            $userProvinceQueryStr = $sql->buildSqlString($userProvinceQuery);
            $queryResult['userProvinces'] = $dbAdapter->query($userProvinceQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (isset($queryResult['userProvinces']) && count($queryResult['userProvinces']) > 0) {
                $countries = array();
                foreach ($queryResult['userProvinces'] as $province) {
                    if (!in_array($province['country'], $countries)) {
                        $countries[] = $province['country'];
                    }
                }
                $queryResult['selectedCountries'] = $countries;
            }
            //User-Country
            $userCountryQuery = $sql->select()->from(array('u_c_map' => 'user_country_map'))
                ->columns(array('country_id'))
                ->where(array('user_id' => $id));
            $userCountryQueryStr = $sql->buildSqlString($userCountryQuery);
            $queryResult['userCountries'] = $dbAdapter->query($userCountryQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        }
        return $queryResult;
    }
}

<?php

namespace Certification\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\TableGateway\TableGateway;

class DistrictTable extends AbstractTableGateway
{

    protected $tableGateway;
    protected $adapter;
    protected $table = 'certification_districts';
    public $sm = null;


    public function __construct(Adapter $adapter, $sm = null)
    {
        $this->adapter = $adapter;
        $this->sm = $sm;


        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new District());
        $this->tableGateway = new TableGateway($this->table, $this->adapter, null, $resultSetPrototype);
    }

    //public function fetchAll() {
    //    $sqlSelect = $this->tableGateway->getSql()->select();
    //    $sqlSelect->columns(array('id', 'district_name', 'region'));
    //    $sqlSelect->join('certification_regions', 'certification_regions.id= certification_districts.region ', array('region_name'), 'left');
    //    $sqlSelect->order('district_name asc');
    //
    //    $resultSet = $this->tableGateway->selectWith($sqlSelect);
    //    return $resultSet;
    //}
    //
    //public function getDistrict($id) {
    //    $id = (int) $id;
    //    $rowset = $this->tableGateway->select(array('id' => $id));
    //    $row = $rowset->current();
    //    if (!$row) {
    //        throw new \Exception("Could not find row $id");
    //    }
    //    return $row;
    //}
    //
    //public function saveDistrict(District $district) {
    //    $data = array(
    //        'district_name' => strtoupper($district->district_name),
    //        'region' => $district->region,
    //    );
    //
    //    $id = (int) $district->id;
    //    if ($id == 0) {
    //        $this->tableGateway->insert($data);
    //    } else {
    //        if ($this->getDistrict($id)) {
    //            $this->tableGateway->update($data, array('id' => $id));
    //        } else {
    //            throw new \Exception('District id does not exist');
    //        }
    //    }
    //}
    //
    //public function deleteDistrict($id) {
    //    $this->tableGateway->delete(array('id' => (int) $id));
    //}

    public function foreigne_key($district)
    {
        $db = $this->adapter;
        $sql = 'SELECT COUNT(district) as nombre from certification_facilities  WHERE district=' . $district;
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        return $nombre;
    }
}

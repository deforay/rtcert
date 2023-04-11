<?php

namespace Certification\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\TableGateway\TableGateway;

class FacilityTable extends AbstractTableGateway
{
    protected $tableGateway;
    protected $adapter;
    protected $table = 'certification_facilities';
    public $sm = null;


    public function __construct(Adapter $adapter, $sm = null)
    {
        $this->adapter = $adapter;
        $this->sm = $sm;


        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Facility());
        $this->tableGateway = new TableGateway($this->table, $this->adapter, null, $resultSetPrototype);
    }
    public function fetchAll()
    {
        $db = $this->adapter;
        $sql = 'SELECT * FROM certification_facilities as c_f LEFT JOIN location_details as l_d ON l_d.location_id=c_f.district ORDER BY facility_name ASC';
        $statement = $db->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getFacility($id)
    {
        $id  = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }

    public function saveFacility(Facility $facility)
    {
        $data = array(
            'facility_name' => strtoupper($facility->facility_name),
            'contact_person_name'  => $facility->contact_person_name,
            'phone_no'  => $facility->phone_no,
            'email_id'  => $facility->email_id,
            'facility_address'  => $facility->facility_address,
            'latitude'  => $facility->latitude,
            'longitude'  => $facility->longitude,
            'district'  => $facility->district
        );

        $id = (int) $facility->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getFacility($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('Facility id does not exist');
            }
        }
    }

    public function deleteFacility($id)
    {
        $this->tableGateway->delete(array('id' => (int) $id));
    }

    public function foreigne_key($facility)
    {
        $db = $this->adapter;
        $sql1 = 'SELECT COUNT(facility_id) as nombre from provider  WHERE facility_id=' . $facility;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        return $nombre;
    }
}

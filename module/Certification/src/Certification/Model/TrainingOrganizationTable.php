<?php

namespace Certification\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Paginator\Adapter\DbSelect;
use Laminas\Paginator\Paginator;

class TrainingOrganizationTable extends AbstractTableGateway
{
    protected $tableGateway;
    protected $adapter;
    protected $table = 'training_organization';
    public $sm = null;

    public function __construct(Adapter $adapter, $sm = null)
    {
        $this->adapter = $adapter;
        $this->sm = $sm;

        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new Region());
        $this->tableGateway =  new TableGateway($this->table, $this->adapter, null, $resultSetPrototype);
    }

    public function fetchAll() {


        $select = new Select('training_organization');
        $select->order('training_organization_name asc');
        $resultSet = $this->tableGateway->select();
        return $resultSet;
    }

    public function getTraining_organization($training_organization_id) {
        $training_organization_id = (int) $training_organization_id;
        $rowset = $this->tableGateway->select(array('training_organization_id' => $training_organization_id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $training_organization_id");
        }
        return $row;
    }

    public function saveTraining_Organization(TrainingOrganization $training_organization) {
        $data = array(
            'training_organization_name'    => strtoupper($training_organization->training_organization_name),
            'abbreviation'                  => $training_organization->abbreviation,
            'type_organization'             => $training_organization->type_organization,
            'address'                       => $training_organization->address,
            'phone'                         => $training_organization->phone
        );
//        var_dump($training_organization);
        $training_organization_id = (int) $training_organization->training_organization_id;
        if ($training_organization_id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getTraining_organization($training_organization_id)) {
                $this->tableGateway->update($data, array('training_organization_id' => $training_organization_id));
            } else {
                throw new \Exception('Training organization id does not exist');
            }
        }
    }

    public function deleteOrganization($training_organization_id) {
        $this->tableGateway->delete(array('training_organization_id' => (int) $training_organization_id));
    }

    public function foreigne_key($training_organization_id) {
        $db = $this->adapter;
        $sql1 = 'SELECT COUNT(training_organization_id) as nombre from training  WHERE training_organization_id=' . $training_organization_id;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        return $nombre;
    }

}

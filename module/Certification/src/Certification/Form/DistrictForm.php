<?php

namespace Certification\Form;

use Laminas\Form\Form;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Session\Container;
use Application\Model\GlobalTable;

class DistrictForm extends Form {

    public $adapter;
    public $regionLabel;
    public $districtsLabel;
    public $facilityLabel;
    public function __construct(AdapterInterface $dbAdapter) {
        $this->adapter = $dbAdapter;
        $globalDb = new GlobalTable($dbAdapter);
        
        $TranslateRegionLabel=$globalDb->getGlobalValue('region');
        if(trim($TranslateRegionLabel)==""){
            $TranslateRegionLabel="Region";
        }
        $TranslateDistrictsLabel=$globalDb->getGlobalValue('districts');
        if(trim($TranslateDistrictsLabel)==""){
            $TranslateDistrictsLabel="Districts";
        }
        $TranslateFacilityLabel=$globalDb->getGlobalValue('facilities');
        if(trim($TranslateFacilityLabel)==""){
            $TranslateFacilityLabel="Facilities";
        }
        
        $this->regionLabel=$TranslateRegionLabel;
        $this->districtsLabel=$TranslateDistrictsLabel;
        $this->facilityLabel=$TranslateFacilityLabel;
        // we want to ignore the name passed
        parent::__construct('district');

        $this->add(array(
            'name' => 'location_id',
            'type' => 'Hidden',
        ));
        $this->add(array(
            'name' => 'parent_location',
            'type' => 'select',
            'options' => array(
                'label' => $this->regionLabel,
                'empty_option' => 'Please Choose a '.$this->regionLabel,
                'value_options' => $this->getRegion(),
            ),
        ));
        $this->add(array(
            'name' => 'location_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Name of '.$this->districtsLabel,
            ),
        ));

        $this->add(array(
            'name' => 'location_code',
            'type' => 'Text',
            'options' => array(
                'label' => 'District Code',
            ),
        ));
        $this->add(array(
            'name' => 'latitude',
            'type' => 'Text',
            'options' => array(
                'label' => 'Latitude',
            ),
        ));
        $this->add(array(
            'name' => 'longitude',
            'type' => 'Text',
            'options' => array(
                'label' => 'Longitude',
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Go',
                'id' => 'submitbutton',
            ),
        ));
    }

    public function getRegion() {
        $dbAdapter = $this->adapter;
        $sql = 'SELECT location_id, location_name FROM location_details WHERE parent_location = 0 ORDER by location_name asc';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        $selectData = [];
        foreach ($result as $res) {
            $selectData[$res['location_id']] = ucwords($res['location_name']);
        }
        return $selectData;
    }

}

<?php

namespace Certification\Form;

use Laminas\Session\Container;
use Laminas\Form\Form;
use Laminas\Db\Adapter\AdapterInterface;
use Application\Model\GlobalTable;

class FacilityForm extends Form {

    public $adapter;
    public $districtsLabel;
    public $facilityLabel;
    public function __construct(AdapterInterface $dbAdapter) {
        $this->adapter = $dbAdapter;
        
        $globalDb = new GlobalTable($dbAdapter);
       
        $TranslateDistrictsLabel=$globalDb->getGlobalValue('districts');
        if(trim($TranslateDistrictsLabel)==""){
            $TranslateDistrictsLabel="Districts";
        }
        $TranslateFacilityLabel=$globalDb->getGlobalValue('facilities');
        if(trim($TranslateFacilityLabel)==""){
            $TranslateFacilityLabel="Facilities";
        }
        
        $this->districtsLabel=$TranslateDistrictsLabel;
        $this->facilityLabel=$TranslateFacilityLabel;
        
        // we want to ignore the name passed
        parent::__construct('certification_facilities');

        $this->add(array(
            'name' => 'id',
            'type' => 'Hidden',
        ));
        $this->add(array(
            'name' => 'district',
            'type' => 'select',
            'options' => array(
                'label' => $this->districtsLabel,
                'empty_option' => 'Please Choose a '.$this->districtsLabel,
                'value_options' => $this->getDistrict(),
            ),
        ));
        $this->add(array(
            'name' => 'facility_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Name of '.$this->facilityLabel,
            ),
        ));
        $this->add(array(
            'name' => 'contact_person_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Contact Person Name',
            ),
        ));
        $this->add(array(
            'name' => 'phone_no',
            'type' => 'Text',
            'options' => array(
                'label' => 'Phone Number',
            ),
        ));
        $this->add(array(
            'name' => 'email_id',
            'type' => 'Text',
            'options' => array(
                'label' => 'Email ID',
            ),
        ));
        $this->add(array(
            'name' => 'facility_address',
            'type' => 'Text',
            'options' => array(
                'label' => $this->facilityLabel.' Address',
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

    public function getDistrict() {
        $dbAdapter = $this->adapter;
        $sql = 'SELECT location_id, location_name FROM location_details WHERE parent_location != 0 ORDER by location_name asc';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        $selectData = [];
        foreach ($result as $res) {
            $selectData[$res['location_id']] = ucwords($res['location_name']);
        }
        return $selectData;
    }

}

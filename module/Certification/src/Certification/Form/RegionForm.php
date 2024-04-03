<?php

namespace Certification\Form;

use Laminas\Session\Container;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Form\Form;
use Application\Model\GlobalTable;

class RegionForm extends Form
{
    public $regionLabel;
    protected $adapter;

    public function __construct(AdapterInterface $dbAdapter)
    {
        $this->adapter = $dbAdapter;
        $globalDb = new GlobalTable($dbAdapter);
        $TranslateRegionLabel = $globalDb->getGlobalValue('region');
        if (trim($TranslateRegionLabel) == "") {
            $TranslateRegionLabel = "Region";
        }
        $this->regionLabel = $TranslateRegionLabel;
        // we want to ignore the name passed
        parent::__construct('region');

        $this->setAttributes(array(
            'method' => 'post',
        ));

        $this->add(array(
            'name' => 'location_id',
            'type' => 'Hidden',
        ));
        $this->add(array(
            'name' => 'country',
            'type' => 'select',
            'options' => array(
                'label' => 'Country',
                'disable_inarray_validator' => true,
                'empty_option' => 'Please Choose a Country',
                'value_options' => $this->getAllActiveCountries()
            ),
        ));
        $this->add(array(
            'name' => 'location_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Name of ' . $this->regionLabel,
            ),
        ));
        $this->add(array(
            'name' => 'location_code',
            'type' => 'Text',
            'options' => array(
                'label' => 'Province Code',
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

    public function getAllActiveCountries()
    {
        $logincontainer = new Container('credo');
        $countryWhere = 'WHERE country_status = "active"';
        if (!empty($logincontainer->country)) {
            $countryWhere = 'WHERE country_id IN(' . implode(',', $logincontainer->country) . ') AND country_status = "active"';
        }
        $dbAdapter = $this->adapter;
        $sql = 'SELECT country_id, country_name FROM country ' . $countryWhere . ' ORDER by country_name asc';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        $selectData = [];
        foreach ($result as $res) {
            $selectData[$res['country_id']] = ucwords($res['country_name']);
        }
        return $selectData;
    }
}

<?php

namespace Certification\Form;

use Laminas\Form\Form;
use Laminas\Session\Container;
use Laminas\Db\Adapter\AdapterInterface;
use Application\Model\GlobalTable;

class ExaminationForm extends Form
{
    public function __construct(AdapterInterface $dbAdapter)
    {
        $this->adapter = $dbAdapter;
        $globalDb = new GlobalTable($dbAdapter);

        $TranslateRegionLabel = $globalDb->getGlobalValue('region');
        if (trim($TranslateRegionLabel) == "") {
            $TranslateRegionLabel = "Region";
        }
        $TranslateDistrictsLabel = $globalDb->getGlobalValue('districts');
        if (trim($TranslateDistrictsLabel) == "") {
            $TranslateDistrictsLabel = "Districts";
        }
        $TranslateFacilityLabel = $globalDb->getGlobalValue('facilities');
        if (trim($TranslateFacilityLabel) == "") {
            $TranslateFacilityLabel = "Facilities";
        }
        $this->regionLabel = $TranslateRegionLabel;
        $this->districtsLabel = $TranslateDistrictsLabel;
        $this->facilityLabel = $TranslateFacilityLabel;
        // we want to ignore the name passed
        parent::__construct('examination');

        $this->add(array(
            'name' => 'id',
            'type' => 'Hidden',
        ));
        $this->add(array(
            'name' => 'provider',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Provider',
                'empty_option' => 'Please choose an Type',
                'value_options' => $this->getListProvider()
            ),
        ));
        $this->add(array(
            'name' => 'id_written_exam',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Written Exam',
                'empty_option' => 'Please choose an Type',
                'value_options' => $this->getListwritten()
            ),
        ));
        $this->add(array(
            'name' => 'practical_exam_id',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Practical Exam',
                'empty_option' => 'Please choose an Type',
                'value_options' => $this->getListpractical()
            ),
        ));
        $this->add(array(
            'name' => 'country',
            'type' => 'select',
            'options' => array(
                'label' => 'Country',
                'disable_inarray_validator' => true,
                'empty_option' => 'Please Choose a Country',
                'value_options' => $this->getAllActiveCountries(),
                'value' => 1,
            ),
        ));

        $this->add(array(
            'name' => 'region',
            'type' => 'select',
            'options' => array(
                'label' => $this->regionLabel,
                'disable_inarray_validator' => true,
                'empty_option' => 'Please Choose a Country First'
            ),
        ));
        $this->add(array(
            'name' => 'district',
            'type' => 'Select',
            'options' => array(
                'label' => $this->districtsLabel,
                'disable_inarray_validator' => true,
                'empty_option' => 'Please Choose a Region First'
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

    public function getListProvider()
    {
        $dbAdapter = $this->adapter;
        $sql = 'SELECT id,certification_id,last_name,first_name,middle_name FROM provider order by last_name asc ';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['id']] = $res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name'] . ' ' . $res['certification_id'];
        }
        return $selectData;
    }

    public function getListwritten()
    {
        $dbAdapter = $this->adapter;
        $sql = 'SELECT id_written_exam,final_score,exam_type,last_name,first_name,middle_name FROM written_exam,provider where provider.certification_id=written_exam.provider_id order by last_name asc ';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['id_written_exam']] = $res['exam_type'] . ' ' . $res['final_score'] . ' ' . $res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name'];
        }
        return $selectData;
    }

    public function getListpractical()
    {
        $dbAdapter = $this->adapter;
        $sql = 'SELECT practice_exam_id,practical_total_score,exam_type,last_name,first_name,middle_name FROM practical_exam,provider where provider.certification_id=practical_exam.provider_id order by last_name asc ';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['practice_exam_id']] = $res['exam_type'] . ' ' . $res['practical_total_score'] . ' ' . $res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name'];
        }
        return $selectData;
    }

    public function getAllActiveCountries()
    {
        $logincontainer = new Container('credo');
        $countryWhere = 'WHERE country_status = "active"';
        if (isset($logincontainer->country) && count($logincontainer->country) > 0) {
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

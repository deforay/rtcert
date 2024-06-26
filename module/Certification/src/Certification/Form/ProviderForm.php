<?php

namespace Certification\Form;

use Laminas\Form\Form;
use Laminas\Session\Container;
use Laminas\Db\Adapter\AdapterInterface;
use Application\Model\GlobalTable;

class ProviderForm extends Form
{

    public $regionLabel;
    public $districtsLabel;
    public $facilityLabel;
    protected $adapter;

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

        parent::__construct("provider");

        $this->setAttributes(array(
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $this->add(array(
            'name' => 'id',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'certification_reg_no',
            'type' => 'Text',
            'options' => array(
                'label' => 'Certification Registration ID',
            ),
        ));

        $this->add(array(
            'name' => 'certification_id',
            'type' => 'Text',
            'options' => array(
                'label' => 'Certification ID',
            ),
        ));

        $this->add(array(
            'name' => 'professional_reg_no',
            'type' => 'Text',
            'options' => array(
                'label' => 'Professional Registration No (if available)',
            ),
        ));
        $this->add(array(
            'name' => 'last_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Last Name (Surname)',
            ),
        ));
        $this->add(array(
            'name' => 'first_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'First Name',
            ),
        ));

        $this->add(array(
            'name' => 'middle_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Middle Name (3rd name)',
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
            'name' => 'type_vih_test',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Type of HIV Test Modality/Point',
                'empty_option' => 'Please Choose a Type',
                'value_options' => array(
                    'ART clinic' => 'ART clinic',
                    'Community' => 'Community',
                    'IPD' => 'IPD',
                    'LAB' => 'LAB',
                    'OPD' => 'OPD',
                    'PITC' => 'PITC',
                    'PMTCT' => 'PMTCT',
                    'STI clinic' => 'STI clinic',
                    'TB clinic' => 'TB clinic',
                    'VCT/HTC' => 'VCT/HTC',
                    'VCT/PMTCT' => 'VCT/PMTCT'
                )
            ),
        ));
        $this->add(array(
            'name' => 'phone',
            'type' => 'text',
            'options' => array(
                'label' => 'Phone',
            ),
        ));
        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email',
            ),
        ));
        $this->add(array(
            'name' => 'prefered_contact_method',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Prefered Contact Method',
                'empty_option' => 'Please Choose a Method',
                'value_options' => array(
                    'Phone' => 'Phone',
                    'Email' => 'Email'
                )
            ),
        ));
        $this->add(array(
            'name' => 'current_jod',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Current Job Title',
                'empty_option' => 'Please Choose a Job Title',
                'value_options' => array(
                    'Assistant Medical Officer' => 'Assistant Medical Officer',
                    'Counselor' => 'Counselor',
                    'Health assistant' => 'Health assistant',
                    'Health attendant' => 'Health attendant',
                    'Lab Assistant' => 'Lab Assistant',
                    'Lab Scientist' => 'Lab Scientist',
                    'Lab technician' => 'Lab technician',
                    'Lab technologist' => 'Lab technologist',
                    'Medical doctor' => 'Medical doctor',
                    'Midwife' => 'Midwife',
                    'Nurse' => 'Nurse',
                    'Nurse assistant' => 'Nurse assistant'
                ),
            ),
        ));

        $this->add(array(
            'name' => 'time_worked',
            'type' => 'Number',
            'options' => array(
                'label' => 'Time Worked As Tester',
            ),
            'attributes' => [
                'min' => '1',
                'step' => '1', // default step interval is 1
            ],
        ));

        $this->add(array(
            'name' => 'username',
            'type' => 'Text',
            'options' => array(
                'label' => 'Username',
            ),
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => 'Password',
            ),
        ));

        $this->add(array(
            'name' => 'test_site_in_charge_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Full Name',
            ),
        ));
        $this->add(array(
            'name' => 'test_site_in_charge_phone',
            'type' => 'Text',
            'options' => array(
                'label' => 'Phone',
            ),
        ));
        $this->add(array(
            'name' => 'test_site_in_charge_email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email',
            ),
        ));
        $this->add(array(
            'name' => 'facility_in_charge_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Full Name',
            ),
        ));
        $this->add(array(
            'name' => 'facility_in_charge_phone',
            'type' => 'Text',
            'options' => array(
                'label' => 'Phone',
            ),
        ));
        $this->add(array(
            'name' => 'facility_in_charge_email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email',
            ),
        ));



        $this->add(array(
            'name' => 'facility_id',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Facility Name',
                'disable_inarray_validator' => true,
                'empty_option' => 'Please Choose a District first',
            ),
        ));


        $this->add(array(
            'name' => 'profile_picture',
            'type' => 'Laminas\Form\Element\File',
            'options' => array(
                'label' => 'Upload Profile Picture'
            ),
            'attributes' => array(
                'class' => 'form-control',
            )
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

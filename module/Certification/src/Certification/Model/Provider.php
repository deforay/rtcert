<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\InputFilterAwareInterface;

class Provider {

    /**
     * @var mixed
     */
    public $facility_name;
    /**
     * @var mixed
     */
    public $facility_address;
    /**
     * @var mixed
     */
    public $region_name;
    /**
     * @var mixed
     */
    public $district_name;
    public $id;
    public $certification_reg_no;
    public $certification_id;
    public $professional_reg_no;
    public $last_name;
    public $first_name;
    public $middle_name;
    public $region;
    public $district;
    public $type_vih_test;
    public $phone;
    public $email;
    public $prefered_contact_method;
    public $current_jod;
    public $time_worked;
    public $username;
    public $password;
    public $test_site_in_charge_name;
    public $test_site_in_charge_phone;
    public $test_site_in_charge_email;
    public $facility_in_charge_name;
    public $facility_in_charge_phone;
    public $facility_in_charge_email;
    public $facility_id;
    public $date_certificate_issued;
    public $date_end_validity;
    public $final_decision;
    public $certid;
    public $examid;
    public $link_send_count;
    public $link_send_on;
    public $link_send_by;
    public $link_token;
    public $profile_picture;
    
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->id = (empty($data['id'])) ? null : $data['id'];
        $this->certification_reg_no = (empty($data['certification_reg_no'])) ? null : $data['certification_reg_no'];
        $this->professional_reg_no = (empty($data['professional_reg_no'])) ? null : $data['professional_reg_no'];
        $this->certification_id = (empty($data['certification_id'])) ? null : $data['certification_id'];
        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
        $this->region = (empty($data['region'])) ? null : $data['region'];
        $this->district = (empty($data['district'])) ? null : $data['district'];
        $this->type_vih_test = (empty($data['type_vih_test'])) ? null : $data['type_vih_test'];
        $this->phone = (empty($data['phone'])) ? null : $data['phone'];
        $this->email = (empty($data['email'])) ? null : $data['email'];
        $this->prefered_contact_method = (empty($data['prefered_contact_method'])) ? null : $data['prefered_contact_method'];
        $this->current_jod = (empty($data['current_jod'])) ? null : $data['current_jod'];
        $this->time_worked = (empty($data['time_worked'])) ? null : $data['time_worked'];
        $this->username = (empty($data['username'])) ? null : $data['username'];
        $this->password = (empty($data['password'])) ? null : $data['password'];
        $this->test_site_in_charge_name = (empty($data['test_site_in_charge_name'])) ? null : $data['test_site_in_charge_name'];
        $this->test_site_in_charge_phone = (empty($data['test_site_in_charge_phone'])) ? null : $data['test_site_in_charge_phone'];
        $this->test_site_in_charge_email = (empty($data['test_site_in_charge_email'])) ? null : $data['test_site_in_charge_email'];
        $this->facility_in_charge_name = (empty($data['facility_in_charge_name'])) ? null : $data['facility_in_charge_name'];
        $this->facility_in_charge_phone = (empty($data['facility_in_charge_phone'])) ? null : $data['facility_in_charge_phone'];
        $this->facility_in_charge_email = (empty($data['facility_in_charge_email'])) ? null : $data['facility_in_charge_email'];
        $this->facility_id = (empty($data['facility_id'])) ? null : $data['facility_id'];
        $this->facility_name = (empty($data['facility_name'])) ? null : $data['facility_name'];
        $this->facility_address = (empty($data['facility_address'])) ? null : $data['facility_address'];
    
        $this->region_name = (empty($data['region_name'])) ? null : $data['region_name'];
        $this->district_name = (empty($data['district_name'])) ? null : $data['district_name'];
        
        $this->date_certificate_issued = (empty($data['date_certificate_issued'])) ? null : $data['date_certificate_issued'];
        $this->date_end_validity = (empty($data['date_end_validity'])) ? null : $data['date_end_validity'];
        $this->final_decision = (empty($data['final_decision'])) ? null : $data['final_decision'];
        $this->certid = (empty($data['certid'])) ? null : $data['certid'];
        $this->examid = (empty($data['examid'])) ? null : $data['examid'];
        $this->link_send_count = (empty($data['link_send_count'])) ? null : $data['link_send_count'];
        $this->link_send_on = (empty($data['link_send_on'])) ? null : $data['link_send_on'];
        $this->link_send_by = (empty($data['link_send_by'])) ? null : $data['link_send_by'];
        $this->link_token = (empty($data['link_token'])) ? null : $data['link_token'];
        $this->profile_picture = (empty($data['profile_picture'])) ? null : $data['profile_picture'];
    }

    public function getArrayCopy() {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $inputFilter->add(array(
                'name' => 'id',
                'required' => false,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'certification_reg_no',
                'required' => false,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'certification_id',
                'required' => false,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'professional_reg_no',
                'required' => false,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'last_name',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));


            $inputFilter->add(array(
                'name' => 'first_name',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'middle_name',
                'required' => false,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('encoding' => 'UTF-8',),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'region',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'district',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));


            $inputFilter->add(array(
                'name' => 'type_vih_test',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));


            $inputFilter->add(array(
                'name' => 'phone',
                'required' => true,
            ));

            $inputFilter->add(array(
                'name' => 'email',
                'required' => false,
            ));
            $inputFilter->add(array(
                'name' => 'prefered_contact_method',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'current_jod',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'time_worked',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));
            $inputFilter->add(array(
                'name' => 'username',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'password',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'test_site_in_charge_name',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'test_site_in_charge_phone',
                'required' => true,
            ));

            $inputFilter->add(array(
                'name' => 'test_site_in_charge_email',
                'required' => false,
            ));

            $inputFilter->add(array(
                'name' => 'facility_in_charge_name',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'facility_in_charge_phone',
                'required' => true,
            ));

            $inputFilter->add(array(
                'name' => 'facility_in_charge_email',
                'required' => false,
            ));

            $inputFilter->add(array(
                'name' => 'facility_id',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                        ),
                    ),
                ),
            ));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}

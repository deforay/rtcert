<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;

class Certification {

    /**
     * @var mixed
     */
    public $certification_reg_no;
    /**
     * @var mixed
     */
    public $last_name;
    /**
     * @var mixed
     */
    public $first_name;
    /**
     * @var mixed
     */
    public $middle_name;
    /**
     * @var mixed
     */
    public $professional_reg_no;
    /**
     * @var mixed
     */
    public $certification_id;
    /**
     * @var mixed
     */
    public $email;
    /**
     * @var mixed
     */
    public $facility_in_charge_email;
    public $id;
    public $provider;
    public $examination;
    public $final_decision;
    public $certification_issuer;
    public $date_certificate_issued;
    public $date_certificate_sent;
    public $certification_type;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->id = (empty($data['id'])) ? null : $data['id'];
        $this->provider = (empty($data['provider'])) ? null : $data['provider'];
        $this->examination = (empty($data['examination'])) ? null : $data['examination'];
        $this->final_decision = (empty($data['final_decision'])) ? null : $data['final_decision'];
        $this->certification_issuer = (empty($data['certification_issuer'])) ? null : $data['certification_issuer'];
        $this->date_certificate_issued = (empty($data['date_certificate_issued'])) ? null : $data['date_certificate_issued'];
        $this->date_certificate_sent = (empty($data['date_certificate_sent'])) ? null : $data['date_certificate_sent'];
        $this->certification_type = (empty($data['certification_type'])) ? null : $data['certification_type'];
        $this->certification_reg_no = (empty($data['certification_reg_no'])) ? null : $data['certification_reg_no'];
        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
        $this->professional_reg_no = (empty($data['professional_reg_no'])) ? null : $data['professional_reg_no'];
        $this->certification_id = (empty($data['certification_id'])) ? null : $data['certification_id'];
        $this->email = (empty($data['email'])) ? null : $data['email'];
        $this->facility_in_charge_email = (empty($data['facility_in_charge_email'])) ? null : $data['facility_in_charge_email'];
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
                'name' => 'provider',
                'required' => false,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'examination',
                'required' => false,
                'filters' => array(
                    array('name' => 'int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'final_decision',
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
                'name' => 'certification_issuer',
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
                'name' => 'date_certificate_issued',
                'required' => false,
            ));

            $inputFilter->add(array(
                'name' => 'date_certificate_sent',
                'required' => false,
            ));

            $inputFilter->add(array(
                'name' => 'certification_type',
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

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}

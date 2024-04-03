<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterInterface;

class Training {

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
    public $certification_reg_no;
    /**
     * @var mixed
     */
    public $training_organization_name;
    /**
     * @var mixed
     */
    public $type_organization;
    public $training_id;
    public $Provider_id;
    public $type_of_competency;
    public $last_training_date;
    public $type_of_training;
    public $length_of_training;
    public $training_organization_id;
    public $facilitator;
    public $training_certificate;
    public $date_certificate_issued;
    public $Comments;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->training_id = (empty($data['training_id'])) ? null : $data['training_id'];
        $this->Provider_id = (empty($data['Provider_id'])) ? null : $data['Provider_id'];
        $this->type_of_competency = (empty($data['type_of_competency'])) ? null : $data['type_of_competency'];
        $this->last_training_date = (empty($data['last_training_date'])) ? null : $data['last_training_date'];
        $this->type_of_training = (empty($data['type_of_training'])) ? null : $data['type_of_training'];
        $this->training_organization_id = (empty($data['training_organization_id'])) ? null : $data['training_organization_id'];
        $this->length_of_training = (empty($data['length_of_training'])) ? null : $data['length_of_training'];
        $this->facilitator = (empty($data['facilitator'])) ? null : $data['facilitator'];
        $this->date_certificate_issued = (empty($data['date_certificate_issued'])) ? null : $data['date_certificate_issued'];
        $this->training_certificate = (empty($data['training_certificate'])) ? null : $data['training_certificate'];
        $this->Comments = (empty($data['Comments'])) ? null : $data['Comments'];

        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
        $this->professional_reg_no = (empty($data['professional_reg_no'])) ? null : $data['professional_reg_no'];
        $this->certification_id = (empty($data['certification_id'])) ? null : $data['certification_id'];
        $this->certification_reg_no = (empty($data['certification_reg_no'])) ? null : $data['certification_reg_no'];

        $this->training_organization_name = (empty($data['training_organization_name'])) ? null : $data['training_organization_name'];
        $this->type_organization = (empty($data['type_organization'])) ? null : $data['type_organization'];
    }

    public function getArrayCopy() {
        return get_object_vars($this);
    }

//
    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $inputFilter->add(array(
                'name' => 'training_id',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'Provider_id',
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
                'name' => 'type_of_competency',
                'required' =>true,
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
                'name' => 'last_training_date',
                'required' => true,
            ));

            
            $inputFilter->add(array(
                'name' => 'type_of_training',
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
                'name' => 'length_of_training',
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
                'name' => 'training_organization_id',
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
                'name' => 'facilitator',
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
                'name' => 'date_certificate_issued',
                'required' => FALSE,
            ));

           $inputFilter->add(array(
                'name' => 'training_certificate',
                'required' => FALSE,
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
                'name' => 'Comments',
                'required' => FALSE,
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

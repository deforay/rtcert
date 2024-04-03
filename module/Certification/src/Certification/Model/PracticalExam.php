<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\InputFilterAwareInterface;

class PracticalExam implements InputFilterAwareInterface {

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
    public $admin_last_name;
    /**
     * @var mixed
     */
    public $admin_first_name;
    /**
     * @var mixed
     */
    public $admin_middle_name;
    public $practice_exam_id;
    public $exam_type;
    public $exam_admin;
    public $location_name;
    public $provider_id;
    public $Sample_testing_score;
    public $direct_observation_score;
    public $practical_total_score;
    public $date;
    public $training_id;
    protected $inputFilter;

    public function exchangeArray($data) {

        $this->practice_exam_id = (empty($data['practice_exam_id'])) ? null : $data['practice_exam_id'];
        $this->exam_type = (empty($data['exam_type'])) ? null : $data['exam_type'];
        $this->exam_admin = (empty($data['exam_admin'])) ? null : $data['exam_admin'];
        $this->location_name = (empty($data['location_name'])) ? null : $data['location_name'];
        $this->provider_id = (empty($data['provider_id'])) ? null : $data['provider_id'];
        $this->Sample_testing_score = (empty($data['Sample_testing_score'])) ? null : $data['Sample_testing_score'];
        $this->direct_observation_score = (empty($data['direct_observation_score'])) ? null : $data['direct_observation_score'];
        $this->practical_total_score = (empty($data['practical_total_score'])) ? null : $data['practical_total_score'];
        $this->date = (empty($data['date'])) ? null : $data['date'];
        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
        $this->admin_last_name = (empty($data['admin_last_name'])) ? null : $data['admin_last_name'];
        $this->admin_first_name = (empty($data['admin_first_name'])) ? null : $data['admin_first_name'];
        $this->admin_middle_name = (empty($data['admin_middle_name'])) ? null : $data['admin_middle_name'];
        $this->training_id = (empty($data['training_id'])) ? null : $data['training_id'];
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
                'name' => 'practice_exam_id',
                'required' => false,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'exam_type',
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
                'name' => 'exam_admin',
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
                'name' => 'provider_id',
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
                'name' => 'direct_observation_score',
                'required' => true,
            ));

            $inputFilter->add(array(
                'name' => 'Sample_testing_score',
                'required' => true,
            ));

            $inputFilter->add(array(
                'name' => 'date',
                'required' => true,
            ));

            $inputFilter->add(array(
                'name' => 'written',
                'required' => false,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'training_id',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                )                
            ));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}

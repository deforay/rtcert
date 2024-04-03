<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;

class Examination {

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
    public $certification_reg_no;
    /**
     * @var mixed
     */
    public $exam_type;
    public $final_score;
    public $practical_total_score;
    public $Sample_testing_score;
    public $direct_observation_score;
    public $id;
    public $provider;
    public $id_written_exam;
    public $practical_exam_id;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->id = (empty($data['id'])) ? null : $data['id'];
        $this->provider = (empty($data['provider'])) ? null : $data['provider'];
        $this->practical_exam_id = (empty($data['practical_exam_id'])) ? null : $data['practical_exam_id'];
        $this->id_written_exam = (empty($data['id_written_exam'])) ? null : $data['id_written_exam'];

        $this->professional_reg_no = (empty($data['professional_reg_no'])) ? null : $data['professional_reg_no'];
        $this->certification_id = (empty($data['certification_id'])) ? null : $data['certification_id'];
        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
        $this->certification_reg_no = (empty($data['certification_reg_no'])) ? null : $data['certification_reg_no'];

        $this->exam_type = (empty($data['exam_type'])) ? null : $data['exam_type'];
        $this->final_score = (empty($data['final_score'])) ? 0 : $data['final_score'];

        $this->practical_total_score = (empty($data['practical_total_score'])) ? 0 : $data['practical_total_score'];
        $this->Sample_testing_score = (empty($data['Sample_testing_score'])) ? 0 : $data['Sample_testing_score'];
        $this->direct_observation_score = (empty($data['direct_observation_score'])) ? 0 : $data['direct_observation_score'];
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $inputFilter->add(array(
                'name' => 'id',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));
            $inputFilter->add(array(
                'name' => 'provider',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'id_written_exam',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                    
                ),
            ));

            $inputFilter->add(array(
                'name' => 'practical_exam_id',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                    
                ),
            ));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}

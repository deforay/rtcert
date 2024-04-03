<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\InputFilterAwareInterface;

class WrittenExam implements InputFilterAwareInterface {

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
    public $name_exam_type;
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
    public $id_written_exam;
    public $test_id;
    public $exam_type;
    public $provider_id;
    public $exam_admin;
    public $location_name;
    public $date;
    public $qa_point;
    public $rt_point;
    public $safety_point;
    public $specimen_point;
    public $testing_algo_point;
    public $report_keeping_point;
    public $EQA_PT_points;
    public $ethics_point;
    public $inventory_point;
    public $total_points;
    public $final_score;
    public $training_id;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->id_written_exam = (empty($data['id_written_exam'])) ? null : $data['id_written_exam'];
        $this->test_id = (empty($data['test_id'])) ? null : $data['test_id'];
        $this->exam_type = (empty($data['exam_type'])) ? null : $data['exam_type'];
        $this->provider_id = (empty($data['provider_id'])) ? null : $data['provider_id'];
        $this->exam_admin = (empty($data['exam_admin'])) ? null : $data['exam_admin'];
        $this->location_name = (empty($data['location_name'])) ? null : $data['location_name'];
        $this->date = (empty($data['date'])) ? null : $data['date'];
        $this->qa_point = (empty($data['qa_point'])) ? 0 : $data['qa_point'];
        $this->rt_point = (empty($data['rt_point'])) ? 0 : $data['rt_point'];
        $this->safety_point = (empty($data['safety_point'])) ? 0 : $data['safety_point'];
        $this->specimen_point = (empty($data['specimen_point'])) ? 0 : $data['specimen_point'];
        $this->testing_algo_point = (empty($data['testing_algo_point'])) ? 0 : $data['testing_algo_point'];
        $this->report_keeping_point = (empty($data['report_keeping_point'])) ? 0 : $data['report_keeping_point'];
        $this->EQA_PT_points = (empty($data['EQA_PT_points'])) ? 0 : $data['EQA_PT_points'];
        $this->ethics_point = (empty($data['ethics_point'])) ? 0 : $data['ethics_point'];
        $this->inventory_point = (empty($data['inventory_point'])) ? 0 : $data['inventory_point'];
        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
        $this->name_exam_type = (empty($data['name_exam_type'])) ? null : $data['name_exam_type'];
        $this->admin_last_name = (empty($data['admin_last_name'])) ? null : $data['admin_last_name'];
        $this->admin_first_name = (empty($data['admin_first_name'])) ? null : $data['admin_first_name'];
        $this->admin_middle_name = (empty($data['admin_middle_name'])) ? null : $data['admin_middle_name'];
        $this->total_points = (empty($data['total_points'])) ? 0 : $data['total_points'];
        $this->final_score = (empty($data['final_score'])) ? 0 : $data['final_score'];
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
                'name' => 'id_written_exam',
                'required' => FALSE,
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
                'name' => 'date',
                'required' => true,
            ));
            $inputFilter->add(array(
                'name' => 'qa_point',
                'required' => true,
            ));
            $inputFilter->add(array(
                'name' => 'rt_point',
                'required' => true,
            ));
            $inputFilter->add(array(
                'name' => 'safety_point',
                'required' => true,
            ));
            $inputFilter->add(array(
                'name' => 'specimen_point',
                'required' => true,
            ));
            $inputFilter->add(array(
                'name' => 'testing_algo_point',
                'required' => true,
            ));
            $inputFilter->add(array(
                'name' => 'report_keeping_point',
                 'required'=>true,
            ));
            $inputFilter->add(array(
                'name' => 'EQA_PT_points',
                 'required'=>true,
            ));
            $inputFilter->add(array(
                'name' => 'ethics_point',
                 'required'=>true,
            ));

            $inputFilter->add(array(
                'name' => 'inventory_point',
                 'required'=>true,
            ));

            $inputFilter->add(array(
                'name' => 'practical',
                'required' => false,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            // $inputFilter->add(array(
            //     'name' => 'training_id',
            //     'required' => true,
            //     'filters' => array(
            //         array('name' => 'StripTags'),
            //         array('name' => 'StringTrim'),
            //     ),
            //     'validators' => array(
            //         array(
            //             'name' => 'StringLength',
            //             'options' => array(
            //                 'encoding' => 'UTF-8',
            //             ),
            //         ),
            //     ),
            // ));

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

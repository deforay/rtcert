<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;

class Recertification {

    /**
     * @var mixed
     */
    public $date_certificate_issued;
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
    public $certification_id;
    public $recertification_id;
    public $due_date;
    public $provider_id;
    public $reminder_type;
    public $reminder_sent_to;
    public $name_of_recipient;
    public $date_reminder_sent;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->recertification_id = (empty($data['recertification_id'])) ? null : $data['recertification_id'];
        $this->due_date = (empty($data['due_date'])) ? null : $data['due_date'];
        $this->provider_id = (empty($data['provider_id'])) ? null : $data['provider_id'];
        $this->reminder_type = (empty($data['reminder_type'])) ? null : $data['reminder_type'];
        $this->reminder_sent_to = (empty($data['reminder_sent_to'])) ? null : $data['reminder_sent_to'];
        $this->name_of_recipient = (empty($data['name_of_recipient'])) ? null : $data['name_of_recipient'];
        $this->date_reminder_sent = (empty($data['date_reminder_sent'])) ? null : $data['date_reminder_sent'];
        $this->date_certificate_issued = (empty($data['date_certificate_issued'])) ? null : $data['date_certificate_issued'];
        $this->last_name = (empty($data['last_name'])) ? null : $data['last_name'];
        $this->first_name = (empty($data['first_name'])) ? null : $data['first_name'];
        $this->middle_name = (empty($data['middle_name'])) ? null : $data['middle_name'];
   $this->certification_id = (empty($data['certification_id'])) ? null : $data['certification_id'];
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
                'name' => 'recertification_id',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'due_date',
                'required' => false,
            ));

            $inputFilter->add(array(
                'name' => 'provider_id',
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
                'name' => 'reminder_type',
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
                'name' => 'reminder_sent_to',
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
                'name' => 'name_of_recipient',
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
                'name' => 'date_reminder_sent',
                'required' => FALSE,
            ));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}

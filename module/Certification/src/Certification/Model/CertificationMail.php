<?php

namespace Certification\Model;

use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;

class CertificationMail {

    /**
     * @var mixed
     */
    public $mail_date;
    public $mail_id;
    public $provider;
    public $to_email;
    public $cc;
    public $bcc;
    public $type;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->mail_id = (empty($data['mail_id'])) ? null : $data['mail_id'];
        $this->provider = (empty($data['provider'])) ? null : $data['provider'];
        $this->to_email = (empty($data['to_email'])) ? null : $data['to_email'];
        $this->cc = (empty($data['cc'])) ? null : $data['cc'];
        $this->bcc = (empty($data['bcc'])) ? null : $data['bcc'];
        $this->type = (empty($data['type'])) ? null : $data['type'];
        $this->mail_date = (empty($data['mail_date'])) ? null : $data['mail_date'];
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            
            $inputFilter->add(array(
                'name' => 'mail_id',
                'required' => FALSE,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));
            $inputFilter->add(array(
                'name' => 'type',
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
                'name' => 'provider',
                'required' => TRUE
            ));
            $inputFilter->add(array(
                'name' => 'add_to',
                'required' => FALSE
            ));
            $inputFilter->add(array(
                'name' => 'to_email',
                'required' => FALSE,
                'validators' => array(
                    array(
                        'name' => 'EmailAddress'
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'cc',
                'required' => FALSE,
                'validators' => array(
                    array(
                        'name' => 'EmailAddress'
                    ),
                ),
            ));
            $inputFilter->add(array(
                'name' => 'bcc',
                'required' => FALSE,
                'validators' => array(
                    array(
                        'name' => 'EmailAddress'
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name' => 'subject',
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
                'name' => 'message',
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

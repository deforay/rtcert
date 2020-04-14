<?php

namespace Certification\Form;

use Zend\Form\Form;

class TrainingOrganizationForm extends Form
{

    public function __construct($name = null)
    {

        parent::__construct('trainning_organization');
        $this->setAttributes(
            array(
                'method' => 'post',

            )
        );

        $this->add(array(
            'name' => 'training_organization_id',
            'type' => 'Hidden',
        ));
        $this->add(array(
            'name' => 'training_organization_name',
            'type' => 'Text',
            'options' => array(
                'label' => 'Name of the Organization',
            ),
        ));
        
        $this->add(array(
            'name' => 'abbreviation',
            'type' => 'Text',
            'options' => array(
                'label' => 'Abbreviation',
            ),
        ));

        $this->add(array(
            'name' => 'type_organization',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Type of Training Organization',
                'empty_option' => 'Please choose an Type of Organization',
                'value_options' => array(
                    'FBO' => 'FBO',
                    'Government' => 'Government',
                    'NGO' => 'NGO',
                    'Private' => 'Private'
                ),
            ),
        ));
        
        $this->add(array(
            'name' => 'address',
            'type' => 'Textarea',
            'options' => array(
                'label' => 'Address & Contact Information',
            ),
        ));
        
        $this->add(array(
            'name' => 'phone',
            'type' => 'Text',
            'options' => array(
                'label' => 'Phone Number',
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
}

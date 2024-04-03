<?php
 namespace Certification\Model;
 use Laminas\InputFilter\InputFilter;
 use Laminas\InputFilter\InputFilterAwareInterface;
 use Laminas\InputFilter\InputFilterInterface;

 class District
 {
     public $location_id;
     public $parent_location;
     public $location_name;
     public $location_code;
     public $latitude;
     public $longitude;
     protected $inputFilter;

     public function exchangeArray($data)
     {
         $this->location_id     = (empty($data['location_id'])) ? null : $data['location_id'];
         $this->parent_location  = (empty($data['parent_location'])) ? null : $data['parent_location'];
         $this->location_name = (empty($data['location_name'])) ? null : $data['location_name'];
         $this->location_code = (empty($data['location_code'])) ? null : $data['location_code'];
         $this->latitude = (empty($data['latitude'])) ? null : $data['latitude'];
         $this->longitude = (empty($data['longitude'])) ? null : $data['longitude'];
     }
      public function getArrayCopy()
     {
         return get_object_vars($this);
     }

          public function setInputFilter(InputFilterInterface $inputFilter)
     {
         throw new \Exception("Not used");
     }

     public function getInputFilter()
     {
         if (!$this->inputFilter) {
             $inputFilter = new InputFilter();

             $inputFilter->add(array(
                 'name'     => 'location_id',
                 'required' => false,
                 'filters'  => array(
                     array('name' => 'Int'),
                 ),
             ));

             $inputFilter->add(array(
                'name' => 'parent_location',
                'required' => true,
                'filters' => array(
                    array('name' => 'Int'),
                ),
            ));

             $inputFilter->add(array(
                 'name'     => 'location_name',
                 'required' => true,
                 'filters'  => array(
                     array('name' => 'StripTags'),
                     array('name' => 'StringTrim'),
                 ),
                 'validators' => array(
                     array(
                         'name'    => 'StringLength',
                         'options' => array(
                             'encoding' => 'UTF-8',
                             'min'      => 1,
                             'max'      => 100,
                         ),
                     ),
                 ),
             ));

             $inputFilter->add(array(
                'name' => 'location_code',
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

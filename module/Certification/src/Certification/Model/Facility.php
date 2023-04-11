<?php
namespace Certification\Model;
 use Laminas\InputFilter\InputFilter;
 use Laminas\InputFilter\InputFilterAwareInterface;
 use Laminas\InputFilter\InputFilterInterface;

class Facility {

    public $id;
    public $district;
    public $facility_name;
    public $facility_address;
    protected $inputFilter;

    public function exchangeArray($data) {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->district = (!empty($data['district'])) ? $data['district'] : null;
        $this->facility_name = (!empty($data['facility_name'])) ? $data['facility_name'] : null;
        $this->contact_person_name = (!empty($data['contact_person_name'])) ? $data['contact_person_name'] : null;
        $this->phone_no = (!empty($data['phone_no'])) ? $data['phone_no'] : null;
        $this->email_id = (!empty($data['email_id'])) ? $data['email_id'] : null;
        $this->facility_address = (!empty($data['facility_address'])) ? $data['facility_address'] : null;
        $this->latitude = (!empty($data['latitude'])) ? $data['latitude'] : null;
        $this->longitude = (!empty($data['longitude'])) ? $data['longitude'] : null;
        
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
                 'name'     => 'id',
                 'required' => false,
                 'filters'  => array(
                     array('name' => 'Int'),
                 ),
             ));

              $inputFilter->add(array(
                 'name'     => 'district',
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
                 'name'     => 'facility_name',
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
                 'name'     => 'phone_no',
                 'required' => false,
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
                             'max'      => 15,
                         ),
                     ),
                 ),
             ));

             $inputFilter->add(array(
                 'name'     => 'facility_address',
                 'required' => false,
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

             $this->inputFilter = $inputFilter;
         }

         return $this->inputFilter;
     }


}

<?php
namespace Certification\Form;

 use Laminas\Db\Adapter\AdapterInterface;
 use Laminas\Form\Form;

 class CertificationMailForm extends Form
 {
     protected $adapter;
     
     public function __construct(AdapterInterface $dbAdapter){
        $this->adapter = $dbAdapter;
         // we want to ignore the name passed
         parent::__construct('certification-mail');

         $this->add(array(
             'name' => 'mail_id',
             'type' => 'Hidden',
         ));
         
         $this->add(array(
             'name' => 'type',
             'type'=> 'Laminas\Form\Element\Select',
             'options' => array(
                'label' => 'Type Of Email',
//                'empty_option' => 'Please Choose an Option',
                'value_options' => array(
                    '1'=>'Send Certificate',
                    '2'=>'Send Reminder'
                )
             ),
         ));
         $this->add(array(
             'type'=> 'Laminas\Form\Element\Select',
             'name' => 'provider',
             'required' => true,
             'options' => array(
                'label' => 'Tester',
                'empty_option' => 'Select Tester',
                'value_options' => $this->getAllCertifiedUser(),
                'disable_inarray_validator' => true
             ),
         ));
         $this->add(array(
                'type' => 'Laminas\Form\Element\MultiCheckbox',
                'name' => 'add_to',
                'options' => array(
                    'label' => 'Add "To" emails',
                    'value_options' => array(
                        '2' => ' Add District Coordinator Email',
                        '3' => ' Add Facility Email'
                    ),
                )
         ));
         $this->add(array(
             'name' => 'to_email',
             'type' => 'Laminas\Form\Element\Email',
             'options' => array(
                 'label' => 'To ',
             ),
         ));
         $this->add(array(
             'name' => 'cc',
             'type' => 'Laminas\Form\Element\Email',
             'options' => array(
                 'label' => 'cc',
             ),
         ));
         $this->add(array(
             'name' => 'bcc',
             'type' => 'Laminas\Form\Element\Email',
             'options' => array(
                 'label' => 'bcc',
             ),
         ));
         $this->add(array(
             'name' => 'subject',
             'type' => 'textarea',
             'options' => array(
                 'label' => 'Subject'
                 ),
         ));
         
         $this->add(array(
             'name' => 'message',
             'type' => 'textarea',
             'options' => array(
                 'label' => 'Message',
             ),
        
         ));
        
        $this->add(array(
             'name' => 'submit',
             'type' => 'button',
             'attributes' => array(
                 'value' => 'Go',
                 'id' => 'submitbutton',
             ),
         ));
     }
     
     public function getAllCertifiedUser() {
        $dbAdapter = $this->adapter;
        $sql = 'SELECT p.id,p.first_name,p.last_name,p.middle_name,p.professional_reg_no,p.certification_id,p.email,p.test_site_in_charge_email,p.facility_in_charge_email,c.id as certid, c.date_certificate_issued,c.date_end_validity FROM provider as p INNER JOIN examination as e ON e.provider=p.id INNER JOIN certification as c ON c.examination=e.id WHERE c.date_end_validity >= CURDATE() AND c.final_decision = "Certified" ORDER by p.last_name asc';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        $selectData = [];
        foreach ($result as $res) {
            $providerRegID = (trim($res['professional_reg_no'])!= '')?'('.$res['professional_reg_no'].')':'';
            $selectData[$res['id'].'##'.$res['email'].'##'.$res['test_site_in_charge_email'].'##'.$res['facility_in_charge_email'].'##'.$res['last_name'].'##'.$res['first_name'].'##'.$res['middle_name'].'##'.$res['professional_reg_no'].'##'.$res['certification_id'].'##'.$res['date_certificate_issued'].'##'.$res['date_end_validity'].'##'.$res['certid']] = ucwords($res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name']).$providerRegID.' - '.$res['certification_id'];
        }
       return $selectData;
     }
 }

<?php

namespace Certification\Form;

use Laminas\Form\Form;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Adapter;

class PracticalExamForm extends Form {

    protected $adapter;

    public function __construct(AdapterInterface $dbAdapter) {

        $this->adapter = $dbAdapter;

        parent::__construct("practical_exam");
        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'practice_exam_id',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'exam_type',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Number of Attempts',
                 'disable_inarray_validator' => true, 
//                
            ),
        ));

        $this->add(array(
            'name' => 'exam_admin',
            'type' => 'text',
            'options' => array(
                'label' => 'Exam Administrator',
                ),
            ));

        $this->add(array(
            'name' => 'provider_id',
            'type' => 'Laminas\Form\Element\Select',
            'options' => array(
                'label' => 'Tester',
                'empty_option' => 'Please choose a Tester',
                'value_options' => $this->getListProvider()
            ),
        ));
        $this->add(array(
            'name' => 'direct_observation_score',
            'type' => 'text',
            'options' => array(
                'label' => 'Direct Observation Score ( % )',
            ),
            
        ));
        
        $this->add(array(
            'name' => 'Sample_testing_score',
            'type' => 'text',
            'options' => array(
                'label' => 'Sample Testing Score  ( % )',
            ),
            
        ));
        $this->add(array(
            'type' => 'text',
            'name' => 'date',
            'attributes' => [
                'id' => 'date',
                'type' => 'text'
            ],
            'options' => array(
                'label' => 'Date'
            )
        ));

        $this->add(array(
            'name' => 'training_id',
            'type' => 'text',
            'options' => array(
                'label' => 'Training',
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


        $this->add(array(
            'name' => 'written',
            'type' => 'hidden',
        ));
    }

    public function getListProvider() {
        $dbAdapter = $this->adapter;
        //$sql = 'SELECT id, certification_id,last_name,first_name,middle_name,professional_reg_no,phone,email FROM provider order by last_name asc ';
        $sql ='SELECT 
                id,
                certification_id,
                last_name,
                first_name,
                middle_name,
                professional_reg_no,
                email,
                phone,
                district,
                region,
                l_d_r.location_name as region_name,
                l_d_d.location_name as district_name
                FROM provider,location_details as l_d_r, location_details as l_d_d
                WHERE provider.region= l_d_r.location_id and provider.district=l_d_d.location_id
                order by last_name asc';
        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $name = $res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name'];
            if(trim($res['professional_reg_no']) != ""){
                $name.=  ' ('.$res['professional_reg_no'].')';
            }
            if(trim($res['phone']) != ""){
                $name.=  ' - '.$res['phone'];
            }
            
            if(trim($res['email']) != ""){
                $name.=  ' - '.$res['email'];
            }

            if(trim($res['region_name']) != ""){
                $name.=  ' - '.$res['region_name'];
            }

            if(trim($res['district_name']) != ""){
                $name.=  ' - '.$res['district_name'];
            }
            
            $selectData[$res['id']] = $name;
        }
        return $selectData;
    }
    public function getListTraining() {
        $dbAdapter = $this->adapter;
        $sql ='SELECT 
                training_id,
                Provider_id,
                type_of_competency,
                last_training_date,
                type_of_training,
                length_of_training,
                facilitator,
                training_certificate,
                date_certificate_issued,
                Comments,
                last_name,
                first_name,
                middle_name, 
                professional_reg_no, 
                certification_id, 
                certification_reg_no,
                training_organization_name,
                type_organization
                FROM training,provider,training_organization  WHERE training.Provider_id= provider.id and training_organization.training_organization_id=training.training_organization_id
                order by training_id asc';

        $statement = $dbAdapter->query($sql);
        $result = $statement->execute();
        
        $selectData = array();
        
        
        foreach ($result as $res) {
            
            $name = $res['type_of_competency'];
            
            if(trim($res['training_organization_name']) != ""){
                $name.=  ' - '.$res['training_organization_name'];
            }
            
            if(trim($res['type_organization']) != ""){
                $name.=  ' - '.$res['type_organization'];
            }
            
            $selectData[$res['training_id']] = $name;
        }

        return $selectData;
    }
    
}

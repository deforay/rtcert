<?php

namespace Certification\Controller;

use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;
use Certification\Model\CertificationMail;
use Certification\Form\CertificationMailForm;
use Zend\View\Model\ViewModel;

class CertificationMailController extends AbstractActionController {

    protected $mailTable;

    public function getCertificationMailTable() {
        if (!$this->mailTable) {
            $sm = $this->getServiceLocator();
            $this->mailTable = $sm->get('Certification\Model\CertificationMailTable');
        }
        return $this->mailTable;
    }

    public function indexAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $id = '';
        $email = '';
        $test_site_in_charge_email = '';
        $facility_in_charge_email = '';
        $professional_reg_no = '';
        $certification_id = '';
        $key2 = '';
        $provider_id = '';
        $provider_name = '';
        $date_certificate_issued = '';
        $due_date = '';
        $header_text = '';
        if($this->params()->fromQuery(base64_encode('id'), null)){
            $id = base64_decode($this->params()->fromQuery(base64_encode('id'), null));
        }
        if($this->params()->fromQuery(base64_encode('email'), null)){
            $email = base64_decode($this->params()->fromQuery(base64_encode('email'), null));
        }
        if($this->params()->fromQuery(base64_encode('test_site_in_charge_email'), null)){
            $test_site_in_charge_email = base64_decode($this->params()->fromQuery(base64_encode('test_site_in_charge_email')), null);
        }
        if($this->params()->fromQuery(base64_encode('facility_in_charge_email'), null)){
            $facility_in_charge_email = base64_decode($this->params()->fromQuery(base64_encode('facility_in_charge_email')), null);
        }
        if($this->params()->fromQuery(base64_encode('professional_reg_no'), null)){
            $professional_reg_no = base64_decode($this->params()->fromQuery(base64_encode('professional_reg_no')));
        }
        if($this->params()->fromQuery(base64_encode('certification_id'), null)){
            $certification_id = base64_decode($this->params()->fromQuery(base64_encode('certification_id')));
        }
        if($this->params()->fromQuery(base64_encode('key2'), null)){
            $key2 = base64_decode($this->params()->fromQuery(base64_encode('key2'),null));
        }
        if($this->params()->fromQuery(base64_encode('provider_id'), null)){
           $provider_id = (int) base64_decode($this->params()->fromQuery(base64_encode('provider_id'), NULL));
        }
        if($this->params()->fromQuery(base64_encode('provider_name'), null)){
            $provider_name = base64_decode($this->params()->fromQuery(base64_encode('provider_name'), null));
        }
        if($this->params()->fromQuery(base64_encode('date_certificate_issued'), null)){
            $date_certificate_issued = base64_decode($this->params()->fromQuery(base64_encode('date_certificate_issued'), null));
        }
        if($this->params()->fromQuery(base64_encode('date_end_validity'), null)){
            $due_date = base64_decode($this->params()->fromQuery(base64_encode('date_end_validity'), null));
        }
        
        $header_text = $this->getCertificationMailTable()->SelectTexteHeader();
//        die($provider_id);
        $commonService= $this->getServiceLocator()->get('CommonService');
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new CertificationMailForm($dbAdapter);
        //$form->get('submit')->setValue('SEND EMAIL');
        $list = $this->getCertificationMailTable()->fetchAll();
        $request = $this->getRequest();
        $container = new Container('alert');
        
        if ($request->isPost()) {
            $save_mail = new CertificationMail();
            $form->setInputFilter($save_mail->getInputFilter());
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $save_mail->exchangeArray($form->getData());
                $parmas = array();
                $parmas['provider'] = $this->getRequest()->getpost('provider', null);
                $parmas['type'] = $this->getRequest()->getpost('type', null);
                $parmas['to_email'] = $this->getRequest()->getpost('to_email', null);
                $parmas['cc'] = $this->getRequest()->getpost('cc', null);
                $parmas['bcc'] = $this->getRequest()->getpost('bcc', null);
                $parmas['subject'] = $this->getRequest()->getpost('subject', null);
                $parmas['message'] = $this->getRequest()->getpost('message', null);
                $parmas['attachedfile'] = $this->getRequest()->getpost('attachedfile', null);
                $hasSent = $commonService->sendCertificateMail($parmas);
                if($hasSent){
                    $testerArray = explode("##",$this->getRequest()->getpost('provider', null));
                    $provider_id = $testerArray[0];
                    $certid = $testerArray[11];
                    $due_date = $testerArray[10];
                    
                    if ($parmas['type'] == 1 && !empty($certid)) {
                        $this->getCertificationMailTable()->dateCertificateSent($certid);
                    } elseif ($parmas['type'] == 2) {
                        $reminder_type = 'Email';
                        $reminder_sent_to = $this->getRequest()->getpost('type_recipient', null);
                        $name_reminder = $this->getRequest()->getpost('name_recipient', null);
                        $date_reminder_sent = date('Y-m-d');
                        if (!empty($certid)) {
                            $this->getCertificationMailTable()->insertRecertification($due_date, $provider_id, $reminder_type, $reminder_sent_to, $name_reminder, $date_reminder_sent);
                            $this->getCertificationMailTable()->reminderSent($certid);
                        }
                    }
                    $this->getCertificationMailTable()->saveCertificationMail($save_mail);
                    $container->alertMsg = 'Mail sent successfully';
                    return $this->redirect()->toRoute('certification-mail');
                }else{
                    return array('form' => $form, 'list' => $list);
                }
            }
        }

        return array(
            'form' => $form,
            'id'=>$id,
            'email' => $email,
            'list' => $list,
            'date_certificate_issued'=> $date_certificate_issued,
            'due_date' => $due_date,
            'provider_id'=>$provider_id,
            'provider_name' => $provider_name,
            'professional_reg_no'=>$professional_reg_no,
            'certification_id'=>$certification_id,
            'test_site_in_charge_email'=>$test_site_in_charge_email,
            'facility_in_charge_email' => $facility_in_charge_email,
            'key2' => $key2,
            'header_text' => $header_text
        );
    }
    
    public function generateCertificatePdfAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('params' =>$params));
            $viewModel->setTerminal(true);
           return $viewModel;
        }
    }

}

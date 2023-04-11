<?php

namespace Certification\Controller;

use Laminas\Session\Container;
use Laminas\Mvc\Controller\AbstractActionController;
use Certification\Model\CertificationMail;
use Laminas\View\Model\ViewModel;

class CertificationMailController extends AbstractActionController
{

    public \Certification\Model\CertificationMailTable $certificationMailTable;
    public \Certification\Form\CertificationMailForm $certificationMailForm;
    public \Application\Service\CommonService $commonService;

    public function __construct($commonService, $certificationMailTable, $certificationMailForm)
    {
        $this->commonService = $commonService;
        $this->certificationMailTable = $certificationMailTable;
        $this->certificationMailForm = $certificationMailForm;
    }

    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
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
        if ($this->params()->fromQuery(base64_encode('id'), null)) {
            $id = base64_decode($this->params()->fromQuery(base64_encode('id'), null));
        }
        if ($this->params()->fromQuery(base64_encode('email'), null)) {
            $email = base64_decode($this->params()->fromQuery(base64_encode('email'), null));
        }
        if ($this->params()->fromQuery(base64_encode('test_site_in_charge_email'), null)) {
            $test_site_in_charge_email = base64_decode($this->params()->fromQuery(base64_encode('test_site_in_charge_email')), null);
        }
        if ($this->params()->fromQuery(base64_encode('facility_in_charge_email'), null)) {
            $facility_in_charge_email = base64_decode($this->params()->fromQuery(base64_encode('facility_in_charge_email')), null);
        }
        if ($this->params()->fromQuery(base64_encode('professional_reg_no'), null)) {
            $professional_reg_no = base64_decode($this->params()->fromQuery(base64_encode('professional_reg_no')));
        }
        if ($this->params()->fromQuery(base64_encode('certification_id'), null)) {
            $certification_id = base64_decode($this->params()->fromQuery(base64_encode('certification_id')));
        }
        if ($this->params()->fromQuery(base64_encode('key2'), null)) {
            $key2 = base64_decode($this->params()->fromQuery(base64_encode('key2'), null));
        }
        if ($this->params()->fromQuery(base64_encode('provider_id'), null)) {
            $provider_id = (int) base64_decode($this->params()->fromQuery(base64_encode('provider_id'), NULL));
        }
        if ($this->params()->fromQuery(base64_encode('provider_name'), null)) {
            $provider_name = base64_decode($this->params()->fromQuery(base64_encode('provider_name'), null));
        }
        if ($this->params()->fromQuery(base64_encode('date_certificate_issued'), null)) {
            $date_certificate_issued = base64_decode($this->params()->fromQuery(base64_encode('date_certificate_issued'), null));
        }
        if ($this->params()->fromQuery(base64_encode('date_end_validity'), null)) {
            $due_date = base64_decode($this->params()->fromQuery(base64_encode('date_end_validity'), null));
        }

        $header_text = $this->certificationMailTable->SelectTexteHeader();

        //$this->certificationMailForm->get('submit')->setValue('SEND EMAIL');
        $list = $this->certificationMailTable->fetchAll();
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $container = new Container('alert');

        if ($request->isPost()) {
            $save_mail = new CertificationMail();
            $this->certificationMailForm->setInputFilter($save_mail->getInputFilter());
            $this->certificationMailForm->setData($request->getPost());
            if ($this->certificationMailForm->isValid()) {
                $save_mail->exchangeArray($this->certificationMailForm->getData());
                $parmas = array();
                $parmas['provider'] = $this->getRequest()->getpost('provider', null);
                $parmas['type'] = $this->getRequest()->getpost('type', null);
                $parmas['to_email'] = $this->getRequest()->getpost('to_email', null);
                $parmas['cc'] = $this->getRequest()->getpost('cc', null);
                $parmas['bcc'] = $this->getRequest()->getpost('bcc', null);
                $parmas['subject'] = $this->getRequest()->getpost('subject', null);
                $parmas['message'] = $this->getRequest()->getpost('message', null);
                $parmas['attachedfile'] = $this->getRequest()->getpost('attachedfile', null);
                $hasSent = $this->commonService->sendCertificateMail($parmas);
                if ($hasSent) {
                    $testerArray = explode("##", $this->getRequest()->getpost('provider', null));
                    $provider_id = $testerArray[0];
                    $certid = $testerArray[11];
                    $due_date = $testerArray[10];

                    if ($parmas['type'] == 1 && !empty($certid)) {
                        $this->certificationMailTable->dateCertificateSent($certid);
                    } elseif ($parmas['type'] == 2) {
                        $reminder_type = 'Email';
                        $reminder_sent_to = $this->getRequest()->getpost('type_recipient', null);
                        $name_reminder = $this->getRequest()->getpost('name_recipient', null);
                        $date_reminder_sent = date('Y-m-d');
                        if (!empty($certid)) {
                            $this->certificationMailTable->insertRecertification($due_date, $provider_id, $reminder_type, $reminder_sent_to, $name_reminder, $date_reminder_sent);
                            $this->certificationMailTable->reminderSent($certid);
                        }
                    }
                    $this->certificationMailTable->saveCertificationMail($save_mail);
                    $container->alertMsg = 'Mail sent successfully';
                    return $this->redirect()->toRoute('certification-mail');
                } else {
                    return array('form' => $this->certificationMailForm, 'list' => $list);
                }
            }
        }

        return array(
            'form' => $this->certificationMailForm,
            'id' => $id,
            'email' => $email,
            'list' => $list,
            'date_certificate_issued' => $date_certificate_issued,
            'due_date' => $due_date,
            'provider_id' => $provider_id,
            'provider_name' => $provider_name,
            'professional_reg_no' => $professional_reg_no,
            'certification_id' => $certification_id,
            'test_site_in_charge_email' => $test_site_in_charge_email,
            'facility_in_charge_email' => $facility_in_charge_email,
            'key2' => $key2,
            'header_text' => $header_text
        );
    }

    public function generateCertificatePdfAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('params' => $params));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}

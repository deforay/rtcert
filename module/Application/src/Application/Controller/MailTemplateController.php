<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MailTemplateController extends AbstractActionController{

    public function indexAction(){
        $request = $this->getRequest();
        $mailServices =$this->getServiceLocator()->get('MailService');
        if($request->isPost()){
            $param = $request->getPost();
            $mailServices->updateMailTemplateDetails($param);
            return $this->redirect()->toRoute('mail-template', array('action' => 'index','id' => $param['mailPurpose']));   
        }
        else{
            $mailHead='';
            $mailPurpose=$this->params()->fromRoute('id');
            if($mailPurpose=='userActivation'){
                $mailHead='User Activation Mail Template';
            }
            $mailTemplate = $mailServices->getMailTemplate($mailPurpose);
            return new ViewModel(array(
                'mailhead'=>$mailHead,
                'mailPurpose'=>$mailPurpose,
                'mailtemplate' => $mailTemplate
            ));
        }
    }


}


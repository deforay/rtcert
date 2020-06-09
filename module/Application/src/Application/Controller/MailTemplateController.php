<?php
namespace Application\Controller;

use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class MailTemplateController extends AbstractActionController{

    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $mailServices = $this->getServiceLocator()->get('MailService');
            $result = $mailServices->getMailServiceListInGrid($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function addAction(){
        $request = $this->getRequest();
        $mailServices =$this->getServiceLocator()->get('MailService');
        if($request->isPost()){
            $param = $request->getPost();
            $mailServices->saveMailTemplateDetails($param);
            return $this->redirect()->toRoute('mail-template'); 
        }
    }
    
    public function editAction(){
        $request = $this->getRequest();
        $mailServices =$this->getServiceLocator()->get('MailService');
        if($request->isPost()){
            $param = $request->getPost();
            $mailServices->saveMailTemplateDetails($param);
            return $this->redirect()->toRoute('mail-template');
        } else{
            $id=base64_decode($this->params()->fromRoute('id'));
            return new ViewModel(array('result' => $mailServices->getMailTemplate($id)));
        }
    }


}


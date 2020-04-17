<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class ConfigController extends AbstractActionController
{
    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService = $this->getServiceLocator()->get('CommonService');
            $result = $commonService->getAllConfig($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $commonService = $this->getServiceLocator()->get('CommonService');
        $result = $commonService->getHeaderText();
        return new ViewModel(array(
            'header_text'               => $result['textHeader'],
            'header_text_font_size'     => $result['HeaderTextFont']
        ));
    }
    
    public function editGlobalAction(){
        $commonService = $this->getServiceLocator()->get('CommonService');
       $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService->updateConfig($params);
            return $this->redirect()->toRoute('config');
        }else{
            $configResult=$commonService->getGlobalConfigDetails();
            return new ViewModel(array(
                'config' => $configResult,
            ));
        }
    }

}

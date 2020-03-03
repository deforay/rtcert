<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class TestSectionController extends AbstractActionController{

    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $testSectionService = $this->getServiceLocator()->get('TestSectionService');
            $result = $testSectionService->getTestSectionList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function addAction(){
        $testSectionService = $this->getServiceLocator()->get('TestSectionService');
        if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $testSectionService->addTestSectionData($params);
            return $this->_redirect()->toRoute('test-section');
        }
    }
    
    public function editAction(){
        $testSectionService = $this->getServiceLocator()->get('TestSectionService');
        if($this->getRequest()->isPost()){
            $param=$this->getRequest()->getPost();
            $result=$testSectionService->updateTestSectionDetails($param);
            return $this->redirect()->toRoute('test-section');
        }
        else{
            $testSectionId=base64_decode($this->params()->fromRoute('id'));
            $testSectionResult = $testSectionService->getTestSectionById($testSectionId);
            return new ViewModel(array(
                'testSectionResult' => $testSectionResult,
            ));
        }
    }
}


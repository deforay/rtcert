<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class TestQuestionController extends AbstractActionController{

    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $questionService = $this->getServiceLocator()->get('QuestionService');
            $result = $questionService->getQuestionList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction(){
        $questionService = $this->getServiceLocator()->get('QuestionService');
        if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $questionService->addQuestionData($params);
            return $this->_redirect()->toRoute('test-question');
        }
        else {
            $testSectionService = $this->getServiceLocator()->get('TestSectionService');
            $sectionResult = $testSectionService->getTestSectionAllList();
            return new ViewModel(array(
                'sectionResult' => $sectionResult,
            ));
        }
    }

    public function editAction(){
        $questionService = $this->getServiceLocator()->get('QuestionService');
        if($this->getRequest()->isPost()){
            $param=$this->getRequest()->getPost();
            $questionService->updateQuestionDetails($param);
            return $this->redirect()->toRoute('test-question');
        }
        else{
            $questionId=base64_decode($this->params()->fromRoute('id'));
            $testSectionService = $this->getServiceLocator()->get('TestSectionService');
            $sectionResult = $testSectionService->getTestSectionAllList();
            $questionResult = $questionService->getQuestionsListById($questionId);
            $optionResult = $questionService->getOptionListById($questionId);
            return new ViewModel(array(
                'sectionResult' => $sectionResult,
                'questionResult' => $questionResult,
                'optionResult' => $optionResult,
            ));
        }
    }

    public function importExcelAction(){
        $questionService = $this->getServiceLocator()->get('QuestionService');
        if($this->getRequest()->isPost()){
            $params=$this->getRequest()->getPost();
            $result = $questionService->uploadTestQuestion($params);
            return new ViewModel(array(
                'result' => $result
            ));
            return $this->_redirect()->toRoute('test-question');
        }
        else {
            $testSectionService = $this->getServiceLocator()->get('TestSectionService');
            $sectionResult = $testSectionService->getTestSectionAllList();
            return new ViewModel(array(
                'sectionResult' => $sectionResult,
            ));
        }
    }
}

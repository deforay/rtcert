<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class TestQuestionController extends AbstractActionController
{

    public \Application\Service\TestSectionService $testSectionService;
    public \Application\Service\QuestionService $questionService;

    public function __construct($testSectionService, $questionService)
    {
        $this->testSectionService = $testSectionService;
        $this->questionService = $questionService;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->questionService->getQuestionList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $this->questionService->addQuestionData($params);
            return $this->redirect()->toRoute('test-question');
        } else {
            $sectionResult = $this->testSectionService->getTestSectionAllList();
            return new ViewModel(array(
                'sectionResult' => $sectionResult,
            ));
        }
    }

    public function editAction()
    {
        if ($this->getRequest()->isPost()) {
            $param = $this->getRequest()->getPost();
            $this->questionService->updateQuestionDetails($param);
            return $this->redirect()->toRoute('test-question');
        } else {
            $questionId = base64_decode($this->params()->fromRoute('id'));
            $sectionResult = $this->testSectionService->getTestSectionAllList();
            $questionResult = $this->questionService->getQuestionsListById($questionId);
            $optionResult = $this->questionService->getOptionListById($questionId);
            return new ViewModel(array(
                'sectionResult' => $sectionResult,
                'questionResult' => $questionResult,
                'optionResult' => $optionResult,
            ));
        }
    }

    public function importExcelAction()
    {
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $result = $this->questionService->uploadTestQuestion($params);
            return new ViewModel(array(
                'result' => $result
            ));
            return $this->_redirect()->toRoute('test-question');
        } else {
            $sectionResult = $this->testSectionService->getTestSectionAllList();
            return new ViewModel(array(
                'sectionResult' => $sectionResult,
            ));
        }
    }
}

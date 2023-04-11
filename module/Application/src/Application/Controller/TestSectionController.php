<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class TestSectionController extends AbstractActionController
{
    public \Application\Service\TestSectionService $testSectionService;

    public function __construct($testSectionService)
    {
        $this->testSectionService = $testSectionService;
    }
    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->testSectionService->getTestSectionList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->testSectionService->addTestSectionData($params);
            return $this->_redirect()->toRoute('test-section');
        }
    }

    public function editAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $param = $request->getPost();
            $result = $this->testSectionService->updateTestSectionDetails($param);
            return $this->redirect()->toRoute('test-section');
        } else {
            $testSectionId = base64_decode($this->params()->fromRoute('id'));
            $testSectionResult = $this->testSectionService->getTestSectionById($testSectionId);
            return new ViewModel(array(
                'testSectionResult' => $testSectionResult,
            ));
        }
    }
}

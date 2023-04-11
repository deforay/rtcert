<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class TestConfigController extends AbstractActionController
{


    public \Application\Service\CommonService $commonService;
    public \Application\Service\TestSectionService $testSectionService;

    public function __construct($commonService, $testSectionService)
    {
        $this->commonService = $commonService;
        $this->testSectionService = $testSectionService;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->commonService->getTestConfig($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function editAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->commonService->updateTestConfig($params);
            return $this->redirect()->toRoute('test-config');
        } else {
            $configResult = $this->commonService->getTestConfigEditDetails();
            $sectionResult = $this->testSectionService->getTestSectionAllList();
            return new ViewModel(array(
                'config' => $configResult,
                'sectionResult' => $sectionResult
            ));
        }
    }
}

<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class TestConfigController extends AbstractActionController
{
    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService = $this->getServiceLocator()->get('CommonService');
            $result = $commonService->getTestConfig($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function editAction()
    {
        $commonService = $this->getServiceLocator()->get('CommonService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService->updateTestConfig($params);
            return $this->redirect()->toRoute('test-config');
        } else {
            $configResult = $commonService->getTestConfigEditDetails();
            $testSectionService = $this->getServiceLocator()->get('TestSectionService');
            $sectionResult = $testSectionService->getTestSectionAllList();
            return new ViewModel(array(
                'config' => $configResult,
                'sectionResult' => $sectionResult
            ));
        }
    }
}

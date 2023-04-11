<?php

namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class TestController extends AbstractActionController
{


    public \Application\Service\TestService $testService;
    public \Application\Service\CommonService $commonService;
    public \Application\Service\QuestionService $questionService;
    public \Certification\Model\ProviderTable $providerTable;

    public function __construct($commonService, $testService, $questionService, $providerTable)
    {
        $this->testService = $testService;
        $this->commonService = $commonService;
        $this->questionService = $questionService;
        $this->providerTable = $providerTable;
    }

    public function indexAction()
    {
        $container = new Container('alert');
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $this->testService->addPreTestData($params);
            return $this->redirect()->toUrl('home');
        } else {
            $questionResult = $this->questionService->getQuestionAllList();
            $redirect = $this->testService->getPostTestCompleteDetails();

            if (isset($questionResult['posttest-page'])) {
                if (((isset($redirect['testSatatus']['pre_test_status']) && trim($redirect['testSatatus']['pre_test_status']) != "") || ($redirect['testSatatus']['pre_test_status'] == 'completed')) && (($questionResult['testResultStatus']['testStatus']['post_test_status'] == NULL) || ($questionResult['testResultStatus']['testStatus']['post_test_status'] == ""))) {
                    $container->alertMsg = "Your test was completed already.";
                    return $this->redirect()->toUrl('/test/result');
                } else if ((isset($redirect['testSatatus']['pre_test_status']) && trim($redirect['testSatatus']['pre_test_status']) != "") || ($redirect['testSatatus']['pre_test_status'] == 'completed')) {
                    $container->alertMsg = "Your test was completed already. Retest not activated.";
                    return $this->redirect()->toUrl('/test/result');
                }
            } else if (isset($questionResult['home-page'])) {
                $container->alertMsg = "Your test not started we'll announce to you once activated.";
                return $this->redirect()->toUrl('/');
            }
            $this->providerTable->updateTestMailSendStatus();
            return new ViewModel(array(
                'questionResult' => $questionResult
            ));
        }
    }

    public function questionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->testService->addPreTestData($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))->setTerminal(true);
            return $viewModel;
        }
    }

    public function resultAction()
    {
        $preResult = $this->testService->getPreResultDetails();
        if ($preResult['test_mail_send'] != 'yes') {
            $this->providerTable->updateTestMailSendStatus($preResult['id']);
        }
        $redirect = $this->testService->getPostTestCompleteDetails();
        $configResult = $this->commonService->getTestConfigDetails();
        if ((isset($redirect['testSatatus']['pre_test_status']) && trim($redirect['testSatatus']['pre_test_status']) != "") || ($redirect['testSatatus']['pre_test_status'] == 'completed')) {
            return new ViewModel(array(
                'preResult' => $preResult,
                'configResult' => $configResult
            ));
        } else {
            return $this->redirect()->toUrl('/');
        }
    }

    public function introAction()
    {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toUrl("/provider/login");
        }
        return new ViewModel(array('name' => $this->commonService->getGlobalValue('country-name')));
    }
}

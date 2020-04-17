<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class TestController extends AbstractActionController{

    public function indexAction(){
        $container = new Container('alert');
        if($this->getRequest()->isPost()){
            $params = $this->getRequest()->getPost();
            $testService = $this->getServiceLocator()->get('TestService');
            $testService->addPreTestData($params);
            return $this->redirect()->toUrl('home');
        }else{
            $questionService = $this->getServiceLocator()->get('QuestionService');
            $questionResult = $questionService->getQuestionAllList();
            $testService = $this->getServiceLocator()->get('TestService');
            $redirect = $testService->getPostTestCompleteDetails();
            // \Zend\Debug\Debug::dump($questionResult);die;

            if(isset($questionResult['posttest-page'])){
                if(((isset($redirect['testSatatus']['pre_test_status']) && trim($redirect['testSatatus']['pre_test_status']) != "") || ($redirect['testSatatus']['pre_test_status'] == 'completed')) && (($questionResult['testResultStatus']['testStatus']['post_test_status']==NULL) || ($questionResult['testResultStatus']['testStatus']['post_test_status']== ""))){
                    $container->alertMsg ="Your test was completed already.";
                    return $this->redirect()->toUrl('/test/result');
                }else if((isset($redirect['testSatatus']['pre_test_status']) && trim($redirect['testSatatus']['pre_test_status']) != "") || ($redirect['testSatatus']['pre_test_status'] == 'completed')){
                    $container->alertMsg ="Your test was completed already. Retest not activated.";
                    return $this->redirect()->toUrl('/test/result');
                }
            }else if(isset($questionResult['home-page'])){
                $container->alertMsg ="Your test not started we'll announce to you once activated.";
                return $this->redirect()->toUrl('/');
            }
            return new ViewModel(array(
                'questionResult'=>$questionResult
            ));
        }
    }

    public function questionAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $testService = $this->getServiceLocator()->get('TestService');
            $result = $testService->addPreTestData($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))->setTerminal(true);
            return $viewModel;
        }
    }

    public function resultAction(){
        $testService = $this->getServiceLocator()->get('TestService');
        $preResult = $testService->getPreResultDetails();
        $redirect = $testService->getPostTestCompleteDetails();
        $commonService = $this->getServiceLocator()->get('CommonService');
        $configResult = $commonService->getTestConfigDetails();
        if((isset($redirect['testSatatus']['pre_test_status']) && trim($redirect['testSatatus']['pre_test_status']) != "") || ($redirect['testSatatus']['pre_test_status'] == 'completed')){
            return new ViewModel(array(
                'preResult'=>$preResult,
                'configResult'=>$configResult
            ));
        }else{
            return $this->redirect()->toUrl('/'); 
        }
    }

    public function introAction(){
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toUrl("/provider/login");
        }
        $commonService = $this->getServiceLocator()->get('CommonService');
        return new ViewModel(array('name'=>$commonService->getGlobalValue('country-name')));
    }
}
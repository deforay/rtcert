<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class PrintTestPdfController extends AbstractActionController{

    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            $result = $printTestPdfService->getprintTestPdfGrid($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            return new ViewModel(array(
                'ptpSelectResult' => $printTestPdfService->getAllprintTestPdf()
            ));
        }
    }
    
    public function viewPdfQuestionAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            $result = $printTestPdfService->getPtpDetailsInGrid($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $ptpId=base64_decode($this->params()->fromRoute('id'));
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            $ptpResult = $printTestPdfService->getPtpDetailsById($ptpId);
            return new ViewModel(array(
                'ptpResult'         => $ptpResult,
                'ptpSelectResult'   => $printTestPdfService->getAllprintTestPdf(),
                'ptpId'             => $this->params()->fromRoute('id')
            ));
        }
    }

    public function addAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $params = $request->getPost();
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            $printTestPdfService->addPrintTestPdfData($params);
            return $this->_redirect()->toRoute('print-test-pdf');
        }
    }

    public function printPdfQuestionAction(){
        $ptpId=base64_decode($this->params()->fromRoute('id'));
        $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
        $result = $printTestPdfService->getPdfDetailsById($ptpId);
        if($result['ptpDetails']['title'] != ''){
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result,'ptpId' => explode('##',$ptpId)));
            $viewModel->setTerminal(true);
            return $viewModel;
        }else{
            return $this->_redirect()->toRoute('print-test-pdf');
        }
    }

    public function editAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()){
            $params = $request->getPost();
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            $result = $printTestPdfService->savePdfTitle($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $layout = $this->layout();
            $layout->setTemplate('layout/modal');
            $ptpId=base64_decode($this->params()->fromRoute('id'));
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            return new ViewModel(array(
                'result' => $printTestPdfService->getPrintTestPdfDetailsById($ptpId)
            ));
        }
    }

    public function changeStatusAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            $params = $request->getPost();
            $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $printTestPdfService->changeStatus($params)));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function answerKeyOneAction(){
        $ptpId=base64_decode($this->params()->fromRoute('id'));
        $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
        $result = $printTestPdfService->getPdfDetailsById($ptpId,'answer');
        if($result['ptpDetails']['title'] != ''){
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result,'ptpId' => explode('##',$ptpId)));
            $viewModel->setTerminal(true);
            return $viewModel;
        }else{
            return $this->_redirect()->toRoute('print-test-pdf');
        }
    }

    public function answerKeyTwoAction(){
        $ptpId=base64_decode($this->params()->fromRoute('id'));
        $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
        $result = $printTestPdfService->getPdfDetailsById($ptpId,'answer');
        if($result['ptpDetails']['title'] != ''){
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result,'ptpId' => explode('##',$ptpId)));
            $viewModel->setTerminal(true);
            return $viewModel;
        }else{
            return $this->_redirect()->toRoute('print-test-pdf');
        }
    }
    
    public function examinationAction(){
        $ptpId=base64_decode($this->params()->fromRoute('id'));
        $printTestPdfService = $this->getServiceLocator()->get('PrintTestPdfService');
        $result = $printTestPdfService->getPdfDetailsById($ptpId,'examination');
        // \Zend\Debug\Debug::dump($result);die;
        if($result['ptpDetails']['title'] != ''){
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }else{
            return $this->_redirect()->toRoute('print-test-pdf');
        }
    }
}
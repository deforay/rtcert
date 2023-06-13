<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class PrintTestPdfController extends AbstractActionController
{

    public \Application\Service\PrintTestPdfService $printTestPdfService;

    public function __construct($printTestPdfService)
    {
        $this->printTestPdfService = $printTestPdfService;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->printTestPdfService->getprintTestPdfGrid($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            return new ViewModel(array(
                'ptpSelectResult' => $this->printTestPdfService->getAllprintTestPdf()
            ));
        }
    }

    public function viewPdfQuestionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->printTestPdfService->getPtpDetailsInGrid($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            $ptpId = base64_decode($this->params()->fromRoute('id'));
            $ptpResult = $this->printTestPdfService->getPtpDetailsById($ptpId);
            return new ViewModel(array(
                'ptpResult'         => $ptpResult,
                'ptpSelectResult'   => $this->printTestPdfService->getAllprintTestPdf(),
                'ptpId'             => $this->params()->fromRoute('id')
            ));
        }
    }

    public function addAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->printTestPdfService->addPrintTestPdfData($params);
            return $this->redirect()->toRoute('print-test-pdf');
        }
    }

    public function printPdfQuestionAction()
    {
        $ptpId = base64_decode($this->params()->fromRoute('id'));
        $result = $this->printTestPdfService->getPdfDetailsById($ptpId);
        if ($result['ptpDetails']['title'] != '') {
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result, 'ptpId' => explode('##', $ptpId)));
            $viewModel->setTerminal(true);
            return $viewModel;
        } else {
            return $this->redirect()->toRoute('print-test-pdf');
        }
    }

    public function editAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->printTestPdfService->savePdfTitle($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            $layout = $this->layout();
            $layout->setTemplate('layout/modal');
            $ptpId = base64_decode($this->params()->fromRoute('id'));
            return new ViewModel(array(
                'result' => $this->printTestPdfService->getPrintTestPdfDetailsById($ptpId)
            ));
        }
    }

    public function changeStatusAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $this->printTestPdfService->changeStatus($params)));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function answerKeyOneAction()
    {
        $ptpId = base64_decode($this->params()->fromRoute('id'));
        $result = $this->printTestPdfService->getPdfDetailsById($ptpId, 'answer');
        if ($result['ptpDetails']['title'] != '') {
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result, 'ptpId' => explode('##', $ptpId)));
            $viewModel->setTerminal(true);
            return $viewModel;
        } else {
            return $this->redirect()->toRoute('print-test-pdf');
        }
    }

    public function answerKeyTwoAction()
    {
        $ptpId = base64_decode($this->params()->fromRoute('id'));
        $result = $this->printTestPdfService->getPdfDetailsById($ptpId, 'answer');
        if ($result['ptpDetails']['title'] != '') {
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result, 'ptpId' => explode('##', $ptpId)));
            $viewModel->setTerminal(true);
            return $viewModel;
        } else {
            return $this->redirect()->toRoute('print-test-pdf');
        }
    }

    public function examinationAction()
    {
        $ptpId = base64_decode($this->params()->fromRoute('id'));
        $result = $this->printTestPdfService->getPdfDetailsById($ptpId, 'examination');
        if ($result['ptpDetails']['title'] != '') {
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        } else {
            return $this->redirect()->toRoute('print-test-pdf');
        }
    }

    public function exportDataAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $result = $this->printTestPdfService->exportPdfDataDetails();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}

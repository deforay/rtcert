<?php

namespace Application\Controller;

use Laminas\Json\Json;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class MailTemplateController extends AbstractActionController
{

    public \Application\Service\MailService $mailService;

    public function __construct($mailService)
    {
        $this->mailService = $mailService;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->mailService->getMailServiceListInGrid($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $param = $request->getPost();
            $this->mailService->saveMailTemplateDetails($param);
            return $this->redirect()->toRoute('mail-template');
        }
    }

    public function editAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $param = $request->getPost();
            $this->mailService->saveMailTemplateDetails($param);
            return $this->redirect()->toRoute('mail-template');
        } else {
            $id = base64_decode($this->params()->fromRoute('id'));
            return new ViewModel(array('result' => $this->mailService->getMailTemplate($id)));
        }
    }
    public function getMailTemplateAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $param = $request->getPost();
            $mailPurpose =  $param['mail_purpose'];
            $result = $this->mailService->getMailTemplateByMailPurpose($mailPurpose);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}

<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CronController extends AbstractActionController
{

    public function indexAction()
    {
    }

    public function sendMailAction()
    {
        $commonService = $this->getServiceLocator()->get('CommonService');
        $commonService->sendTempMail();
    }

    public function sendTestAlertMailAction()
    {
        $sm = $this->getServiceLocator();
        $providerTable = $sm->get('Certification\Model\ProviderTable');
        $providerTable->sendAutoTestLink();
    }

    public function sendAuditMailAction()
    {
        $commonService = $this->getServiceLocator()->get('CommonService');
        $commonService->sendAuditMail();
    }

    public function dbBackupAction()
    {
        $commonService = $this->getServiceLocator()->get('CommonService');
        $commonService->dbBackup();
    }
}

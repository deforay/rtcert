<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CronController extends AbstractActionController
{

    public function indexAction()
    {
    }

    public function dbBackupAction()
    {
        $commonService = $this->getServiceLocator()->get('CommonService');
        $commonService->dbBackup();
    }
}

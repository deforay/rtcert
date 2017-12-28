<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class CommonController extends AbstractActionController {

    public function indexAction() {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->checkFieldValidations($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);

        return $viewModel;
    }
    public function multipleFieldValidationAction()
    {
        $result = "";
                $request = $this->getRequest();
                if ($request->isPost()) {
                    $params = $request->getPost();
                    //\Zend\Debug\Debug::dump($params);die;
                    $common = $this->getServiceLocator()->get('CommonService');
                    $result = $common->checkMultipleFieldValidations($params);
                }
                $viewModel = new ViewModel();
                $viewModel->setVariables(array('result' => $result))
                        ->setTerminal(true);

                return $viewModel;
    }
    public function auditLocationsAction()
    {
        $odkFormService = $this->getServiceLocator()->get('OdkFormService');
        $request = $this->getRequest();
        if ($request->isGet()) {
            $val = $request->getQuery();
            $spiV3auditRoundNo = $odkFormService->getSpiV3FormAuditNo();
            return new ViewModel(array(
                'id' => $val,
                'spiV3auditRoundNo'=>$spiV3auditRoundNo
            ));
        }
    }

}


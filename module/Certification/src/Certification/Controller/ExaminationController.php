<?php

namespace Certification\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;

class ExaminationController extends AbstractActionController {

    protected $examinationTable;

    public function getExaminationTable() {
        if (!$this->examinationTable) {
            $sm = $this->getServiceLocator();
            $this->examinationTable = $sm->get('Certification\Model\ExaminationTable');
        }
        return $this->examinationTable;
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getExaminationTable()->fetchAll($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        return new ViewModel(array(
           'recommended' => $this->getExaminationTable()->fetchAllRecommended(),
           'approved' => $this->getExaminationTable()->fetchAllApproved()
        ));
    }

}

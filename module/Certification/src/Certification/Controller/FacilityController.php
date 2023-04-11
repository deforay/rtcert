<?php

namespace Certification\Controller;

use Laminas\Session\Container;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Certification\Model\Facility;

class FacilityController extends AbstractActionController
{

    public \Certification\Model\FacilityTable $facilityTable;
    public \Application\Service\CommonService $commonService;
    public \Certification\Form\FacilityForm $facilityForm;

    public function __construct($commonService, $facilityTable, $facilityForm)
    {
        $this->commonService = $commonService;
        $this->facilityTable = $facilityTable;
        $this->facilityForm = $facilityForm;
    }

    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        
        $this->facilityForm->get('submit')->setValue('Submit');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $facility = new Facility();
            $this->facilityForm->setInputFilter($facility->getInputFilter());
            $this->facilityForm->setData($request->getPost());
            if ($this->facilityForm->isValid()) {
                $facility->exchangeArray($this->facilityForm->getData());
                $this->facilityTable->saveFacility($facility);
                $container = new Container('alert');
                $labelVal = $this->commonService->getGlobalValue('facilities');
                $facilityLabel = (isset($labelVal) && trim($labelVal) != '') ? ucwords($labelVal) : 'Facility';
                $container->alertMsg = $facilityLabel . ' added successfully';
                return $this->redirect()->toRoute('facility');
            }
        }

        return new ViewModel(array(
            'facilities' => $this->facilityTable->fetchAll(),
            'form' => $this->facilityForm,
        ));
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
        if (!$id) {
            return $this->redirect()->toRoute('facility', array(
                'action' => 'index'
            ));
        }

        try {
            $facility = $this->facilityTable->getFacility($id);
            //            die(print_r($facility));
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('facility', array(
                'action' => 'index'
            ));
        }
        
        $this->facilityForm->bind($facility);
        $this->facilityForm->get('submit')->setAttribute('value', 'Update');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            //$facility = new Facility();
            $this->facilityForm->setInputFilter($facility->getInputFilter());
            $this->facilityForm->setData($request->getPost());
            if ($this->facilityForm->isValid()) {
                //$facility->exchangeArray($this->facilityForm->getData());
                $this->facilityTable->saveFacility($facility);
                $container = new Container('alert');
                $labelVal = $this->commonService->getGlobalValue('facilities');
                $facilityLabel = (isset($labelVal) && trim($labelVal) != '') ? ucwords($labelVal) : 'Facility';
                $container->alertMsg = $facilityLabel . ' updated successfully';
                return $this->redirect()->toRoute('facility');
            }
        }

        return array(
            'id' => $id,
            'form' => $this->facilityForm,
        );
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('facility');
        } else {
            $forein_key = $this->facilityTable->foreigne_key($id);
            if ($forein_key == 0) {
                $this->facilityTable->deleteFacility($id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('facility');
            } else {
                $container = new Container('alert');
                $labelVal = $this->commonService->getGlobalValue('facilities');
                $facilityLabel = (isset($labelVal) && trim($labelVal) != '') ? strtolower($labelVal) : 'facility';
                $container->alertMsg = 'Unable to delete this ' . $facilityLabel . ' because it is used for one or more provider(s).';
                return $this->redirect()->toRoute('facility');
            }
        }
    }
}

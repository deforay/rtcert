<?php

namespace Certification\Controller;

use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Certification\Model\Facility;
use Certification\Form\FacilityForm;

class FacilityController extends AbstractActionController {

    protected $facilityTable;

    public function getFacilityTable() {
        if (!$this->facilityTable) {
            $sm = $this->getServiceLocator();
            $this->facilityTable = $sm->get('Certification\Model\FacilityTable');
        }
        return $this->facilityTable;
    }

    public function indexAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new FacilityForm($dbAdapter);
        $form->get('submit')->setValue('Submit');
        $commonSerive = $this->getServiceLocator()->get('CommonService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $facility = new Facility();
            $form->setInputFilter($facility->getInputFilter());
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $facility->exchangeArray($form->getData());
                $this->getFacilityTable()->saveFacility($facility);
                $container = new Container('alert');
                $labelVal = $commonSerive->getGlobalValue('facilities');
                $facilityLabel = (isset($labelVal) && trim($labelVal)!= '')?ucwords($labelVal):'Facility';
                $container->alertMsg = $facilityLabel.' added successfully';
                return $this->redirect()->toRoute('facility');
            }
        }

        return new ViewModel(array(
            'facilities' => $this->getFacilityTable()->fetchAll(),
            'form' => $form,
        ));
    }

    public function editAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
        if (!$id) {
            return $this->redirect()->toRoute('facility', array(
                        'action' => 'index'
            ));
        }

        try {
            $facility = $this->getFacilityTable()->getFacility($id);
//            die(print_r($facility));
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('facility', array(
                        'action' => 'index'
            ));
        }
        $form = new FacilityForm($dbAdapter);
        $form->bind($facility);
        $form->get('submit')->setAttribute('value', 'Update');
        $commonSerive = $this->getServiceLocator()->get('CommonService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            //$facility = new Facility();
            $form->setInputFilter($facility->getInputFilter());
            $form->setData($request->getPost());
            if ($form->isValid()) {
                //$facility->exchangeArray($form->getData());
                $this->getFacilityTable()->saveFacility($facility);
                $container = new Container('alert');
                $labelVal = $commonSerive->getGlobalValue('facilities');
                $facilityLabel = (isset($labelVal) && trim($labelVal)!= '')?ucwords($labelVal):'Facility';
                $container->alertMsg = $facilityLabel.' updated successfully';
                return $this->redirect()->toRoute('facility');
            }
        }

        return array(
            'id' => $id,
            'form' => $form,
        );
    }
    
    public function deleteAction() {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('facility');
        } else {
            $forein_key = $this->getFacilityTable()->foreigne_key($id);
            if ($forein_key == 0) {
                $this->getFacilityTable()->deleteFacility($id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('facility');
            } else {
                $container = new Container('alert');
                $commonSerive = $this->getServiceLocator()->get('CommonService');
                $labelVal = $commonSerive->getGlobalValue('facilities');
                $facilityLabel = (isset($labelVal) && trim($labelVal)!= '')?strtolower($labelVal):'facility';
                $container->alertMsg = 'Unable to delete this '.$facilityLabel.' because it is used for one or more provider(s).';
                return $this->redirect()->toRoute('facility');
            }
        }
    }

}

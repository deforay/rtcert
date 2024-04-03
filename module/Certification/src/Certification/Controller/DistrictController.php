<?php

namespace Certification\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Certification\Model\District;
use Certification\Form\DistrictForm;

class DistrictController extends AbstractActionController
{


    public \Certification\Model\DistrictTable $districtTable;
    public \Application\Service\CommonService $commonService;
    public \Certification\Form\DistrictForm $districtForm;

    public function __construct($commonService, $districtTable, $districtForm)
    {
        $this->commonService = $commonService;
        $this->districtTable = $districtTable;
        $this->districtForm = $districtForm;
    }


    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        
        $this->districtForm->get('submit')->setValue('Submit');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $district = new District();
            $this->districtForm->setInputFilter($district->getInputFilter());
            $this->districtForm->setData($request->getPost());
            if ($this->districtForm->isValid()) {
                $district->exchangeArray($this->districtForm->getData());
                $this->commonService->saveDistrict($district);
                return $this->redirect()->toRoute('district');
            }
        }
        return new ViewModel(array(
            'districts' => $this->commonService->getAllDistricts(),
            'form' => $this->districtForm,
        ));
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
        if ($id === 0) {
            return $this->redirect()->toRoute('district', array(
                'action' => 'index'
            ));
        }

        try {
            $district = $this->commonService->getLocation($id);
            //             die(print_r($district));
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('district', array(
                'action' => 'index'
            ));
        }

        
        $this->districtForm->bind($district);
        $this->districtForm->get('submit')->setAttribute('value', 'Update');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $district = new District();
            $this->districtForm->setInputFilter($district->getInputFilter());
            $this->districtForm->setData($request->getPost());
            if ($this->districtForm->isValid()) {
                $district->exchangeArray($this->districtForm->getData());
                $this->commonService->saveDistrict($district);
                return $this->redirect()->toRoute('district');
            }
        }

        return array(
            'id' => $id,
            'form' => $this->districtForm,
        );
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('district');
        } else {
            $forein_key = $this->districtTable->foreigne_key($id);
            if ($forein_key == 0) {
                $this->commonService->deleteLocation($id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('district');
            } else {
                $container = new Container('alert');
                $config = $this->commonService->getGlobalConfigDetails();
                $districtLabel = (isset($config['districts']) && trim($config['districts']) != '') ? strtolower($config['districts']) : 'district';
                $facilityLabel = (isset($config['facilities']) && trim($config['facilities']) != '') ? strtolower($config['facilities']) : 'facility(ies)';
                $container->alertMsg = 'Unable to delete this ' . $districtLabel . ' because it is used for one or more ' . $facilityLabel;
                return $this->redirect()->toRoute('district');
            }
        }
    }
}

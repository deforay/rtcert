<?php

namespace Certification\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Certification\Model\Region;
use Certification\Form\RegionForm;

class RegionController extends AbstractActionController
{


    public \Certification\Model\RegionTable $regionTable;
    public \Application\Service\CommonService $commonService;
    public \Certification\Form\RegionForm $regionForm;

    public function __construct($commonService, $regionTable, $regionForm)
    {
        $this->commonService = $commonService;
        $this->regionTable = $regionTable;
        $this->regionForm = $regionForm;
    }



    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $this->regionForm->get('submit')->setValue('Submit');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $region = new Region();
            $this->regionForm->setInputFilter($region->getInputFilter());
            $this->regionForm->setData($request->getPost());
            if ($this->regionForm->isValid()) {
                $region->exchangeArray($this->regionForm->getData());
                $this->commonService->saveRegion($region);
                return $this->redirect()->toRoute('region');
            }
        }

        return new ViewModel(array(
            'regions' => $this->commonService->getAllProvinces(),
            'form' => $this->regionForm,
        ));
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
        if ($id === 0) {
            return $this->redirect()->toRoute('region', array(
                'action' => 'index'
            ));
        }
        try {
            $region = $this->commonService->getLocation($id);
            //            die(print_r($region));
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('region', array(
                'action' => 'index'
            ));
        }

        $this->regionForm->bind($region);
        $this->regionForm->get('submit')->setAttribute('value', 'Update');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $region = new Region();
            $this->regionForm->setInputFilter($region->getInputFilter());
            $this->regionForm->setData($request->getPost());
            if ($this->regionForm->isValid()) {
                $region->exchangeArray($this->regionForm->getData());
                $this->commonService->saveRegion($request->getPost());
                return $this->redirect()->toRoute('region');
            }
        }

        return array(
            'id' => $id,
            'form' => $this->regionForm
        );
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if ($id === 0) {
            return $this->redirect()->toRoute('region');
        } else {
            $forein_key = $this->regionTable->foreigne_key($id);
            if ($forein_key == 0) {
                $this->commonService->deleteLocation($id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('region');
            } else {
                $container = new Container('alert');
                $config = $this->commonService->getGlobalConfigDetails();
                $regionLabel = (isset($config['region']) && trim($config['region']) != '') ? strtolower($config['region']) : 'region';
                $districtLabel = (isset($config['districts']) && trim($config['districts']) != '') ? strtolower($config['districts']) : 'districts';
                $container->alertMsg = 'Unable to delete this ' . strtolower($regionLabel) . ' because it is used for one or more ' . $districtLabel;
                return $this->redirect()->toRoute('region');
            }
        }
    }
}

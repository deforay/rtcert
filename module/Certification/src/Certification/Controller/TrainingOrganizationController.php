<?php

namespace Certification\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Certification\Model\TrainingOrganization;
use Laminas\Session\Container;

class TrainingOrganizationController extends AbstractActionController
{

    public \Certification\Model\TrainingOrganizationTable $trainingOrganizationTable;
    public \Certification\Form\TrainingOrganizationForm $trainingOrganizationForm;

    public function __construct($trainingOrganizationForm, $trainingOrganizationTable)
    {
        $this->trainingOrganizationTable = $trainingOrganizationTable;
        $this->trainingOrganizationForm = $trainingOrganizationForm;
    }


    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $paginator = $this->trainingOrganizationTable->fetchAll();
        return new ViewModel(array(
            'paginator' => $paginator,
        ));
    }

    public function addAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $this->trainingOrganizationForm->get('submit')->setValue('SUBMIT');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $training_organization = new TrainingOrganization();
            $this->trainingOrganizationForm->setInputFilter($training_organization->getInputFilter());
            $this->trainingOrganizationForm->setData($request->getPost());

            if ($this->trainingOrganizationForm->isValid()) {
                $training_organization->exchangeArray($this->trainingOrganizationForm->getData());
                $this->trainingOrganizationTable->saveTraining_Organization($training_organization);
                $container = new Container('alert');
                $container->alertMsg = 'Training Organization added successfully';

                return $this->redirect()->toRoute('training-organization');
            }
        }
        return array('form' => $this->trainingOrganizationForm);
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $training_organization_id = (int) base64_decode($this->params()->fromRoute('training_organization_id', 0));

        if (!$training_organization_id) {
            return $this->redirect()->toRoute('training-organization', array('action' => 'add'));
        }

        try {
            $training_organization = $this->trainingOrganizationTable->getTraining_organization($training_organization_id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('training-organization', array('action' => 'index'));
        }


        $this->trainingOrganizationForm->bind($training_organization);
        $this->trainingOrganizationForm->get('submit')->setAttribute('value', 'UPDATE');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->trainingOrganizationForm->setInputFilter($training_organization->getInputFilter());
            $this->trainingOrganizationForm->setData($request->getPost());

            if ($this->trainingOrganizationForm->isValid()) {
                $this->trainingOrganizationTable->saveTraining_Organization($training_organization);
                $container = new Container('alert');
                $container->alertMsg = 'Training Organization updated successfully';
                return $this->redirect()->toRoute('training-organization');
            }
        }

        return array(
            'training_organization_id' => $training_organization_id,
            'form' => $this->trainingOrganizationForm,
        );
    }

    public function deleteAction()
    {
        $training_organization_id = (int) $this->params()->fromRoute('training_organization_id', 0);

        if (!$training_organization_id) {
            return $this->redirect()->toRoute('training-organization');
        } else {
            $forein_key = $this->trainingOrganizationTable->foreigne_key($training_organization_id);
            if ($forein_key == 0) {
                $this->trainingOrganizationTable->deleteOrganization($training_organization_id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('training-organization');
            } else {
                $container = new Container('alert');
                $container->alertMsg = 'Unable to delete this organization because it is used for one or more training (s).';
                return $this->redirect()->toRoute('training-organization');
            }
        }
    }
}

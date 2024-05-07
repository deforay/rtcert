<?php

namespace Certification\Controller;

use Laminas\Session\Container;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Certification\Model\PracticalExam;

class PracticalExamController extends AbstractActionController
{

    public \Certification\Model\PracticalExamTable $practicalExamTable;
    public \Certification\Form\PracticalExamForm $practicalExamForm;

    public function __construct($practicalExamTable, $practicalExamForm)
    {
        $this->practicalExamForm = $practicalExamForm;
        $this->practicalExamTable = $practicalExamTable;
    }

    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        return new ViewModel(array(
            'practicals' => $this->practicalExamTable->fetchAll(),
        ));
    }

    public function addAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $id_written = (int) base64_decode($this->params()->fromQuery(base64_encode('id_written_exam'), 0));
        $provider = $this->practicalExamTable->getProviderName($id_written);
        $this->practicalExamForm->get('submit')->setValue('SUBMIT');
        $container = new Container('alert');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $practicalExam = new PracticalExam();
            $this->practicalExamForm->setInputFilter($practicalExam->getInputFilter());
            $this->practicalExamForm->setData($request->getPost());
            $written = $request->getPost('written', null);
            //            $written=$_POST['written'];
            $provider_id = $this->getRequest()->getPost('provider_id');
            $exam_to_val = $this->practicalExamTable->examToValidate($provider_id);
            if ($exam_to_val > 0) {
                $container->alertMsg = 'This tester has a review pending validation. you must first validate it in the Examination tab.';
                return $this->redirect()->toRoute('practical-exam', array('action' => 'add'));
            }
            $practical_nb = $this->practicalExamTable->counPractical($provider_id);
            $nb_days = $this->practicalExamTable->numberOfDays($provider_id);
            if (isset($nb_days) && $nb_days <= 30) {
                $container->alertMsg = 'The last attempt of this tester was ' . $nb_days . ' day(s) ago. Please wait at lease ' . date("d-m-Y", strtotime(date("Y-m-d") . "  + " . (31 - $nb_days) . " day"));
                return array(
                    'form' => $this->practicalExamForm
                );
            } elseif ($practical_nb == 0) {
                if ($this->practicalExamForm->isValid() && empty($written)) {
                    $practicalExam->exchangeArray($this->practicalExamForm->getData());
                    $this->practicalExamTable->savePracticalExam($practicalExam);
                    $last_id = $this->practicalExamTable->last_id();
                    $this->practicalExamTable->insertToExamination($last_id);
                    $container->alertMsg = 'Practical exam added successfully';
                    return $this->redirect()->toRoute('practical-exam', array('action' => 'add'));
                } elseif ($this->practicalExamForm->isValid() && !empty($written)) {
                    $practicalExam->exchangeArray($this->practicalExamForm->getData());
                    $this->practicalExamTable->savePracticalExam($practicalExam);
                    $last_id = $this->practicalExamTable->last_id();
                    $nombre2 = $this->practicalExamTable->countWritten2($id_written);
                    if ($nombre2 == 0) {
                        $this->practicalExamTable->examination($written, $last_id);
                    } else {
                        $this->practicalExamTable->insertToExamination($last_id);
                    }
                    $container->alertMsg = 'Practical exam added successfully';
                    return $this->redirect()->toRoute('practical-exam', array('action' => 'add'));
                }
            } else {
                $container->alertMsg = 'Impossible to add !!!! Because this tester has already taken a practical exam, he is waiting to add the written exam';
                return array('form' => $this->practicalExamForm);
            }
        }
        $nombre = null;
        if (isset($provider['id'])) {
            $nombre = $this->practicalExamTable->attemptNumber($provider['id']);
        }
        return array(
            'form' => $this->practicalExamForm,
            'written' => $id_written,
            'nombre' => $nombre,
            'provider' => $provider,
            'practicals' => $this->practicalExamTable->fetchAll()
        );
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $practice_exam_id = (int) base64_decode($this->params()->fromRoute('practice_exam_id', 0));
        if ($practice_exam_id === 0) {
            return $this->redirect()->toRoute('practical-exam', array(
                'action' => 'add'
            ));
        }

        try {
            $practicalExam = $this->practicalExamTable->getPracticalExam($practice_exam_id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('practical-exam', array(
                'action' => 'index'
            ));
        }
        $provider = $this->practicalExamTable->getProviderName2($practice_exam_id);
        $training = $this->practicalExamTable->getTrainingName($practice_exam_id);
        $practicalExam->date = date("d-m-Y", strtotime($practicalExam->date));
        $this->practicalExamForm->bind($practicalExam);
        $this->practicalExamForm->get('submit')->setAttribute('value', 'UPDATE');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->practicalExamForm->setInputFilter($practicalExam->getInputFilter());
            $this->practicalExamForm->setData($request->getPost());
            if ($this->practicalExamForm->isValid()) {
                $this->practicalExamTable->savePracticalExam($practicalExam);
                $container = new Container('alert');
                $container->alertMsg = 'Practical exam updated successfully';

                return $this->redirect()->toRoute('practical-exam');
            }
        }
        $attemptNumber = $this->practicalExamTable->getExamType($practice_exam_id);

        return array(
            'practice_exam_id' => $practice_exam_id,
            'form' => $this->practicalExamForm,
            'attemptNumber' => $attemptNumber,
            'provider_id' => $provider['id'],
            'provider_name' => $provider['name'],
            'training_id' => $training['id'],
            'training_name' => $training['name'],
        );
    }

    public function attemptAction()
    {
        $q = (int) $_GET['q'];
        $result = $this->practicalExamTable->attemptNumber($q);
        return array(
            'result' => $result,
        );
    }

    public function deleteAction()
    {
        $practical_exam_id = (int) base64_decode($this->params()->fromRoute('practice_exam_id', 0));

        if ($practical_exam_id === 0) {
            return $this->redirect()->toRoute('practical-exam');
        } else {
            $nb_practical = $this->practicalExamTable->CountPractical($practical_exam_id);
            if ($nb_practical == 1) {
                $this->practicalExamTable->deletePractical($practical_exam_id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('practical-exam');
            } else {
                $container = new Container('alert');
                $container->alertMsg = 'This practical exam can not be deleted because it is already used for another examination!';
                return $this->redirect()->toRoute('practical-exam');
            }
        }
    }
    public function importExcelAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }

        $this->practicalExamForm->get('submit')->setValue('SUBMIT');


        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $result = $this->practicalExamTable->uploadPracticalExamExcel($post);
            return array(
                'form' => $this->practicalExamForm,
                'result' => $result,
            );
        }
        return array(
            'form' => $this->practicalExamForm,
            'writtens' => $this->practicalExamTable->fetchAll(),
        );
    }
}

<?php

namespace Certification\Controller;

use Laminas\Session\Container;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class WrittenExamController extends AbstractActionController
{

    public \Certification\Model\WrittenExamTable $writtenExamTable;
    public \Certification\Form\WrittenExamForm $writtenExamform;


    public function __construct($writtenExamform, $writtenExamTable)
    {
        $this->writtenExamTable = $writtenExamTable;
        $this->writtenExamform = $writtenExamform;
    }

    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->writtenExamTable->fetchAllWrittenExam($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $practical = (int) base64_decode($this->params()->fromQuery(base64_encode('practice_exam_id'), 0));
        $provider = $this->writtenExamTable->getProviderName($practical);
        $this->writtenExamform->get('submit')->setValue('SUBMIT');
        $container = new Container('alert');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {

            $writtenExam = new \Certification\Model\WrittenExam();
            $this->writtenExamform->setInputFilter($writtenExam->getInputFilter());
            $this->writtenExamform->setData($request->getPost());
            $practical = $request->getPost('practical', null);
            $provider_id = $this->getRequest()->getPost('provider_id');
            $exam_to_val = $this->writtenExamTable->examToValidate($provider_id);
            if ($exam_to_val > 0) {
                $container->alertMsg = 'This tester has a review pending validation. you must first validate it in the Examination tab.';
                return $this->redirect()->toRoute('written-exam', array('action' => 'add'));
            }
            $written = $this->writtenExamTable->counWritten($provider_id);

            $nb_days = $this->writtenExamTable->numberOfDays($provider_id);
            if (isset($nb_days) && $nb_days <= 30) {
                $container->alertMsg = 'The last attempt of this tester was ' . $nb_days . ' day(s) ago. Please wait at lease ' . date("d-m-Y", strtotime(date("Y-m-d") . "  + " . (31 - $nb_days) . " day"));
                return array(
                    'form' => $this->writtenExamform,
                );
            } elseif ($written == 0) {
                if ($this->writtenExamform->isValid() && empty($practical)) {
                    $writtenExam->exchangeArray($this->writtenExamform->getData());
                    $this->writtenExamTable->saveWrittenExam($writtenExam);
                    $last_id = $this->writtenExamTable->last_id();
                    $this->writtenExamTable->insertToExamination($last_id);
                    $container->alertMsg = 'Written exam added successfully';
                    return $this->redirect()->toRoute('written-exam', array('action' => 'add'));
                } elseif ($this->writtenExamform->isValid() && !empty($practical)) {
                    $writtenExam->exchangeArray($this->writtenExamform->getData());
                    $this->writtenExamTable->saveWrittenExam($writtenExam);
                    $last_id = $this->writtenExamTable->last_id();
                    $nombre2 = $this->writtenExamTable->countPractical2($practical);
                    if ($nombre2 == 0) {
                        $this->writtenExamTable->examination($last_id, $practical);
                    } else {
                        $this->writtenExamTable->insertToExamination($last_id);
                    }
                    $container->alertMsg = 'written exam added successfully';
                    return $this->redirect()->toRoute('written-exam', array('action' => 'add'));
                }
            } else {
                $container->alertMsg = 'Unable to process this request. This tester has already taken a written exam.';
                return array('form' => $this->writtenExamform);
            }
        }
        $nombre = null;
        if (isset($provider['id'])) {
            $nombre = $this->writtenExamTable->attemptNumber($provider['id']);
        }
        return new ViewModel(array(
            'form' => $this->writtenExamform,
            'practical' => $practical,
            'nombre' => $nombre,
            'provider' => $provider,
        ));
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $id_written_exam = (int) base64_decode($this->params()->fromRoute('id_written_exam', 0));
        if ($id_written_exam === 0) {
            return $this->redirect()->toRoute('written-exam', array(
                'action' => 'add'
            ));
        }

        try {
            $writtenExam = $this->writtenExamTable->getWrittenExam($id_written_exam);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('written-exam', array(
                'action' => 'index'
            ));
        }
        $provider = $this->writtenExamTable->getProviderName2($id_written_exam);
        $training = $this->writtenExamTable->getTrainingName($id_written_exam);

        $writtenExam->date = date("d-m-Y", strtotime($writtenExam->date));
        $this->writtenExamform->bind($writtenExam);
        $this->writtenExamform->get('submit')->setAttribute('value', 'UPDATE');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->writtenExamform->setInputFilter($writtenExam->getInputFilter());
            $this->writtenExamform->setData($request->getPost());

            if ($this->writtenExamform->isValid()) {
                $this->writtenExamTable->saveWrittenExam($writtenExam);
                $container = new Container('alert');
                $container->alertMsg = 'Written exam updated successfully';

                return $this->redirect()->toRoute('written-exam');
            }
        }
        $attemptNumber = $this->writtenExamTable->getExamType($id_written_exam);
        return array(
            'id_written_exam' => $id_written_exam,
            'form' => $this->writtenExamform,
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
        $result = $this->writtenExamTable->attemptNumber($q);
        return array(
            'result' => $result,
        );
    }

    public function deleteAction()
    {
        $id_written_exam = (int) base64_decode($this->params()->fromRoute('id_written_exam', 0));

        if ($id_written_exam === 0) {
            return $this->redirect()->toRoute('written-exam');
        } else {
            $nb_written = $this->writtenExamTable->CountWritten($id_written_exam);
            if ($nb_written == 1) {
                $this->writtenExamTable->deleteWritten($id_written_exam);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('written-exam');
            } else {
                $container = new Container('alert');
                $container->alertMsg = 'This written exam can not be deleted because it is already used for another examination!';
                return $this->redirect()->toRoute('written-exam');
            }
        }
    }

    public function importExcelAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }

        $this->writtenExamform->get('submit')->setValue('SUBMIT');


        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $result = $this->writtenExamTable->uploadWrittenExamExcel($post);
            return array(
                'form' => $this->writtenExamform,
                'result' => $result,
            );
        }
        return array(
            'form' => $this->writtenExamform,
            'writtens' => $this->writtenExamTable->fetchAll(),
        );
    }
}

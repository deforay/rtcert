<?php

namespace Certification\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Certification\Model\Provider;
use Certification\Form\ProviderForm;
use Laminas\Json\Json;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProviderController extends AbstractActionController
{

    public \Application\Service\CommonService $commonService;
    public \Application\Service\TestService $testService;
    public \Application\Service\QuestionService $questionService;
    public \Certification\Model\ProviderTable $providerTable;
    public \Certification\Form\ProviderForm $providerForm;

    public function __construct($commonService, $questionService, $testService, $providerForm, $providerTable)
    {
        $this->commonService = $commonService;
        $this->questionService = $questionService;
        $this->testService = $testService;
        $this->providerTable = $providerTable;
        $this->providerForm = $providerForm;
    }

    public function indexAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        // return new ViewModel(array(
        //     'providers' => $this->providerTable->fetchAll(),
        // ));

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->providerTable->fetchProviderData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $this->providerForm->get('submit')->setValue('SUBMIT');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $provider = new Provider();
            $this->providerForm->setInputFilter($provider->getInputFilter());
            $this->providerForm->setData($post);
            $select_time = $request->getPost('select_time');
            if ($this->providerForm->isValid()) {
                $provider->exchangeArray($this->providerForm->getData());
                $provider->time_worked = $provider->time_worked . ' ' . $select_time;
                $this->providerTable->saveProvider($provider);
                $container = new Container('alert');
                $container->alertMsg = 'New tester added successfully';
                return $this->redirect()->toRoute('provider', array('action' => 'add'));
            }
        }
        return array(
            'form' => $this->providerForm,
            'providers' => $this->providerTable->fetchAll(),
        );
    }

    public function editAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $id = (int) base64_decode($this->params()->fromRoute('id', 0));

        if ($id === 0) {
            return $this->redirect()->toRoute('provider', array('action' => 'add'));
        }

        try {
            $provider = $this->providerTable->getProvider($id);
            //            die(print_r($provider));
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('provider', array(
                'action' => 'index'
            ));
        }

        $time_array = explode(' ', $provider->time_worked);
        $time1 = $time_array[0];
        $time2 = $time_array[1];

        $provider->time_worked = $time1;
        $this->providerForm->bind($provider);
        $this->providerForm->get('submit')->setAttribute('value', 'UPDATE');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $select_time = $request->getPost('select_time');
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            // Debug::dump($post);die;
            $this->providerForm->setInputFilter($provider->getInputFilter());
            $this->providerForm->setData($post);

            if ($this->providerForm->isValid()) {
                $provider->time_worked = $provider->time_worked . ' ' . $select_time;
                $this->providerTable->saveProvider($provider);
                $container = new Container('alert');
                $container->alertMsg = 'Tester updated successfully';
                return $this->redirect()->toRoute('provider');
            }
            /* else{
                Debug::dump($this->providerForm->getMessages());die;
            } */
        }
        $location = $this->providerTable->getCountryIdbyRegion($provider->region);
        return array(
            'id' => $id,
            'form' => $this->providerForm,
            'country_id' => $location['country_id'],
            'region_id' => $provider->region,
            'district_id' => $provider->district,
            'facility_id' => $provider->facility_id,
            'profile_picture' => $provider->profile_picture,
            'time2' => $time2,
        );
    }

    public function regionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->providerTable->getRegion($params);
            $viewModel = new ViewModel(array(
                'result' => $result,
                'params' => $params
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
        //$q = (int) $_GET['q'];
        //$id = (isset($_GET['id']))?(int) $_GET['id']:'';
        //$result = $this->providerTable->getRegion($q);
        //return array(
        //    'result' => $result,
        //    'id'=>$id
        //);
    }

    public function districtAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $q = (int) $_GET['q'];
        $id = (isset($_GET['id'])) ? (int) $_GET['id'] : '';
        $result = $this->providerTable->getDistrict($q);
        return array(
            'result' => $result,
            'id' => $id
        );
    }

    public function facilityAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $q = (int) $_GET['q'];
        $id = (isset($_GET['id'])) ? (int) $_GET['id'] : '';
        $result = $this->providerTable->getFacility($q);
        return array(
            'result' => $result,
            'id' => $id
        );
    }

    public function setCountrySelectionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->providerTable->getAllActiveCountries();
            $viewModel = new ViewModel(array(
                'result' => $result,
                'params' => $params
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
        //$id = (int) $_GET['id'];
        //$result = $this->providerTable->getAllActiveCountries();
        //return array(
        //    'id'=>$id,
        //    'result' => $result,
        //);
    }

    public function deleteAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $id = (int) $this->params()->fromRoute('id', 0);

        if ($id === 0) {
            return $this->redirect()->toRoute('provider');
        } else {
            $keys = $this->providerTable->foreigne_key($id);
            $key1 = $keys['nombre'];
            $key2 = $keys['nombre2'];
            $key3 = $keys['nombre3'];
            if ($key1 == 0 && $key2 == 0 && $key3 == 0) {
                $this->providerTable->deleteProvider($id);
                $container = new Container('alert');
                $container->alertMsg = 'Deleted successfully';
                return $this->redirect()->toRoute('provider');
            } else {
                $container = new Container('alert');
                $container->alertMsg = 'Unable to remove this provider because he has already completed an exam or a training.';
                return $this->redirect()->toRoute('provider');
            }
        }
    }

    public function xlsAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $this->providerForm->get('submit')->setValue('DOWNLOAD REPORT');
        // $this->providerForm->get('getreport')->setValue('GET REPORT');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $country = $request->getPost('country');
            $region = $request->getPost('region');
            $district = $request->getPost('district');
            $facility = $request->getPost('facility_id');
            $typeHiv = $request->getPost('type_vih_test');
            $contact_method = $request->getPost('prefered_contact_method');
            $jobTitle = $request->getPost('current_jod');
            $excludeTesterName = $request->getPost('exclude_tester_name');
            $sResult = $this->providerTable->report($country, $region, $district, $facility, $typeHiv, $contact_method, $jobTitle);
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $sheet->mergeCells('A1:F1'); //merge some column
            $sheet->mergeCells('G1:M1');
            $sheet->mergeCells('Q1:S1');
            $sheet->mergeCells('T1:V1');

            $sheet->setCellValue('A1', 'Tester Identification');
            $sheet->SetCellValue('G1', 'Tester Contact Information');
            $sheet->SetCellValue('Q1', 'Testing Site In charge');
            $sheet->SetCellValue('T1', 'Facility In Charge');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
                )
            );
            $sheet->getStyle('A1:V2')->applyFromArray($styleArray); //apply style from array style array
            $sheet->getStyle('A1:V2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border

            $sheet->getRowDimension(1)->setRowHeight(17); // row dimension
            $sheet->getRowDimension(2)->setRowHeight(30);

            $sheet->getDefaultColumnDimension()->setWidth(25);

            $sheet->getStyle('A1:F2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $sheet->getStyle('G1:M2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            $sheet->getStyle('N1:P2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            $sheet->getStyle('Q1:S2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            $sheet->getStyle('T1:V2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');


            $sheet->SetCellValue('A2', 'Certification registration no');
            $sheet->SetCellValue('B2', 'Certification id');
            $sheet->SetCellValue('C2', 'Professional registration no');
            $sheet->SetCellValue('D2', 'Last name');
            $sheet->SetCellValue('E2', 'First name');
            $sheet->SetCellValue('F2', 'Middle name');
            $sheet->SetCellValue('G2', 'Country');
            $sheet->SetCellValue('H2', 'Region');
            $sheet->SetCellValue('I2', 'District');
            $sheet->SetCellValue('J2', 'Type of vih test');
            $sheet->SetCellValue('K2', 'Phone');
            $sheet->SetCellValue('L2', 'Email');
            $sheet->SetCellValue('M2', 'Prefered contact method');
            $sheet->SetCellValue('N2', 'Facility');
            $sheet->SetCellValue('O2', 'Current job title');
            $sheet->SetCellValue('P2', 'Time worked as tester');
            $sheet->SetCellValue('Q2', 'Testing site in charge name');
            $sheet->SetCellValue('R2', 'Testing site in charge phone');
            $sheet->SetCellValue('S2', 'Testing site in charge email');
            $sheet->SetCellValue('T2', 'Facility in charge name');
            $sheet->SetCellValue('U2', 'Facility in charge phone');
            $sheet->SetCellValue('V2', 'Facility in charge email');


            $ligne = 3;
            $output = array();
            foreach($sResult as $aRow) {
                $row = array();
                $row[] = $aRow['certification_reg_no'];
                $row[] = $aRow['certification_id'];
                $row[] = $aRow['professional_reg_no'];
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['last_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['first_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['middle_name'] : '';
                $row[] = (isset($aRow['country_name'])) ? $aRow['country_name'] : '';
                $row[] = (isset($aRow['region_name'])) ? $aRow['region_name'] : '';
                $row[] = (isset($aRow['district_name'])) ? $aRow['district_name'] : '';
                $row[] = $aRow['type_vih_test'];
                $row[] = $aRow['phone'];
                $row[] = $aRow['email'];
                $row[] = $aRow['prefered_contact_method'];
                $row[] = $aRow['facility_name'];
                $row[] = $aRow['current_jod'];
                $row[] = $aRow['time_worked'];
                $row[] = $aRow['test_site_in_charge_name'];
                $row[] = $aRow['test_site_in_charge_phone'];
                $row[] = $aRow['test_site_in_charge_email'];
                $row[] = $aRow['facility_in_charge_name'];
                $row[] = $aRow['facility_in_charge_phone'];
                $row[] = $aRow['facility_in_charge_email'];
                $output[] = $row;
            }

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                $rRowCount = $rowNo + 3;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
                    } else {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode((string) $value));
                    }
                    $colNo++;
                }
            }
            $sheet->getStyle('A2:U2')->getAlignment()->setWrapText(true); // make a new line in cell
            $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  //center column contain

            $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_list of all testers.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }
        return array('form' => $this->providerForm);
    }

    public function getReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $country = $request->getPost('country');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $facility = $request->getPost('facility_id');
        $typeHiv = $request->getPost('type_vih_test');
        $contact_method = $request->getPost('prefered_contact_method');
        $jobTitle = $request->getPost('current_jod');
        $excludeTesterName = $request->getPost('exclude_tester_name');
        $provider = $this->providerTable->report($country, $region, $district, $facility, $typeHiv, $contact_method, $jobTitle);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $provider));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function testHistoryAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }
        $tester = base64_decode($this->params()->fromQuery('tester', null));
        $result = $this->providerTable->getTesterTestHistoryDetails($tester);
        $viewModel = new ViewModel(array(
            'result' => $result
        ));
        $this->layout('layout/modal');
        return $viewModel;
    }

    public function loginAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $route = $this->providerTable->loginProviderDetails($params);
            return $this->redirect()->toUrl($route);
        }
        $tester = $this->params()->fromQuery('u', null);
        if ($tester) {
            $params = $this->providerTable->getProviderByToken($tester);
            if (!$params) {
                $container = new Container('alert');
                $container->alertMsg = "Your link expired. Kindly request a link to RT Certification admin";
            } else {
                $logincontainer = new Container('credo');
                $logincontainer->roleName = 'RT Providers';
                $logincontainer->userId = $params['id'];
                $logincontainer->login = $params['username'];
                $logincontainer->roleId = 6;
                $logincontainer->roleCode = 'provider';
                return $this->redirect()->toUrl('/test/intro');
            }
        } else {
            return $this->redirect()->toUrl('/');
        }
    }

    public function logoutAction()
    {
        $sessionLogin = new Container('credo');
        $sessionLogin->getManager()->getStorage()->clear();
        return $this->redirect()->toUrl("/");
    }

    public function sendTestLinkAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $provider = $this->providerTable->saveLinkSend($params);
            if (isset($provider['provider']->email) && $provider['provider']->email != '' && $provider['provider']->email != null) {
                /* Mail services start */
                $mailTemplates = $this->commonService->getMailTemplateByPurpose('send-online-test-link');
                $config = new \Laminas\Config\Reader\Ini();
                $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                $to = $provider['provider']->email;

                $mailSearch = array('##USER##', '##URL##', '##URLWITHOUTLINK##', '##COUNTRY##');

                $linkEncode = $provider['provider']->link_token . $configResult["password"]["salt"];
                $key = hash('sha256', $linkEncode);

                $mailReplace = array(
                    $provider['provider']->first_name . ' ' . $provider['provider']->last_name,
                    "<a href='" . $configResult['domain'] . "/provider/login?u=" . $key . "'>click here</a>",
                    "" . $configResult['domain'] . "/provider/login?u=" . $key . "",
                    $provider['countryName']
                );

                $message = str_replace($mailSearch, $mailReplace, $mailTemplates['mail_content']);
                $message = str_replace("&nbsp;", "", (string) $message);
                $message = str_replace("&amp;nbsp;", "", (string) $message);
                $message = html_entity_decode($message . $mailTemplates['mail_footer'], ENT_QUOTES, 'UTF-8');

                $fromMail = $mailTemplates['mail_from'];
                $fromName = $mailTemplates['from_name'];
                $subject = $mailTemplates['mail_subject'];
                $cc = $configResult['provider']['to']['cc'];
                $bcc = "";
                /* Mail services end */
                $mail = $this->commonService->insertTempMail($to, $subject, $message, $fromMail, $fromName, $cc, $bcc);
                $viewModel = new ViewModel(array('result' => $mail));
                $viewModel->setTerminal(true);
                return $viewModel;
            } else {
                $viewModel = new ViewModel(array('result' => 0));
                $viewModel->setTerminal(true);
                return $viewModel;
            }
        }
    }

    public function testFrequencyAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->questionService->getUserTestList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function frequencyQuestionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->questionService->getFrequencyQuestionList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function exportTestDetailsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->testService->exportTestDetails();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function exportQuestionDetailsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->questionService->exportQuestionDetails();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function certificatePdfAction()
    {
        if ($this->params()->fromRoute('id') != '') {
            $testId = base64_decode($this->params()->fromRoute('id'));
            $result = $this->testService->getCertificateFieldDetails($testId);
            return array(
                'result' => $result
            );
        } elseif ($this->getRequest()->isGet()) {
            $testId = base64_decode($this->getRequest()->getQuery('testId'));
            $result = $this->testService->getCertificateFieldDetails($testId);
            return array(
                'result' => $result
            );
        }
    }




    public function importExcelAction()
    {
        $logincontainer = new Container('credo');
        if (empty($logincontainer->userId)) {
            return $this->redirect()->toRoute("login");
        }

        $this->providerForm->get('submit')->setValue('SUBMIT');


        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $result = $this->providerTable->uploadTesterExcel($post);
            return array(
                'form' => $this->providerForm,
                'result' => $result,
            );
        }
        return array(
            'form' => $this->providerForm,
            'providers' => $this->providerTable->fetchAll(),
        );
    }



    public function getProviderDataAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $parameters['addproviders'] = "addproviders";
            $result = $this->providerTable->fetchProviderData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function getTesterReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $parameters['addproviders'] = "addproviders";
            $result = $this->providerTable->reportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
}

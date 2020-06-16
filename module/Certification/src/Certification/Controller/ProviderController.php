<?php

namespace Certification\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Certification\Model\Provider;
use Certification\Form\ProviderForm;
use Zend\Debug\Debug;
use Zend\Json\Json;

class ProviderController extends AbstractActionController {

    protected $providerTable;

    public function getProviderTable() {
        if (!$this->providerTable) {
            $sm = $this->getServiceLocator();
            $this->providerTable = $sm->get('Certification\Model\ProviderTable');
        }
        return $this->providerTable;
    }

    public function indexAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        return new ViewModel(array(
            'providers' => $this->getProviderTable()->fetchAll(),
        ));
    }

    public function addAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new ProviderForm($dbAdapter);
        $form->get('submit')->setValue('SUBMIT');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            ); 
            $provider = new Provider();
            $form->setInputFilter($provider->getInputFilter());
            $form->setData($post);
            $select_time = $request->getPost('select_time');
            if ($form->isValid()) {
                $provider->exchangeArray($form->getData());
                $provider->time_worked = $provider->time_worked . ' ' . $select_time;
                $this->getProviderTable()->saveProvider($provider);
                $container = new Container('alert');
                $container->alertMsg = 'New tester added successfully';
                return $this->redirect()->toRoute('provider', array('action' => 'add'));
            }
        }
        return array('form' => $form,
            'providers' => $this->getProviderTable()->fetchAll(),
        );
    }

    public function editAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

        $id = (int) base64_decode($this->params()->fromRoute('id', 0));

        if (!$id) {
            return $this->redirect()->toRoute('provider', array('action' => 'add'));
        }

        try {
            $provider = $this->getProviderTable()->getProvider($id);
//            die(print_r($provider));
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('provider', array(
                        'action' => 'index'
            ));
        }

        $form = new ProviderForm($dbAdapter);
        $time_array = explode(' ', $provider->time_worked);
        $time1 = $time_array[0];
        $time2 = $time_array[1];

        $provider->time_worked = $time1;
        $form->bind($provider);
        $form->get('submit')->setAttribute('value', 'UPDATE');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $select_time = $request->getPost('select_time');

            $form->setInputFilter($provider->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $provider->time_worked = $provider->time_worked . ' ' . $select_time;
                $this->getProviderTable()->saveProvider($provider);
                $container = new Container('alert');
                $container->alertMsg = 'Tester updated successfully';
                return $this->redirect()->toRoute('provider');
            }
        }
        $location = $this->getProviderTable()->getCountryIdbyRegion($provider->region);
        return array(
            'id' => $id,
            'form' => $form,
            'country_id' => $location['country_id'],
            'region_id' => $provider->region,
            'district_id' => $provider->district,
            'facility_id' => $provider->facility_id,
            'time2' => $time2,
        );
    }

    public function regionAction() {
        $request = $this->getRequest();                
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->getProviderTable()->getRegion($params);
            $viewModel = new ViewModel(array(
                'result' => $result,
                'params'=>$params
                ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
        //$q = (int) $_GET['q'];
        //$id = (isset($_GET['id']))?(int) $_GET['id']:'';
        //$result = $this->getProviderTable()->getRegion($q);
        //return array(
        //    'result' => $result,
        //    'id'=>$id
        //);
    }
    
    public function districtAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $q = (int) $_GET['q'];
        $id = (isset($_GET['id']))?(int) $_GET['id']:'';
        $result = $this->getProviderTable()->getDistrict($q);
        return array(
            'result' => $result,
            'id'=>$id
        );
    }

    public function facilityAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $q = (int) $_GET['q'];
        $id = (isset($_GET['id']))?(int) $_GET['id']:'';
        $result = $this->getProviderTable()->getFacility($q);
        return array(
            'result' => $result,
            'id'=>$id
        );
    }

    public function setCountrySelectionAction() {
        $request = $this->getRequest();                
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->getProviderTable()->getAllActiveCountries();
            $viewModel = new ViewModel(array(
                'result' => $result,
                'params'=>$params
                ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
        //$id = (int) $_GET['id'];
        //$result = $this->getProviderTable()->getAllActiveCountries();
        //return array(
        //    'id'=>$id,
        //    'result' => $result,
        //);
    }
    
    public function deleteAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('provider');
        } else {
            $keys = $this->getProviderTable()->foreigne_key($id);
            $key1 = $keys['nombre'];
            $key2 = $keys['nombre2'];
            $key3 = $keys['nombre3'];
            if ($key1 == 0 && $key2 == 0 && $key3 == 0) {
                $this->getProviderTable()->deleteProvider($id);
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

    public function xlsAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new ProviderForm($dbAdapter);
        $form->get('submit')->setValue('DOWNLOAD REPORT');
        // $form->get('getreport')->setValue('GET REPORT');
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
            $provider = $this->getProviderTable()->report($country, $region, $district, $facility, $typeHiv, $contact_method, $jobTitle);
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:F1'); //merge some column
            $objPHPExcel->setActiveSheetIndex()->mergeCells('G1:M1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('Q1:S1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('T1:V1');

            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Tester Identification');
            $objPHPExcel->getActiveSheet()->SetCellValue('G1', 'Tester Contact Information');
            $objPHPExcel->getActiveSheet()->SetCellValue('Q1', 'Testing Site In charge');
            $objPHPExcel->getActiveSheet()->SetCellValue('T1', 'Facility In Charge');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
            ));
            $objPHPExcel->getActiveSheet()->getStyle('A1:V2')->applyFromArray($styleArray); //apply style from array style array
            $objPHPExcel->getActiveSheet()->getStyle('A1:V2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THICK); // set cell border

            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(17); // row dimension
            $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(30);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(25);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $objPHPExcel->getActiveSheet()->getStyle('G1:M2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            $objPHPExcel->getActiveSheet()->getStyle('N1:N2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            $objPHPExcel->getActiveSheet()->getStyle('Q1:S2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            $objPHPExcel->getActiveSheet()->getStyle('T1:V2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');


            $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Certification registration no');
            $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'Certification id');
            $objPHPExcel->getActiveSheet()->SetCellValue('C2', 'Professional registration no');
            $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'Last name');
            $objPHPExcel->getActiveSheet()->SetCellValue('E2', 'First name');
            $objPHPExcel->getActiveSheet()->SetCellValue('F2', 'Middle name');
            $objPHPExcel->getActiveSheet()->SetCellValue('G2', 'Country');
            $objPHPExcel->getActiveSheet()->SetCellValue('H2', 'Region');
            $objPHPExcel->getActiveSheet()->SetCellValue('I2', 'District');
            $objPHPExcel->getActiveSheet()->SetCellValue('J2', 'Type of vih test');
            $objPHPExcel->getActiveSheet()->SetCellValue('K2', 'Phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('L2', 'Email');
            $objPHPExcel->getActiveSheet()->SetCellValue('M2', 'Prefered contact method');
            $objPHPExcel->getActiveSheet()->SetCellValue('N2', 'Facility');
            $objPHPExcel->getActiveSheet()->SetCellValue('O2', 'Current job title');
            $objPHPExcel->getActiveSheet()->SetCellValue('P2', 'Time worked as tester');
            $objPHPExcel->getActiveSheet()->SetCellValue('Q2', 'Testing site in charge name');
            $objPHPExcel->getActiveSheet()->SetCellValue('R2', 'Testing site in charge phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('S2', 'Testing site in charge email');
            $objPHPExcel->getActiveSheet()->SetCellValue('T2', 'Facility in charge name');
            $objPHPExcel->getActiveSheet()->SetCellValue('U2', 'Facility in charge phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('V2', 'Facility in charge email');


            $ligne = 3;
            foreach ($provider as $provider) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $ligne, $provider['certification_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $ligne, $provider['certification_id']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $ligne, $provider['professional_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$provider['last_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$provider['first_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$provider['middle_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $ligne, (isset($provider['country_name']))?$provider['country_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, $ligne, (isset($provider['region_name']))?$provider['region_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, $ligne, (isset($provider['district_name']))?$provider['district_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, $ligne, $provider['type_vih_test']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, $ligne, $provider['phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, $ligne, $provider['email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(12, $ligne, $provider['prefered_contact_method']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, $ligne, $provider['facility_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, $ligne, $provider['current_jod']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(15, $ligne, $provider['time_worked']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(16, $ligne, $provider['test_site_in_charge_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(17, $ligne, $provider['test_site_in_charge_phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(18, $ligne, $provider['test_site_in_charge_email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(19, $ligne, $provider['facility_in_charge_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(20, $ligne, $provider['facility_in_charge_phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(21, $ligne, $provider['facility_in_charge_email']);
                $ligne++;
            }
            $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getAlignment()->setWrapText(true); // make a new line in cell
            $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  //center column contain

            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_list of all testers.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            exit;
        }
        return array('form' => $form);
    }

    public function getTesterReportAction()
    {
        $request = $this->getRequest();
        $country = $request->getPost('country');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $facility = $request->getPost('facility_id');
        $typeHiv = $request->getPost('type_vih_test');
        $contact_method = $request->getPost('prefered_contact_method');
        $jobTitle = $request->getPost('current_jod');
        $excludeTesterName = $request->getPost('exclude_tester_name');
        $provider = $this->getProviderTable()->report($country, $region, $district, $facility, $typeHiv, $contact_method, $jobTitle);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' =>$provider));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    public function testHistoryAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        $tester = base64_decode($this->params()->fromQuery('tester', null));
        $result = $this->getProviderTable()->getTesterTestHistoryDetails($tester);
        $viewModel = new ViewModel(array(
            'result'=>$result
        ));
        $this->layout('layout/modal');
        return $viewModel;
    }

    public function loginAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $route = $this->getProviderTable()->loginProviderDetails($params);
            return $this->redirect()->toUrl($route);
        }
        $tester = $this->params()->fromQuery('u', null);
        if($tester){
            $params = $this->getProviderTable()->getProviderByToken($tester);
            if(!$params){
                $container = new Container('alert');
                $container->alertMsg = "Your link expired. Kindly request a link to RT Certification admin";
            }else{
                $logincontainer = new Container('credo');
                $logincontainer->roleName = 'RT Providers';
                $logincontainer->userId = $params['id'];
                $logincontainer->login = $params['username'];
                $logincontainer->roleId = 6;
                $logincontainer->roleCode = 'provider';
                return $this->redirect()->toUrl('/test/intro');
            }
        }else{
            return $this->redirect()->toUrl('/');
        }
    }

    public function logoutAction() {
        $sessionLogin = new Container('credo');
        $sessionLogin->getManager()->getStorage()->clear();
        return $this->redirect()->toUrl("/");
    }

    public function sendTestLinkAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $provider = $this->getProviderTable()->saveLinkSend($params);
            if(isset($provider['provider']->email) && $provider['provider']->email != '' && $provider['provider']->email != null){
                /* Mail services start */
                $commonService = $this->getServiceLocator()->get('CommonService');
                $mailTemplates = $commonService->getMailTemplateByPurpose('send-online-test-link');
                $config = new \Zend\Config\Reader\Ini();
                $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                $to = $provider['provider']->email;
                
                $mailSearch = array('##USER##', '##URL##', '##URLWITHOUTLINK##' ,'##COUNTRY##');
                
                $linkEncode = $provider['provider']->link_token . $configResult["password"]["salt"];
                $key = hash('sha256', $linkEncode);
                
                $mailReplace = array(
                    $provider['provider']->first_name.' '.$provider['provider']->last_name,
                    "<a href='".$configResult['domain']."/provider/login?u=".$key."'>click here</a>",
                    "".$configResult['domain']."/provider/login?u=".$key."",
                    $provider['countryName']
                );
                
                $message = str_replace($mailSearch, $mailReplace, $mailTemplates['mail_content']);
                $message = str_replace("&nbsp;", "", strval($message));
                $message = str_replace("&amp;nbsp;", "", strval($message));
                $message = html_entity_decode($message . $mailTemplates['mail_footer'], ENT_QUOTES, 'UTF-8');
                
                $fromMail = $mailTemplates['mail_from'];
                $fromName = $mailTemplates['from_name'];
                $subject = $mailTemplates['mail_subject'];
                $cc = $configResult['provider']['to']['cc'];
                $bcc = "";
                /* Mail services end */
                $commonService = $this->getServiceLocator()->get('CommonService');
                $mail = $commonService->insertTempMail($to, $subject, $message, $fromMail, $fromName, $cc, $bcc);
                $viewModel = new ViewModel(array('result' => $mail));
                $viewModel->setTerminal(true);
                return $viewModel;
            }else{
                $viewModel = new ViewModel(array('result' => 0));
                $viewModel->setTerminal(true);
                return $viewModel;
            }
        }
    }

    public function testFrequencyAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            // \Zend\Debug\Debug::dump($parameters);die;
            $questionService = $this->getServiceLocator()->get('QuestionService');
            $result = $questionService->getUserTestList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function frequencyQuestionAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()){
            $parameters = $request->getPost();
            $questionService = $this->getServiceLocator()->get('QuestionService');
            $result = $questionService->getFrequencyQuestionList($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function exportTestDetailsAction()
    {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $parameters = $request->getPost();
            $testService = $this->getServiceLocator()->get('TestService');
            $result=$testService->exportTestDetails($parameters);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function exportQuestionDetailsAction()
    {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $parameters = $request->getPost();
            $questionService = $this->getServiceLocator()->get('QuestionService');
            $result=$questionService->exportQuestionDetails($parameters);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function certificatePdfAction() {
        if($this->params()->fromRoute('id') != ''){
            $testId=base64_decode($this->params()->fromRoute('id'));
            $testService = $this->getServiceLocator()->get('TestService');
            $result = $testService->getCertificateFieldDetails($testId);
            return array(
                'result'=>$result
            );
        }else if ($this->getRequest()->isGet()) {
            $testId = base64_decode($this->getRequest()->getQuery('testId'));
            // \Zend\Debug\Debug::dump($testId);die;
            $testService = $this->getServiceLocator()->get('TestService');
            $result = $testService->getCertificateFieldDetails($testId);
            return array(
                'result'=>$result
            );
        }
    }

    


    public function importExcelAction() {
        $logincontainer = new Container('credo');
        if ((isset($logincontainer->userId) || !isset($logincontainer->userId)) && $logincontainer->userId == "") {
            return $this->redirect()->toRoute("login");
        }
        
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new ProviderForm($dbAdapter);
        $form->get('submit')->setValue('SUBMIT');

        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            ); 
            $result = $this->getProviderTable()->uploadTesterExcel($post);
            if($result['status']){
                return $this->redirect()->toRoute("provider");
            } else{
                return array('form' => $form,
                    'result' => $result,
                );
            }
        }
        return array('form' => $form,
            'providers' => $this->getProviderTable()->fetchAll(),
        );
    }

    public function importManuallyAction()
    {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $parameters = $request->getPost();
            $result = $this->getProviderTable()->importManuallyData($parameters);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

}
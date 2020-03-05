<?php
namespace Application\Service;

use PHPExcel;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use \Application\Service\CommonService;
use TCPDF;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime as Mime;

class TestService{

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    /* Add data to pre test  table */
    public function addPreTestData($params){
        $db = $this->sm->get('PretestQuestionsTable');
        $result = $db->savePreTestData($params);
        if($result > 0){
            return true;
        }else{
            return false;
        }
    }
    public function getPreTestAllDetails(){
        $db = $this->sm->get('PretestQuestionsTable');
        return $db->fetchPreTestAllDetails();
    }

    
    public function getPreResultDetails(){
        $db = $this->sm->get('PreTestQuestionsTable');
        return $db->fetchPreResultDetails();
    }
    /* Add data to post test  table */
    public function addPostTestData($params){
        $db = $this->sm->get('PostTestQuestionsTable');
        $result = $db->addPostTestData($params);
        if($result > 0){
            return true;
        }else{
            return false;
        }
    }
   
    public function getPostTestCompleteDetails(){
        $db = $this->sm->get('PostTestQuestionsTable');
        return $db->fetchPostTestCompleteDetails();
    }
   
    public function getPostTestAllDetails(){
        $db = $this->sm->get('PostTestQuestionsTable');
        return $db->fetchPostTestAllDetails();
    }


    public function getPostResultDetails(){
        $config = new \Zend\Config\Reader\Ini();
        $db = $this->sm->get('PostTestQuestionsTable');
        $testConfigDb = $this->sm->get('TestConfigTable');
        $tempMailDb = $this->sm->get('TempMailTable');
        $mailTemplateDb = $this->sm->get('MailTemplateTable');
        $postResult = $db->fetchPostResultDetails();
        $configResult = $testConfigDb->fetchTestConfigDetails();
        // To send passed certificate to slmta biosafety users
        $maxQuestion = count($postResult['preTestQuestion']);    
        $score = ($postResult['post_test_score'] / $maxQuestion);
        $total = round($score * 100);
        if($total>=$configResult['0']['test_config_value']){
            $mailTemplateDetails = $mailTemplateDb->fetchMailTemplate('biosafetyUserActivation');
            $testsDb = $this->sm->get('TestsTable');
            $result = $testsDb->fetchCertificateFieldDetails($postResult['test_id']);
            $fileAttached = $this->saveCertificate($result);
            $postTestDate = explode(" ",$result['field']['posttest_end_datetime']);
            $dateFormat = date("d, M Y", strtotime($postTestDate[0]));;
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $userName = ucwords($postResult['full_name']);
            $domainUrlforLink = substr($configResult['domain'],0,-1);
            $toMail = $postResult['email_id'];
            $cc = $mailTemplateDetails['mail_from'];
            $bcc = $mailTemplateDetails['mail_from'];
            $subject = "Certificate for Safety Familiarization for Clinicians course";
            $message = "Dear ".$postResult['full_name'].",<br><br>";
            $message.="Thank you for completing the Safety Familiarization for Clinicians course. Your certificate ".$result['field']['certificate_no']." dated ".$dateFormat." is attached with this email.<br><br>";
            $message.= "Regards,<br>Biosafety eLearning Team";
            $footer = $mailTemplateDetails['mail_footer'];
            $message = html_entity_decode($message . $footer, ENT_QUOTES, 'UTF-8');
            $fromMail = $mailTemplateDetails['mail_from'];
            $fromName = $mailTemplateDetails['from_name'];
            $tempMailDb->insertTempMail($message,$fromMail,$toMail,$cc,$bcc,$subject,$fromName,$fileAttached['filename']);
        }
        return $postResult;
    }

    function saveCertificate($result){
        $postTestDate = explode(" ",$result['field']['posttest_end_datetime']);
        $dateFormat = date("m/d/Y", strtotime($postTestDate[0]));;
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Biosafety Team');
        $pdf->SetTitle('Safety Familiarization for Clinicians');
        $pdf->SetSubject('Certificate for Safety Familiarization for Clinicians');
        $pdf->SetKeywords('Safety Familiarization for Clinicians, Certificate');


        // set margins
        $pdf->SetMargins(5, 5, 5);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        // remove default footer
        $pdf->setPrintFooter(false);

        // set auto page breaks
        //$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('times', '', 12);
        $pdf->setFontSubsetting(false);
        // remove default header
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(10, 20, 10, true);
        // add a page
        $pdf->AddPage();
        $pdf->Rect(15,15,180,250,'F','',$fill_color = array(241,236,231));


        // -- set new background ---
        $pdf->SetAutoPageBreak(false, 0);
        // set the starting point for the page content
        $pdf->setPageMark();

        $certificate_title = '<div style="width=10px; height=10px; text-align:center;"><span style="line-height: 0.0">This certificate is awarded to</span><br></div>';

        $tester='<div style=" color:#36a2eb; font-size:170%; text-align:center;">&nbsp;&nbsp;'.strtoupper($result['field']['full_name']).'&nbsp;&nbsp;</div>';
        $textHr = '<div style="text-align:center;width:50%;">__________________________________________________________</div>'; 
        $text_content='<div style="text-align:center;">
        <span style="font-size:85%;">for successful completion of the training and testing component of the professional  <br> Clinical Safety educational activity entitled</span> <br/><br/>
        <span style=" Font-Weight: Bold ">
            Safety Familiarization for Clinicians (an e-learning course)
            <br/><br/>
            on
            <br/><br/>
            <span>'.$dateFormat.'</span><br/>
        <span>
        Certificate Number - '.$result['field']['certificate_no'].'</span>
        </span>

        <br/><br/>
        <span style="font-size:70%">This program has been approved by the National Association for Healthcare Quality for 1.25 <br/>
        Continuing Professional Development (CPD) credits</span>
        </div>';

        $textFooter = '<div style="font-size:75%">
        Pascale Ondoa, MD, MSc, PhD
        <br/>
        Directior of Science and New initiatives
        <br/>
        African Society for Laboratory Medicine
        </div>';

        // set different text position
        //$pdf->writeHTMLCell(0, 0, 10, 5, $header_text, 0, 0, 0, true, 'J', true);
        $pdf->writeHTMLCell(0, 0, 05, 62, $certificate_title, 0, 0, 0, true, 'J', true);
        $pdf->writeHTMLCell(0, 0, 05, 78, $tester, 0, 0, 0, true, 'J', true);
        $pdf->writeHTMLCell(0, 0, 10, 94, $textHr, 0, 0, 0, true, 'J', true);
        $pdf->writeHTMLCell(0, 0, 10, 115, $text_content, 0, 0, 0, true, 'J', true);
        //$pdf->writeHTMLCell(0, 0, 130, 220, $textFooter, 0, 0, 0, true, 'J', true);


        //$img_file = dirname(__CLASS__) . 'public/assets/images/logo_cert1.png';
        $img_file2 = '/assets/images/logo_cert2.jpg';
        $img_file3 = '/assets/images/logo_cert3.png';

        //$pdf->Image($img_file, 25, 25, 35, '', '', '', '', false, 300, '', false, false, 0);

        $pdf->Image($img_file2, 165, 22, 20, '', '', '', '', false, 300, '', false, false, 0);

        $pdf->Image($img_file3, 25, 210, 40, '', '', '', '', false, 300, '', false, false, 0);

        $directoryName = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'biosafety-test';
        $filename = ucfirst($result['field']['full_name']) . '_HIVRT Certification_' . date('Y') . '.pdf';
        //Check if the directory already exists.
        if(!is_dir($directoryName)){
            //Directory does not exist, so lets create it.
            mkdir($directoryName, 0755);
            if(!file_exists($directoryName . DIRECTORY_SEPARATOR . $filename)) {
                $pdf->Output($directoryName . DIRECTORY_SEPARATOR . $filename,"F");
            }
        }else{
            chmod($directoryName, 0777);
            if(!file_exists($directoryName . DIRECTORY_SEPARATOR . $filename)) {
                $pdf->Output($directoryName . DIRECTORY_SEPARATOR . $filename,"F");
            }
            $pdf->Output($directoryName . DIRECTORY_SEPARATOR . $filename,"F");
        }
        $attachedResult['filepath'] = $directoryName . DIRECTORY_SEPARATOR . $filename;
        $attachedResult['filename'] = $filename;
        return $attachedResult;
    }
    public function getCertificateFieldDetails($testId)
    {
        $db = $this->sm->get('TestsTable');
        return $db->fetchCertificateFieldDetails($testId);
    }

    public function getTestsDetailsbyId(){
        $db = $this->sm->get('TestsTable');
        return $db->fetchTestsDetailsbyId();
    }

    public function exportTestDetails(){
        try{
            $querycontainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $sResult = $dbAdapter->query($querycontainer->testQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            
            $output= array();
            if(count($sResult) > 0) {
                $cResult = $dbAdapter->query($querycontainer->testQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(count($cResult) > 0) {
                    foreach($cResult as $aRow) {
                        $row = array();
                        $row[] = ucfirst($aRow['full_name']);
                        // \Zend\Debug\Debug::dump($aRow['rbp_job']);die;
                        $row[] = ucfirst($aRow['rbp_job']);

                        // Pre date time check
                        if(($aRow['pretest_start_datetime'] != "") && ($aRow['pretest_start_datetime'] != '0000-00-00 00:00:00') && ($aRow['pretest_start_datetime'] != NULL)){

                            $row[] = date("F jS,Y (H:i:s)", strtotime($aRow['pretest_start_datetime']));
                        }else{
                            $row[] = "";
                        }
                        if(($aRow['pretest_end_datetime'] != "") && ($aRow['pretest_end_datetime'] != '0000-00-00 00:00:00') && ($aRow['pretest_end_datetime'] != NULL)){

                            $row[] = date("F jS,Y (H:i:s)", strtotime($aRow['pretest_end_datetime']));
                        }else{
                            $row[] = "";
                        }

                        $row[] = $aRow['pre_test_score'];
                        $row[] = ucfirst($aRow['pre_test_status']);

                        // Post date time check
                        if(($aRow['posttest_start_datetime'] != "") && ($aRow['posttest_start_datetime'] != '0000-00-00 00:00:00') && ($aRow['posttest_start_datetime'] != NULL)){

                            $row[] = date("F jS,Y (H:i:s)", strtotime($aRow['posttest_start_datetime']));
                        }else{
                            $row[] = "";
                        }
                        if(($aRow['posttest_end_datetime'] != "") && ($aRow['posttest_end_datetime'] != '0000-00-00 00:00:00') && ($aRow['posttest_end_datetime'] != NULL)){

                            $row[] = date("F jS,Y (H:i:s)", strtotime($aRow['posttest_end_datetime']));
                        }else{
                            $row[] = "";
                        }

                        $row[] = $aRow['post_test_score'];
                        $row[] = ucfirst($aRow['post_test_status']);
                        $row[] = $aRow['certificate_no'];
                        $row[] = ucfirst($aRow['user_test_status']);

                        $output[] = $row;
                    }
                }
                $styleArray = array(
                    'font' => array(
                        'bold' => true,
                        'size'=>12,
                    ),
                    'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    ),
                    'borders' => array(
                        'outline' => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN,
                        ),
                    )
                );

                $borderStyle = array(
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                if(isset($parameters['searchByEmployee']) && $parameters['searchByEmployee']!=''){
                    $cdate =  $parameters['searchByEmployee'];
                }
                // $sheet->setCellValue('A2', html_entity_decode('Biosafety Test Details', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                
                $sheet->setCellValue('A1', html_entity_decode('Participant Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('B1', html_entity_decode('Participant Profession', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('C1', html_entity_decode('Pre Test Start Time', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D1', html_entity_decode('Pre Test End Time', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E1', html_entity_decode('Pre Test Score', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('F1', html_entity_decode('Pre Test Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('G1', html_entity_decode('Post Test Start Time', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('H1', html_entity_decode('Post Test End Time', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('J1', html_entity_decode('Post Test Score', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('I1', html_entity_decode('Post Test Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('K1', html_entity_decode('Certificate No', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('L1', html_entity_decode('Participant Test Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);


                // $sheet->mergeCells('A2:B2');
                // $sheet->getStyle('A2:B2')->getFont()->setBold(TRUE)->setSize(16);

                $sheet->getStyle('A1')->applyFromArray($styleArray);
                $sheet->getStyle('B1')->applyFromArray($styleArray);
                $sheet->getStyle('C1')->applyFromArray($styleArray);
                $sheet->getStyle('D1')->applyFromArray($styleArray);
                $sheet->getStyle('E1')->applyFromArray($styleArray);
                $sheet->getStyle('F1')->applyFromArray($styleArray);
                $sheet->getStyle('G1')->applyFromArray($styleArray);
                $sheet->getStyle('H1')->applyFromArray($styleArray);
                $sheet->getStyle('I1')->applyFromArray($styleArray);
                $sheet->getStyle('J1')->applyFromArray($styleArray);
                $sheet->getStyle('K1')->applyFromArray($styleArray);
                $sheet->getStyle('L1')->applyFromArray($styleArray);


                foreach ($output as $rowNo => $rowData) {
                    $colNo = 0;
                    foreach ($rowData as $field => $value) {
                        if (!isset($value)) {
                            $value = "";
                        }
                        if (is_numeric($value)) {
                            $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        } else {
                            $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                        $rRowCount = $rowNo + 2;
                        $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                        $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                        $sheet->getDefaultRowDimension()->setRowHeight(18);
                        $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                        $sheet->getStyleByColumnAndRow($colNo, $rowNo + 2)->getAlignment()->setWrapText(true);
                        $colNo++;
                    }
                }

                $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                $filename = 'biosafety-test-list' . date('d-M-Y-H-i-s') . '.xls';
                //The name of the directory that we need to create.
                $directoryName = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'biosafety-test';

                //Check if the directory already exists.
                if(!is_dir($directoryName)){
                    //Directory does not exist, so lets create it.
                    mkdir($directoryName, 0755);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'biosafety-test' . DIRECTORY_SEPARATOR . $filename);

                }else{
                    chmod($directoryName, 0777);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'biosafety-test' . DIRECTORY_SEPARATOR . $filename);
                }
                $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'biosafety-test' . DIRECTORY_SEPARATOR . $filename);
                return $filename;
            }else{
                return "not-found";
            }
        }
        catch (Exception $exc) {
            return "";
            error_log("GENERATE-TOUR-PLAN-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}
?>

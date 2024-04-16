<?php

namespace Application\Service;

use Exception;
use Laminas\Mail;
use Carbon\Carbon;
use TCPDF as MYPDF;
use DateTimeImmutable;
use Laminas\Mime\Mime;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;
use Laminas\Mime\Part as MimePart;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mail\Transport\Smtp as SmtpTransport;

class CommonService
{

    public $sm = null;

    public function __construct($sm = null)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public static function getCurrentWeekStartAndEndDate()
    {
        $startOfWeek = date('Y-m-d', strtotime('Monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('Sunday this week'));

        return ['weekStart' => $startOfWeek, 'weekEnd' => $endOfWeek];
    }


    public static function generateRandomString($length = 8)
    {
        if ($length % 2 != 0) {
            $length++;
        }
        return substr(bin2hex(random_bytes($length / 2)), 0, $length);
    }

    private static function parseDate(string $dateStr, ?array $formats = null, $ignoreTime = true): ?Carbon
    {
        if ($ignoreTime) {
            $dateStr = explode(' ', $dateStr)[0]; // Extract only the date part
        }
        if ($formats) {
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateStr);
                } catch (Exception $e) {
                    error_log("Invalid or unparseable date $dateStr : " . $e->getMessage());
                    continue;
                }
            }
        }
        try {
            return Carbon::parse($dateStr);
        } catch (Exception $e) {
            error_log("Invalid or unparseable date $dateStr : " . $e->getMessage());
        }

        return null;
    }

    public static function isDateValid($date): bool
    {
        $date = trim((string) $date);

        if (empty($date) || 'undefined' === $date || 'null' === $date) {
            return false;
        }

        return self::parseDate($date) !== null;
    }

    // Returns the given date in Y-m-d format
    public static function isoDateFormat($date, $includeTime = false)
    {
        if (!self::isDateValid($date)) {
            return null;
        }

        $format = $includeTime ? "Y-m-d H:i:s" : "Y-m-d";
        return Carbon::parse($date)->format($format);
    }


    // Returns the given date in d-M-Y format
    // (with or without time depending on the $includeTime parameter)
    public static function humanReadableDateFormat($date, $includeTime = false, $format = null)
    {
        if (!self::isDateValid($date)) {
            return null;
        }

        $format = $format ?? 'd-M-Y';

        $format = $includeTime ? $format . " H:i" : $format;
        return Carbon::parse($date)->format($format);
    }

    public function checkFieldValidations($params)
    {

        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $tableName = $params['tableName'];
        $fieldName = $params['fieldName'];
        $value = trim($params['value']);
        $fnct = $params['fnct'];
        try {
            $sql = new Sql($adapter);
            if ($fnct == '' || $fnct == 'null') {
                $select = $sql->select()->from($tableName)->where(array($fieldName => $value));
                //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."'");
                $statement = $sql->prepareStatementForSqlObject($select);
                $result = $statement->execute();
                $data = count($result);
            } else {
                $table = explode("##", $fnct);
                if ($fieldName == 'password') {
                    //Password encrypted
                    $config = new \Laminas\Config\Reader\Ini();
                    $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                    $password = sha1($value . $configResult["password"]["salt"]);
                    $select = $sql->select()->from($tableName)->where(array($fieldName => $password, $table[0] => $table[1]));
                    $statement = $sql->prepareStatementForSqlObject($select);
                    $result = $statement->execute();
                    $data = count($result);
                } else {
                    // first trying $table[1] without quotes. If this does not work, then in catch we try with single quotes
                    //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."' and ".$table[0]."!=".$table[1] );
                    $select = $sql->select()->from($tableName)->where(array("$fieldName='$value'", "$table[0]!=$table[1]"));
                    $statement = $sql->prepareStatementForSqlObject($select);
                    $result = $statement->execute();
                    $data = count($result);
                }
            }
            return $data;
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function insertTempMail($to, $subject, $message, $fromMail, $fromName, $cc, $bcc)
    {
        $tempmailDb = $this->sm->get('TempMailTable');
        return $tempmailDb->insertTempMailDetails($to, $subject, $message, $fromMail, $fromName, $cc, $bcc);
    }

    public function sendTempMail()
    {
        try {
            $tempMailDb = $this->sm->get('TempMailTable');
            $config = new \Laminas\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            // Setup SMTP transport using LOGIN authentication
            $transport = new SmtpTransport();
            $options = new SmtpOptions(array(
                'host' => $configResult["email"]["host"],
                'port' => $configResult["email"]["config"]["port"],
                'connection_class' => $configResult["email"]["config"]["auth"],
                'connection_config' => array(
                    'username' => $configResult["email"]["config"]["username"],
                    'password' => $configResult["email"]["config"]["password"],
                    'ssl' => $configResult["email"]["config"]["ssl"],
                ),
            ));
            $transport->setOptions($options);
            $limit = '10';
            $mailQuery = $sql->select()->from(array('tm' => 'temp_mail'))->where("status='pending'")->limit($limit);
            $mailQueryStr = $sql->buildSqlString($mailQuery);
            $mailResult = $dbAdapter->query($mailQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($mailResult) > 0) {
                foreach ($mailResult as $result) {
                    $alertMail = new Mail\Message();
                    $id = $result['temp_id'];
                    $tempMailDb->updateTempMailStatus($id);

                    $fromEmail = $result['from_mail'];
                    $fromFullName = $result['from_full_name'];

                    $alertMail->addFrom($fromEmail, $fromFullName);
                    $alertMail->addReplyTo($fromEmail, $fromFullName);
                    $alertMail->addTo($result['to_email']);

                    if (isset($result['cc']) && trim($result['cc']) != "") {
                        $alertMail->addCc($result['cc']);
                    }

                    if (isset($result['bcc']) && trim($result['bcc']) != "") {
                        $alertMail->addBcc($result['bcc']);
                    }
                    $subjectArray = explode("$$", $result['subject']);
                    if (isset($subjectArray[1])) {
                        $subject_content = $subjectArray[0];
                        $mail_purpose = $subjectArray[1];
                    } else {
                        // Handle the case where the delimiter " $$ " is not found
                        $subject_content = $result['subject']; // Assign the entire subject to $subject_content
                        $mail_purpose = ''; // Or handle it in another way based on your requirements
                    }
                    if ($mail_purpose == 'send-certificate' || $mail_purpose == 'send-reminder') {
                        if ($result['message'] != '') {
                            $messageArray = explode("$$", $result['message']);
                            $message_content = $messageArray[0];
                            $unique_ids = $messageArray[1];
                            $type_recipient = $messageArray[2];
                            $name_recipient = $messageArray[3];
                            $certificateDb = $this->sm->get('Certification\Model\CertificationTable');
                            $results = $certificateDb->getToBeSentCertificateById($unique_ids);
                            $certificateMailDb = $this->sm->get('Certification\Model\CertificationMailTable');
                            $header_text = $certificateMailDb->SelectTexteHeader();
                            $message = '';
                            foreach ($results as $data) {
                                $tester_name = strtoupper($data['first_name']) . ' ' . strtoupper($data['middle_name']) . ' ' . strtoupper($data['last_name']);
                                $subject = str_replace("##USER##", $tester_name, $subject_content);
                                if ($mail_purpose == 'send-certificate') {
                                    $message = str_replace("##USER##", $tester_name, $message_content);
                                }
                                if ($mail_purpose == 'send-reminder') {
                                    if ($type_recipient  == '') {
                                        $message = '';
                                    } elseif ($type_recipient  == 'Provider') {
                                        $message = str_replace("##CERTIFICATE_EXPIRY_DATE##", $data['date_end_validity'], $message_content);
                                    } elseif ($type_recipient != 'Provider') {
                                        $message = ' This is a reminder that the HIV tester certificate of ' . $tester_name . ' will expire on ' . $data['date_end_validity'] . '. Please contact your national certification organization to schedule both the written and practical examinations. Any delay in completing these assessments will automatically result in the withdrawal of the certificate.';
                                    }
                                }
                                $filename = '';
                                if ($mail_purpose == 'send-certificate') {
                                    // create new PDF document
                                    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

                                    // set document information
                                    $pdf->SetCreator(PDF_CREATOR);
                                    $pdf->SetAuthor('RT CERTIFICATION');
                                    $pdf->SetTitle('Personnel Certificate by RTCQI');
                                    $pdf->SetSubject('Certificate');
                                    $pdf->SetKeywords('RTCQI, HIV, Certificate, HIV Testing, RT CERTIFICATE');

                                    // set margins
                                    $pdf->SetMargins(5, 5, 5);
                                    $pdf->SetHeaderMargin(0);
                                    $pdf->SetFooterMargin(0);

                                    // remove default footer
                                    $pdf->setPrintFooter(false);

                                    // set image scale factor
                                    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

                                    // set some language-dependent strings (optional)
                                    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
                                        require_once(dirname(__FILE__) . '/lang/eng.php');
                                        $pdf->setLanguageArray($l);
                                    }

                                    // ---------------------------------------------------------
                                    // set font
                                    $pdf->SetFont('times', '', 20);
                                    $pdf->setFontSubsetting(false);
                                    // remove default header
                                    $pdf->setPrintHeader(false);
                                    $pdf->SetMargins(10, 20, 10, true);
                                    // add a page
                                    $pdf->AddPage('L', 'A4');

                                    // -- set new background ---
                                    $pdf->SetAutoPageBreak(false, 0);
                                    // set bacground image
                                    $img_file = dirname(__CLASS__) . '/public/assets/img/microsoft-word-certificate-borders.png';
                                    $pdf->Image($img_file, 0, 0, 295, 209, '', '', '', false, 300, '', false, false, 0);
                                    // set the starting point for the page content
                                    $pdf->setPageMark();

                                    $header_text = '<div style="text-align:center;"><span>' . $header_text . '</span></div>';

                                    $certificate_title = '<div style="width=10px; height=10px; text-align:center;"><span style="font-size:200%; line-height: 0.0">Certificate of Competency</span><br>
                                    <span style="line-height: 0.0">is issued to</span></div>';

                                    $tester = '<div style="color:#4B77BE; font-size:170%; text-align:center;">&nbsp;&nbsp;' . $tester_name . '&nbsp;&nbsp;</div>';

                                    $text_content = '<div style="font-size:85%;text-align:center;">For having successfully fulfilled the requirements of the Health Laboratory Practitioners’ Council<br>and is certified to be competent in the area of <strong>HIV Rapid Testing</strong>
                                    <br><span style="font-size:65%; font-style: normal;"> Note : This certificate is <span style="Font-Weight: Bold">only </span>issued for HIV Rapid Testing and does not allow to perform any other test.</span>

                                    <br><br>
                                    Professional Registration Number : <span style=" color:#4B77BE;">' . $data['professional_reg_no'] . '</span>
                                    <br>
                                    Certification Number : <span style=" color:#4B77BE; ">' . $data['certification_id'] . '</span>
                                    <br>
                                    <span style="font-size:90%; font-style: normal;">Validity : <span> ' . date("d-M-Y", strtotime($data['date_certificate_issued'])) . ' to ' . date("d-M-Y", strtotime($data['date_end_validity'])) . '</span>
                                    <br><br><br><br><br>
                                    <table style="width:900px;">
                                    <tr>
                                    <td style="text-align:left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Registrar Name and Title </td>
                                    <td style="text-align:right;">Signature of Registrar</td>
                                    </tr>
                                    </table>

                                    </div>';

                                    // set different text position
                                    $pdf->writeHTMLCell(0, 0, 10, 30, $header_text, 0, 0, 0, true, 'J', true);
                                    $pdf->writeHTMLCell(0, 0, 05, 45, $certificate_title, 0, 0, 0, true, 'J', true);
                                    $pdf->writeHTMLCell(0, 0, 05, 68, $tester, 0, 0, 0, true, 'J', true);
                                    $pdf->writeHTMLCell(0, 0, 10, 88, $text_content, 0, 0, 0, true, 'J', true);

                                    $img_file = $img_file2 = "";
                                    if (file_exists(dirname(__CLASS__) . '/public/assets/img/logo_cert1.png')) {
                                        $img_file = dirname(__CLASS__) . '/public/assets/img/logo_cert1.png';
                                    }

                                    if (file_exists(dirname(__CLASS__) . '/public/assets/img/logo_cert2.png')) {
                                        $img_file2 = dirname(__CLASS__) . '/public/assets/img/logo_cert2.png';
                                    }

                                    $pdf->Image($img_file, 20, 35, 50, 35, '', '', '', false, 300, '', false, false, 0);

                                    $pdf->Image($img_file2, 225, 35, 50, 35, '', '', '', false, 300, '', false, false, 0);

                                    ///---------------------------------------------------------
                                    $alertContainer = new Container('alert');
                                    $alertContainer->rVal = \Application\Service\CommonService::generateRandomString(6);
                                    if (!file_exists(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $alertContainer->rVal) && !is_dir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $alertContainer->rVal)) {
                                        mkdir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $alertContainer->rVal);
                                    }
                                    $pathFront = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $alertContainer->rVal;
                                    $filename = ucfirst($data['first_name']) . '' . ucfirst($data['middle_name']) . '' . ucfirst($data['last_name']) . '_HIVRT_Certification_' . date('Y') . '.pdf';
                                    $pdf->Output($pathFront . DIRECTORY_SEPARATOR . $filename, "F");
                                    $filename = $alertContainer->rVal . '##' . $filename;
                                    $alertContainer->rVal = '';
                                }
                                //$original_message = substr($message, 0, strpos($message, "$$"));
                                $html = new MimePart($message);
                                $html->type = "text/html";

                                $body = new MimeMessage();
                                $body->setParts(array($html));
                                if ($filename != '') {
                                    $fileArray = explode('##', $filename);
                                    $dirPath = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileArray[0];
                                    if (is_dir($dirPath)) {
                                        $dh  = opendir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileArray[0]);
                                        while (($filename = readdir($dh)) !== false) {
                                            if ($filename != "." && $filename != "..") {
                                                $fileContent = fopen($dirPath . DIRECTORY_SEPARATOR . $fileArray[1], 'r');
                                                $attachment = new MimePart($fileContent);
                                                $attachment->filename    = $filename;
                                                $attachment->type        = Mime::TYPE_OCTETSTREAM;
                                                $attachment->encoding    = Mime::ENCODING_BASE64;
                                                $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                                                $body->addPart($attachment);
                                            }
                                        }
                                        closedir($dh);
                                        $this->removeDirectory($dirPath);
                                    }
                                }
                                $alertMail->setSubject($subject);
                                $alertMail->setBody($body);
                                $hasSent = $transport->send($alertMail);

                                if ($hasSent || $hasSent == NULL) {
                                    $cert_id = $data['id'];
                                    $provider_id = $data['provider'];
                                    $due_date = $data['date_end_validity'];

                                    if ($mail_purpose == 'send-certificate' && !empty($cert_id)) {
                                        $certificateMailDb->dateCertificateSent($cert_id);
                                    } elseif ($mail_purpose == 'send-reminder') {
                                        $reminder_type = 'Email';
                                        $reminder_sent_to = $type_recipient;
                                        $name_reminder = $name_recipient;
                                        $date_reminder_sent = date('Y-m-d');
                                        if (!empty($cert_id)) {
                                            $certificateMailDb->insertRecertification($due_date, $provider_id, $reminder_type, $reminder_sent_to, $name_reminder, $date_reminder_sent);
                                            $certificateMailDb->reminderSent($cert_id);
                                        }
                                    }
                                    $save_mail = new \Certification\Model\CertificationMail();
                                    $save_mail->to_email = $result['to_email'];
                                    $save_mail->type = $mail_purpose;
                                    $save_mail->cc = '';
                                    $save_mail->bcc = '';
                                    $save_mail->mail_id = 0;
                                    $certificateMailDb->saveCertificationMail($save_mail);
                                    $tempMailDb->deleteTempMail($id);
                                }
                            }
                        }
                    } else {
                        $subject = str_replace("##USER##", "", $subject_content);
                        $html = new MimePart($result['message']);
                        $html->type = "text/html";

                        $body = new MimeMessage();
                        $body->setParts(array($html));

                        if (!file_exists(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "email") && !is_dir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "email")) {
                            mkdir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "email");
                        }

                        $dirPath = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "email" . DIRECTORY_SEPARATOR . $id;
                        if (!file_exists($dirPath) && !is_dir($dirPath)) {
                            mkdir($dirPath);
                        }
                        if (is_dir($dirPath)) {
                            $dh  = opendir($dirPath);
                            while (($filename = readdir($dh)) !== false) {
                                if ($filename != "." && $filename != "..") {
                                    $fileContent = fopen($dirPath . DIRECTORY_SEPARATOR . $filename, 'r');
                                    $attachment = new MimePart($fileContent);
                                    $attachment->filename    = $filename;
                                    $attachment->type        = Mime::TYPE_OCTETSTREAM;
                                    $attachment->encoding    = Mime::ENCODING_BASE64;
                                    $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                                    $body->addPart($attachment);
                                }
                            }
                            closedir($dh);
                            $this->removeDirectory($dirPath);
                        }
                        $alertMail->setSubject($subject);
                        $alertMail->setBody($body);

                        $transport->send($alertMail);
                        $tempMailDb->deleteTempMail($id);
                    }
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            error_log('whoops! Something went wrong in send-mail.');
        }
    }

    public static function getDate($timezone = 'UTC', $format = 'Y-m-d')
    {
        return Carbon::now($timezone)->format($format);
    }

    public static function getDateTime($timezone = 'UTC', $format = 'Y-m-d H:i:s')
    {
        return Carbon::now($timezone)->format($format);
    }

    public static function getCurrentTime($timezone = 'UTC', $format = 'H:i')
    {
        return Carbon::now($timezone)->format($format);
    }

    public function checkMultipleFieldValidations($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $jsonData = $params['json_data'];
        $tableName = $jsonData['tableName'];
        $sql = new Sql($adapter);
        $select = $sql->select()->from($tableName);
        foreach ($jsonData['columns'] as $val) {
            if ($val['column_value'] != "") {
                $select->where($val['column_name'] . "=" . "'" . $val['column_value'] . "'");
            }
        }
        //edit
        if (isset($jsonData['tablePrimaryKeyValue']) && $jsonData['tablePrimaryKeyValue'] != null && $jsonData['tablePrimaryKeyValue'] != "null") {
            $select->where($jsonData['tablePrimaryKeyId'] . "!=" . $jsonData['tablePrimaryKeyValue']);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return count($result);
    }

    public function getAllConfig($parameters)
    {
        $configDb = $this->sm->get('GlobalTable');
        return $configDb->fetchAllConfig($parameters);
    }
    public function getGlobalConfigDetails()
    {
        $globalDb = $this->sm->get('GlobalTable');
        return $globalDb->getGlobalConfig();
    }

    public function getGlobalValue($globalName)
    {
        $globalDb = $this->sm->get('GlobalTable');
        return $globalDb->getGlobalValue($globalName);
    }

    public function updateConfig($params)
    {
        $container = new Container('alert');
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $globalDb = $this->sm->get('GlobalTable');
            $updateRes = $globalDb->updateConfigDetails($params);
            $subject = '';
            $eventType = 'global-config-update';
            $action = 'updated global config details';
            $resourceName = 'global-config-update';
            $eventLogDb = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            $adapter->commit();
            $container->alertMsg = "Global Config Updated Successfully.";
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function dbBackup()
    {
        try {
            $configResult = include(CONFIG_PATH . '/autoload/local.php');
            $dbUsername = $configResult["db"]["username"];
            $dbPassword = $configResult["db"]["password"];
            $dbName = $configResult["db"]["data-base-name"];
            $dbHost = $configResult["db"]["data-base-host"];
            $folderPath = BACKUP_PATH . DIRECTORY_SEPARATOR;

            if (!file_exists($folderPath) && !is_dir($folderPath)) {
                mkdir($folderPath);
            }
            $currentDate = date("d-m-Y-H-i-s");
            $file = $folderPath . 'rtcert-' . $currentDate . '.sql';
            $command = sprintf("mysqldump -h %s -u %s --password='%s' -d %s --skip-no-data > %s", $dbHost, $dbUsername, $dbPassword, $dbName, $file);
            exec($command);

            $days = 30;
            if (is_dir($folderPath)) {
                $dh = opendir($folderPath);
                while (($oldFileName = readdir($dh)) !== false) {
                    if ($oldFileName == 'index.php' || $oldFileName == "." || $oldFileName == ".." || $oldFileName == "") {
                        continue;
                    }
                    $file = $folderPath . $oldFileName;
                    if (time() - filemtime($file) > (86400) * $days) {
                        unlink($file);
                    }
                }
                closedir($dh);
            }
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
            error_log('whoops! Something went wrong in cron/dbBackup');
        }
    }

    function removeDirectory($dirname)
    {
        // Sanity check
        if (!file_exists($dirname)) {
            return false;
        }

        // Simple delete for a file
        if (is_file($dirname) || is_link($dirname)) {
            return unlink($dirname);
        }

        // Loop through the folder
        $dir = dir($dirname);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Recurse
            $this->removeDirectory($dirname . DIRECTORY_SEPARATOR . $entry);
        }

        // Clean up
        $dir->close();
        return rmdir($dirname);
    }

    public function getAllActiveCountries()
    {
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $countryQuery = $sql->select()->from(array('c' => 'country'))->where("c.country_status='active'");
        $countryQueryStr = $sql->buildSqlString($countryQuery);
        return $dbAdapter->query($countryQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getAllProvinces($selectedCountries = [])
    {
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $provinceQuery = $sql->select()->from(array('l_d' => 'location_details'))
            ->join(array('c' => 'country'), 'c.country_id=l_d.country', array('country_name'))
            ->where("l_d.parent_location='0'");
        if (isset($selectedCountries) && !empty($selectedCountries)) {
            $provinceQuery = $provinceQuery->where('l_d.country IN (' . implode(',', $selectedCountries) . ')');
        }
        $provinceQueryStr = $sql->buildSqlString($provinceQuery);
        return $dbAdapter->query($provinceQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getAllDistricts($selectedProvinces = [])
    {
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $districtQuery = $sql->select()->from(array('l_d' => 'location_details'))
            ->join(array('l_d_r' => 'location_details'), 'l_d_r.location_id=l_d.parent_location', array('region_name' => 'location_name'))
            ->where("l_d.parent_location !='0'");
        if (isset($selectedProvinces) && !empty($selectedProvinces)) {
            $districtQuery = $districtQuery->where('l_d.parent_location IN (' . implode(',', $selectedProvinces) . ')');
        }
        $districtQueryStr = $sql->buildSqlString($districtQuery);
        return $dbAdapter->query($districtQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getCountryLocations($params)
    {
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $countryLocations = array();
        $provinceQuery = $sql->select()->from(array('l_d' => 'location_details'))->where("l_d.parent_location ='0'");
        if (isset($params['country']) && !empty($params['country'])) {
            $provinceQuery = $provinceQuery->where('l_d.country IN (' . implode(',', $params['country']) . ')');
        }
        $provinceQueryStr = $sql->buildSqlString($provinceQuery);
        $countryLocations['province'] = $dbAdapter->query($provinceQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $districtQuery = $sql->select()->from(array('l_d' => 'location_details'))->where("l_d.parent_location !='0'");
        if (isset($params['country']) && !empty($params['country'])) {
            $districtQuery = $districtQuery->where('l_d.country IN (' . implode(',', $params['country']) . ')');
        }
        $districtQueryStr = $sql->buildSqlString($districtQuery);
        $countryLocations['district'] = $dbAdapter->query($districtQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $countryLocations;
    }

    public function getProvinceDistricts($params)
    {
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $districtQuery = $sql->select()->from(array('l_d' => 'location_details'))->where("l_d.parent_location !='0'");
        if (isset($params['province']) && !empty($params['province'])) {
            $districtQuery = $districtQuery->where('l_d.parent_location IN (' . implode(',', $params['province']) . ')');
        }
        $districtQueryStr = $sql->buildSqlString($districtQuery);
        return $dbAdapter->query($districtQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function saveRegion($region)
    {
        $container = new Container('alert');
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $locationDetailsDb = $this->sm->get('LocationDetailsTable');
            $globalDb = $this->sm->get('GlobalTable');
            $response = $locationDetailsDb->saveRegion($region);
            $labelVal = $globalDb->getGlobalValue('region');
            $regionLabel = (isset($labelVal) && trim($labelVal) != '') ? ucwords($labelVal) : 'Region';
            if ($response > 0) {
                $adapter->commit();
                //<-- Event log
                $id = (int) $region->location_id;
                if ($id == 0) {
                    $subject = $response;
                    $eventType = 'region-add';
                    $action = 'added a new region ' . $region->location_name;
                    $resourceName = 'Region';
                    $eventLogDb = $this->sm->get('EventLogTable');
                    $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                    $container->alertMsg = $regionLabel . ' added successfully';
                } else {
                    $subject = $response;
                    $eventType = 'region-update';
                    $action = 'updates a region ' . $region->location_name;
                    $resourceName = 'Region';
                    $eventLogDb = $this->sm->get('EventLogTable');
                    $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                    $container->alertMsg = $regionLabel . ' updated successfully';
                }
            } else {
                $container->alertMsg = 'Oops..';
            }
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function saveDistrict($district)
    {
        $container = new Container('alert');
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $locationDetailsDb = $this->sm->get('LocationDetailsTable');
            $globalDb = $this->sm->get('GlobalTable');
            $response = $locationDetailsDb->saveDistrict($district);
            $labelVal = $globalDb->getGlobalValue('districts');
            $districtLabel = (isset($labelVal) && trim($labelVal) != '') ? ucwords($labelVal) : 'District';
            if ($response > 0) {
                $adapter->commit();
                //<-- Event log
                $id = (int) $district->location_id;
                if ($id == 0) {
                    $subject = $response;
                    $eventType = 'district-add';
                    $action = 'added a new district ' . $district->location_name;
                    $resourceName = 'District';
                    $eventLogDb = $this->sm->get('EventLogTable');
                    $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                    $container->alertMsg = $districtLabel . ' added successfully';
                } else {
                    $subject = $response;
                    $eventType = 'district-update';
                    $action = 'updates a district ' . $district->location_name;
                    $resourceName = 'District';
                    $eventLogDb = $this->sm->get('EventLogTable');
                    $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                    $container->alertMsg = $districtLabel . ' updated successfully';
                }
            } else {
                $container->alertMsg = 'Oops..';
            }
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getLocation($id)
    {
        $locationDetailsDb = $this->sm->get('LocationDetailsTable');
        return $locationDetailsDb->getLocation($id);
    }

    public function deleteLocation($id)
    {
        $locationDetailsDb = $this->sm->get('LocationDetailsTable');
        return $locationDetailsDb->delete(array('location_id' => $id));
    }

    public function sendCertificateMail($params)
    {
        try {
            $config = new \Laminas\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');

            // Setup SMTP transport using LOGIN authentication
            $transport = new SmtpTransport();
            $options = new SmtpOptions(array(
                'host' => $configResult["email"]["host"],
                'port' => $configResult["email"]["config"]["port"],
                'connection_class' => $configResult["email"]["config"]["auth"],
                'connection_config' => array(
                    'username' => $configResult["email"]["config"]["username"],
                    'password' => $configResult["email"]["config"]["password"],
                    'ssl' => $configResult["email"]["config"]["ssl"]
                ),
            ));
            $transport->setOptions($options);
            $alertMail = new Mail\Message();

            $fromEmail = 'rtqiicertification@gmail.com';
            $fromFullName = 'RTCQI PERSONNEL CERTIFICATION PROGRAM';
            $subject = $params['subject'];
            $alertMail->addFrom($fromEmail, $fromFullName);
            $alertMail->addReplyTo($fromEmail, $fromFullName);

            $toArray = explode(",", $params['to_email']);
            $counter = count($toArray);
            for ($e = 0; $e < $counter; $e++) {
                $alertMail->addTo($toArray[$e]);
            }

            if (isset($params['cc']) && trim($params['cc']) != "") {
                $alertMail->addCc($params['cc']);
            }

            if (isset($params['bcc']) && trim($params['bcc']) != "") {
                $alertMail->addBcc($params['bcc']);
            }

            $alertMail->setSubject($subject);

            $html = new MimePart($params['message']);
            $html->type = "text/html";

            $body = new MimeMessage();
            $body->setParts(array($html));

            if (isset($params['attachedfile']) && trim($params['attachedfile']) != '') {
                $fileArray = explode('##', $params['attachedfile']);
                $dirPath = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileArray[0];
                if (is_dir($dirPath)) {
                    $dh  = opendir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileArray[0]);
                    while (($filename = readdir($dh)) !== false) {
                        if ($filename != "." && $filename != "..") {
                            $fileContent = fopen($dirPath . DIRECTORY_SEPARATOR . $fileArray[1], 'r');
                            $attachment = new MimePart($fileContent);
                            $attachment->filename    = $filename;
                            $attachment->type        = Mime::TYPE_OCTETSTREAM;
                            $attachment->encoding    = Mime::ENCODING_BASE64;
                            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                            $body->addPart($attachment);
                        }
                    }
                    closedir($dh);
                    //$this->removeDirectory($dirPath);
                }
            }

            $alertMail->setBody($body);
            $result = $transport->send($alertMail);
            if ($result || $result == NULL) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            error_log('whoops! Something went wrong in send-certificate-reminder-mail.');
        }
    }

    public function getTestConfig($parameters)
    {
        $configDb = $this->sm->get('TestConfigTable');
        return $configDb->fetchTestConfig($parameters);
    }

    public function updateTestConfig($params)
    {
        $container = new Container('alert');
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $testConfigDb = $this->sm->get('TestConfigTable');
            $updateRes = $testConfigDb->updateTestConfigDetails($params);
            if ($updateRes) {
                $subject = '';
                $eventType = 'test-config-update';
                $action = 'updated test config details';
                $resourceName = 'test-config';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                $adapter->commit();
                $container->alertMsg = "Test Config Updated Successfully.";
            }
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getTestConfigDetails()
    {
        $testConfigDb = $this->sm->get('TestConfigTable');
        return $testConfigDb->fetchTestConfigDetails();
    }

    public function getTestConfigEditDetails()
    {
        $testConfigDb = $this->sm->get('TestConfigTable');
        return $testConfigDb->fetchTestConfigEdit();
    }

    public function getTestValue($globalName)
    {
        $testConfigDb = $this->sm->get('TestConfigTable');
        return $testConfigDb->fetchTestValue($globalName);
    }

    public function getMailTemplateByPurpose($purpose)
    {
        $mailTemplateDb = $this->sm->get('MailTemplateTable');
        return $mailTemplateDb->fetchMailTemplateByPurpose($purpose);
    }

    public function getHeaderText()
    {
        $configDb = $this->sm->get('GlobalTable');
        return $configDb->fetchHeaderText();
    }

    public static function getRandomArray($min, $max)
    {
        $valArray = array();
        foreach (range($min, $max) as $val) {
            $indexVal = $min;
            do {
                $indexVal = rand($min, $max);
            } while (in_array($indexVal, $valArray));
            $valArray[] = $indexVal;
        }
        return $valArray;
    }

    public function sendContactMail($params)
    {
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        try {
            $config = new \Laminas\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');

            // Setup SMTP transport using LOGIN authentication
            $transport = new SmtpTransport();
            $options = new SmtpOptions(array(
                'host' => $configResult["email"]["host"],
                'port' => $configResult["email"]["config"]["port"],
                'connection_class' => $configResult["email"]["config"]["auth"],
                'connection_config' => array(
                    'username' => $configResult["email"]["config"]["username"],
                    'password' => $configResult["email"]["config"]["password"],
                    'ssl' => $configResult["email"]["config"]["ssl"]
                ),
            ));

            $sQuery = $sql->select()->from('global_config')->where(array('global_name' => 'feedback-send-mailid'));
            $sQueryStr = $sql->buildSqlString($sQuery);
            $configValues = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            $sendmail = $configValues ? $configValues['global_value'] : $configResult["email"]["config"]["toMail"];
            $transport->setOptions($options);
            $alertMail = new Mail\Message();

            $fromEmail = $params['email'];
            $fromFullName = $params['name'];
            $subject = $params['subject'];
            $alertMail->addFrom($fromEmail, $fromFullName);
            $alertMail->addReplyTo($fromEmail, $fromFullName);

            $toArray = explode(",", $sendmail);
            $counter = count($toArray);
            for ($e = 0; $e < $counter; $e++) {
                $alertMail->addTo($toArray[$e]);
            }

            if (isset($params['cc']) && trim($params['cc']) != "") {
                $alertMail->addCc($params['cc']);
            }

            if (isset($params['bcc']) && trim($params['bcc']) != "") {
                $alertMail->addBcc($params['bcc']);
            }

            $alertMail->setSubject($subject);

            $html = new MimePart($params['message']);
            $html->type = "text/html";

            $body = new MimeMessage();
            $body->setParts(array($html));

            if (isset($params['attachedfile']) && trim($params['attachedfile']) != '') {
                $fileArray = explode('##', $params['attachedfile']);
                $dirPath = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileArray[0];
                if (is_dir($dirPath)) {
                    $dh  = opendir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileArray[0]);
                    while (($filename = readdir($dh)) !== false) {
                        if ($filename != "." && $filename != "..") {
                            $fileContent = fopen($dirPath . DIRECTORY_SEPARATOR . $fileArray[1], 'r');
                            $attachment = new MimePart($fileContent);
                            $attachment->filename    = $filename;
                            $attachment->type        = Mime::TYPE_OCTETSTREAM;
                            $attachment->encoding    = Mime::ENCODING_BASE64;
                            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                            $body->addPart($attachment);
                        }
                    }
                    closedir($dh);
                }
            }

            $feedbackmailDb = $this->sm->get('FeedbackMailTable');
            $feedbackmailDb->insertFeedbackMailDetails($params['name'],  $params['email'],  $params['subject'],  $params['message']);

            $alertMail->setBody($body);

            if ($transport->send($alertMail)) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            error_log('whoops! Something went wrong in send-certificate-reminder-mail.');
        }
    }
    public function sendMultipleCertificationMail($parameters)
    {
        $mailTemplateDb = $this->sm->get('MailTemplateTable');
        $mailTemplateDetails = $mailTemplateDb->fetchMailTemplateByPurpose($parameters['mailPurpose']);
        $subject = '';
        $message = '';

        $fromName = $mailTemplateDetails['from_name'];
        $fromMail = $mailTemplateDetails['mail_from'];
        $subject = $mailTemplateDetails['mail_subject'];
        $message = $mailTemplateDetails['mail_content'];
        $toMail = $parameters['to_mail'];
        $cc = '';
        $bcc = '';
        $subject .= "$$" . $mailTemplateDetails['mail_purpose'];
        $message .= "$$" . $parameters['uniqueIds'] . "$$" . $parameters['type_recipient'] . "$$" . $parameters['name_recipient'];
        $tempmailDb = $this->sm->get('TempMailTable');
        return $tempmailDb->insertTempMailDetails($toMail, $subject, $message, $fromMail, $fromName, $cc, $bcc);
    }
}

<?php

namespace Application\Service;

use DateTimeImmutable;
use Laminas\Session\Container;
use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Mime;
use Laminas\Mail\Transport\Sendmail;
use Laminas\Db\Sql\Expression;

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

    public function getCurrentWeekStartAndEndDate()
    {
        $cDate = date('Y-m-d');
        $date = new \DateTime($cDate);
        $week = $date->format("W");
        $year = date('Y');
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $ret['weekStart'] = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $ret['weekEnd'] = $dto->format('Y-m-d');
        return $ret;
    }

    public static function generateRandomString($length = 8)
    {
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $number = random_int(0, 36);
            $character = base_convert($number, 10, 36);
            $randomString .= $character;
        }
        return $randomString;
    }


    public static function verifyIfDateValid($date): bool
    {
        $date = trim($date);
        $response = false;

        if (empty($date) || 'undefined' === $date || 'null' === $date) {
            $response = false;
        } else {
            try {
                $dateTime = new DateTimeImmutable($date);
                $errors = DateTimeImmutable::getLastErrors();
                if (empty($dateTime) || $dateTime === false || !empty($errors['warning_count']) || !empty($errors['error_count'])) {
                    //error_log("Invalid date :: $date");
                    $response = false;
                } else {
                    $response = true;
                }
            } catch (Exception $e) {
                //error_log("Invalid date :: $date :: " . $e->getMessage());
                $response = false;
            }
        }

        return $response;
    }

    // Returns the given date in Y-m-d format
    public static function isoDateFormat($date, $includeTime = false)
    {
        $date = trim($date);
        if (false === self::verifyIfDateValid($date)) {
            return null;
        } else {
            $format = "Y-m-d";
            if ($includeTime === true) {
                $format = $format . " H:i:s";
            }
            return (new DateTimeImmutable($date))->format($format);
        }
    }


    // Returns the given date in d-M-Y format
    // (with or without time depending on the $includeTime parameter)
    public static function humanReadableDateFormat($date, $includeTime = false, $format = "d-M-Y")
    {
        $date = trim($date);
        if (false === self::verifyIfDateValid($date)) {
            return null;
        } else {

            if ($includeTime === true) {
                $format = $format . " H:i";
            }

            return (new DateTimeImmutable($date))->format($format);
        }
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

    public function dateFormat($date)
    {
        if (!isset($date) || $date == null || $date == "" || $date == "0000-00-00") {
            return "0000-00-00";
        } else {
            $dateArray = explode('-', $date);
            if (sizeof($dateArray) == 0) {
                return;
            }
            $newDate = $dateArray[2] . "-";

            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $mon = 1;
            $mon += array_search(ucfirst($dateArray[1]), $monthsArray);

            if (strlen($mon) == 1) {
                $mon = "0" . $mon;
            }
            return $newDate .= $mon . "-" . $dateArray[0];
        }
    }


    public function viewDateFormat($date)
    {

        if ($date == null || $date == "" || $date == "0000-00-00") {
            return "";
        } else {
            $dateArray = explode('-', $date);
            $newDate = $dateArray[2] . "-";

            $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
            $mon = $monthsArray[$dateArray[1] - 1];

            return $newDate .= $mon . "-" . $dateArray[0];
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
                    $subject = $result['subject'];

                    $alertMail->addFrom($fromEmail, $fromFullName);
                    $alertMail->addReplyTo($fromEmail, $fromFullName);
                    $alertMail->addTo($result['to_email']);

                    if (isset($result['cc']) && trim($result['cc']) != "") {
                        $alertMail->addCc($result['cc']);
                    }

                    if (isset($result['bcc']) && trim($result['bcc']) != "") {
                        $alertMail->addBcc($result['bcc']);
                    }

                    $alertMail->setSubject($subject);

                    $html = new MimePart($result['message']);
                    $html->type = "text/html";

                    $body = new MimeMessage();
                    $body->setParts(array($html));

                    $dirPath = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "email" . DIRECTORY_SEPARATOR . $id;
                    if (is_dir($dirPath)) {
                        $dh  = opendir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "email" . DIRECTORY_SEPARATOR . $id);
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

                    $alertMail->setBody($body);

                    $transport->send($alertMail);
                    $tempMailDb->deleteTempMail($id);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            error_log('whoops! Something went wrong in send-mail.');
        }
    }

    public function sendAuditMail()
    {
        try {
            $auditMailDb = $this->sm->get('AuditMailTable');
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
            $mailQuery = $sql->select()->from(array('a_mail' => 'audit_mails'))->where("status='pending'")->limit($limit);
            $mailQueryStr = $sql->buildSqlString($mailQuery);
            $mailResult = $dbAdapter->query($mailQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($mailResult) > 0) {
                foreach ($mailResult as $result) {
                    $alertMail = new Mail\Message();
                    $id = $result['mail_id'];
                    $auditMailDb->updateInitialAuditMailStatus($id);

                    $fromEmail = $result['from_mail'];
                    $fromFullName = $result['from_full_name'];
                    $subject = $result['subject'];

                    $alertMail->addFrom($fromEmail, $fromFullName);
                    $alertMail->addReplyTo($fromEmail, $fromFullName);
                    $alertMail->addTo($result['to_email']);

                    if (isset($result['cc']) && trim($result['cc']) != "") {
                        $alertMail->addCc($result['cc']);
                    }

                    if (isset($result['bcc']) && trim($result['bcc']) != "") {
                        $alertMail->addBcc($result['bcc']);
                    }

                    $alertMail->setSubject($subject);

                    $html = new MimePart($result['message']);
                    $html->type = "text/html";

                    $body = new MimeMessage();
                    $body->setParts(array($html));

                    $dirPath = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email" . DIRECTORY_SEPARATOR . $id;
                    if (is_dir($dirPath)) {
                        $dh  = opendir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email" . DIRECTORY_SEPARATOR . $id);
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

                    $alertMail->setBody($body);

                    if ($transport->send($alertMail)) {
                        $auditMailDb->updateAuditMailStatus($id);
                    }
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            error_log('whoops! Something went wrong in send-audit-mail.');
        }
    }

    public static function getDate($timezone = 'Asia/Calcutta')
    {
        $date = new \DateTime(date('Y-m-d'), new \DateTimeZone($timezone));
        return $date->format('Y-m-d');
    }

    public static function getDateTime($timezone = 'Asia/Calcutta')
    {
        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone($timezone));
        return $date->format('Y-m-d H:i:s');
    }

    public static function getCurrentTime($timezone = 'Asia/Calcutta')
    {
        $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone($timezone));
        return $date->format('H:i');
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
        $data = count($result);
        return $data;
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
            $fromFullName = 'RTQII PERSONNEL CERTIFICATION PROGRAM';
            $subject = $params['subject'];
            $alertMail->addFrom($fromEmail, $fromFullName);
            $alertMail->addReplyTo($fromEmail, $fromFullName);

            $toArray = explode(",", $params['to_email']);
            for ($e = 0; $e < count($toArray); $e++) {
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

    public function getRandomArray($min, $max)
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
            if ($configValues) {
                $sendmail = $configValues['global_value'];
            } else {
                $sendmail = $configResult["email"]["config"]["toMail"];
            }
            $transport->setOptions($options);
            $alertMail = new Mail\Message();

            $fromEmail = $params['email'];
            $fromFullName = $params['name'];
            $subject = $params['subject'];
            $alertMail->addFrom($fromEmail, $fromFullName);
            $alertMail->addReplyTo($fromEmail, $fromFullName);

            $toArray = explode(",", $sendmail);
            for ($e = 0; $e < count($toArray); $e++) {
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
}

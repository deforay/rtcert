<?php

namespace Application\Service;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Adapter\Adapter;
use Laminas\Session\Container;
use \Application\Model\TestOptionsTable;
use \Application\Model\TestSectionTable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionService
{

    public $sm = null;

    public function __construct($sm)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public function addQuestionData($params)
    {

        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $QuestionDb = $this->sm->get('QuestionTable');
            $response = $QuestionDb->addQuestion($params);

            if ($response > 0) {
                $subject = '';
                $eventType = 'test-question-add';
                $action = 'added test question details';
                $resourceName = 'test-question';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Online tests added successfully';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getQuestionList($parameters)
    {
        $QuestionDb = $this->sm->get('QuestionTable');
        $acl = $this->sm->get('AppAcl');
        return $QuestionDb->fetchQuestionList($parameters, $acl);
    }

    public function getQuestionsListById($questionId)
    {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchQuestionsListById($questionId);
    }
    //Options List Service
    public function getOptionListById($questionId)
    {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchOptionListById($questionId);
    }

    public function updateQuestionDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $QuestionDb = $this->sm->get('QuestionTable');
            $QuestionId = $QuestionDb->updateQuestion($params);

            if ($QuestionId > 0) {
                $subject = '';
                $eventType = 'test-question-update';
                $action = 'updated test question details';
                $resourceName = 'test-question';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Online tests updated successfully';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getQuestionAllList()
    {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchQuestionAllList();
    }
    public function getPostQuestionList()
    {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchPostQuestionList();
    }

    public function getFrequencyQuestionList($parameters)
    {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchFrequencyQuestionList($parameters);
    }

    public function exportQuestionDetails()
    {
        try {
            $querycontainer = new Container('query');
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $sQueryStr = $sql->buildSqlString($querycontainer->questionPreQueryStr);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            $output = array();
            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = ucfirst($aRow['question']);
                    $row[] = $aRow['preQCount'];
                    $row[] = $aRow['preRCount'];
                    $output[] = $row;
                }
                $styleArray = array(
                    'font' => array(
                        'bold' => true,
                        'size' => 12,
                    ),
                    'alignment' => array(
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ),
                    'borders' => array(
                        'outline' => array(
                            'style' => Border::BORDER_THIN,
                        ),
                    )
                );

                $borderStyle = array(
                    'alignment' => array(
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ),
                    'borders' => array(
                        'outline' => array(
                            'style' => Border::BORDER_THIN,
                        ),
                    )
                );

                $sheet->setCellValue('A1', html_entity_decode('Questions', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('B1', html_entity_decode('No.of times shown to people test', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('C1', html_entity_decode('No. of times people got it right in test', ENT_QUOTES, 'UTF-8'));

                $sheet->getStyle('A1')->applyFromArray($styleArray);
                $sheet->getStyle('B1')->applyFromArray($styleArray);
                $sheet->getStyle('C1')->applyFromArray($styleArray);

                foreach ($output as $rowNo => $rowData) {
                    $colNo = 1;
                    $rRowCount = $rowNo + 2;
                    foreach ($rowData as $field => $value) {
                        if (!isset($value)) {
                            $value = "";
                        }
                        if (is_numeric($value)) {
                            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
                        } else {
                            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode((string) $value));
                        }
                        $cellName = Coordinate::stringFromColumnIndex($colNo) . $rRowCount;
                        $sheet->getStyle($cellName)->applyFromArray($borderStyle);
                        $sheet->getDefaultRowDimension()->setRowHeight(18);
                        $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                        $sheet->getStyle($cellName)->getAlignment()->setWrapText(true);
                        $colNo++;
                    }
                }

                $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);
                $filename = 'online-test-question-frequency-(' . date('d-M-Y-H-i-s') . ').xls';
                $directoryName = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'question-frequency';

                if (!is_dir($directoryName)) {
                    mkdir($directoryName, 0755);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'question-frequency' . DIRECTORY_SEPARATOR . $filename);
                } else {
                    chmod($directoryName, 0777);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'question-frequency' . DIRECTORY_SEPARATOR . $filename);
                }
                return $filename;
            } else {
                return "not-found";
            }
        } catch (Exception $exc) {
            return "";
            error_log("EXPORT-QUESTION-FREQUENCY-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getUserTestList($params)
    {
        $db = $this->sm->get('TestsTable');
        $acl = $this->sm->get('AppAcl');
        return $db->fetchUserTestList($params, $acl);
    }



    public function uploadTestQuestion($fileName)
    {

        $dbAdapter         = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql               = new Sql($dbAdapter);
        $loginContainer    = new Container('credo');
        $QuestionDb        = $this->sm->get('QuestionTable');
        $TestSectionDb        = new TestSectionTable($dbAdapter);
        $TestOptionsDb        = new TestOptionsTable($dbAdapter);
        $response = array();

        $allowedExtensions = array('xls', 'xlsx', 'csv');
        $fileName          = preg_replace('/[^A-Za-z0-9.]/', '-', $_FILES['question_excel']['name']);
        $fileName          = str_replace(" ", "-", $fileName);
        $ranNumber         = str_pad(rand(0, pow(10, 6) - 1), 6, '0', STR_PAD_LEFT);
        $extension         = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileName          = $ranNumber . "." . $extension;
        $container = new Container('alert');

        if (in_array($extension, $allowedExtensions)) {
            $uploadPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'test-questions';
            if (!file_exists($uploadPath) && !is_dir($uploadPath)) {
                mkdir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "test-questions");
            }

            if (!file_exists($uploadPath . DIRECTORY_SEPARATOR . $fileName) && move_uploaded_file($_FILES['question_excel']['tmp_name'], $uploadPath . DIRECTORY_SEPARATOR . $fileName)) {
                $objPHPExcel = \PHPExcel_IOFactory::load($uploadPath . DIRECTORY_SEPARATOR . $fileName);
                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                $count = count($sheetData);
                for ($i = 2; $i <= $count; ++$i) {
                    $sectionName = $sheetData[$i]['C'];
                    $sectionVals = strtolower($sectionName);
                    $sectionSlug = str_replace(" ", "-", $sectionVals);
                    $testsectionVal = $TestSectionDb->select(array('section_slug' => $sectionSlug))->current();
                    if ($testsectionVal) {
                        $sectionId = $testsectionVal->section_id;
                    } else {
                        $sectionData = array(
                            'section_name' => $sectionName,
                            'section_slug' => $sectionSlug,
                            'section_description'  => '',
                            'status'  => 'active',
                        );
                        $TestSectionDb->insert($sectionData);
                        $sectionId = $TestSectionDb->lastInsertValue;
                    }

                    $QuestionVAl = $QuestionDb->select(array('question' => $sheetData[$i]['B']))->current();
                    if ($sheetData[$i]['A'] == '' || $sheetData[$i]['B'] == '' || $sheetData[$i]['C'] == '') {
                        $response['data']['mandatory'][] = array(
                            'question_code' => $sheetData[$i]['A'],
                            'question'      => $sheetData[$i]['B'],
                            'section'       => $sheetData[$i]['C']
                        );
                        $container->alertMsg = 'Some questions from the excel file were not imported. Please check the highlighted fields below to ensure the questions not duplicated.';
                    } elseif (!$QuestionVAl) {
                        $data = array(
                            'question_code' => $sheetData[$i]['A'],
                            'question' => $sheetData[$i]['B'],
                            'section'  => $sectionId,
                            'status'   => 'active',
                        );
                        $QuestionDb->insert($data);
                        $QuestionId = $QuestionDb->lastInsertValue;
                        $correctOption = strtoupper($sheetData[$i]['D']);
                        $response['data']['imported'][] = array(
                            'question_code' => $sheetData[$i]['A'],
                            'question'      => $sheetData[$i]['B'],
                            'section'       => $sheetData[$i]['C']
                        );
                        for ($j = 1; $j <= 4; ++$j) {
                            if ($j == 1) {
                                $option = "A";
                                $optionVal = "A. " . $sheetData[$i]['E'];
                            }
                            if ($j == 2) {
                                $option = "B";
                                $optionVal = "B. " . $sheetData[$i]['F'];
                            }
                            if ($j == 3) {
                                $option = "C";
                                $optionVal = "C. " . $sheetData[$i]['G'];
                            }
                            if ($j == 4) {
                                $option = "D";
                                $optionVal = "D. " . $sheetData[$i]['H'];
                            }

                            $TestOptionsVAl = $TestOptionsDb->select(array('option' => $optionVal))->current();
                            if ($TestOptionsVAl) {
                                $OptionId = $TestOptionsVAl->correct_option;
                            } else {
                                $optiondata = array(
                                    'question' => $QuestionId,
                                    'option' => $optionVal,
                                    'status'   => 'active',
                                );
                                $TestOptionsDb->insert($optiondata);
                                $OptionId = $TestOptionsDb->lastInsertValue;
                            }
                            if ($option == $correctOption) {
                                $QuestionDb->update(array(
                                    'correct_option'          => $OptionId,
                                    'correct_option_text'          => $optionVal
                                ), array("question_id" => $QuestionId));
                            }
                        }
                        $container->alertMsg = 'Question details added successfully';
                    } else {
                        $response['data']['duplicate'][] = array(
                            'question_code' => $sheetData[$i]['A'],
                            'question'      => $sheetData[$i]['B'],
                            'section'       => $sheetData[$i]['C']
                        );
                        $container->alertMsg = 'Some questions from the excel file were not imported. Please check the highlighted fields below to ensure the questions not duplicated.';
                    }
                }
                unlink($uploadPath . DIRECTORY_SEPARATOR . $fileName);
            }
        }
        return $response;
    }
}

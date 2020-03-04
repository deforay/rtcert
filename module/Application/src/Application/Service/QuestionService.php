<?php
namespace Application\Service;

use PHPExcel;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;

class QuestionService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function addQuestionData($params){

        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
           $QuestionDb = $this->sm->get('QuestionTable');
           $response = $QuestionDb->addQuestion($params);

           if($response > 0){
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
       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }
    }

    public function getQuestionList($parameters) {
        $QuestionDb = $this->sm->get('QuestionTable');
        $acl = $this->sm->get('AppAcl');
        return $QuestionDb->fetchQuestionList($parameters,$acl);
    }

    public function getQuestionsListById($questionId) {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchQuestionsListById($questionId);
    }
    //Options List Service
    public function getOptionListById($questionId) {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchOptionListById($questionId);
    }

    public function updateQuestionDetails($params){        
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
       try {
            $QuestionDb = $this->sm->get('QuestionTable');
            $QuestionId = $QuestionDb->updateQuestion($params);

            if($QuestionId > 0){
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

       }
       catch (Exception $exc) {
           $adapter->rollBack();
           error_log($exc->getMessage());
           error_log($exc->getTraceAsString());
       }

    }

    public function getQuestionAllList() {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchQuestionAllList();
    }
    public function getPostQuestionList() {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchPostQuestionList();
    }

    public function getFrequencyQuestionList($parameters) {
        $QuestionDb = $this->sm->get('QuestionTable');
        return $QuestionDb->fetchFrequencyQuestionList($parameters);
    }

    public function exportQuestionDetails(){
        try{
            $querycontainer = new Container('query');
            $common = new \Application\Service\CommonService();
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $sQueryStr = $sql->getSqlStringForSqlObject($querycontainer->questionPreQueryStr);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            
            $output= array();
            if(count($sResult) > 0) {
                $cQueryStr = $sql->getSqlStringForSqlObject($querycontainer->questionPreQueryStr);
                $cResult = $dbAdapter->query($cQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(count($cResult) > 0) {
                    $common = new CommonService();
                    foreach($cResult as $aRow) {
                        $row = array();
                        $row[] = ucfirst($aRow['question']);
                        $row[] = $aRow['preQCount'];
                        $row[] = $aRow['preRCount'];
                        $sQuery2 = $sql->select()->from(array('q' => 'questions'))->columns(array('question_id','question','correct_option'))
                              ->join(array('post' => 'posttest_questions'), 'q.question_id = post.question_id', array('postQCount' => new Expression('COUNT(post_test_id)'),"postRCount" => new Expression("SUM(CASE WHEN (post.score  = 1) THEN 1 ELSE 0 END)")),'left')
                              ->join(array('t' => 'tests'), 'post.test_id = t.test_id', array('posttest_start_datetime','pretest_start_datetime'))
                              ->group("q.question_id");
                        if(isset($querycontainer->postTestDateRange) && $querycontainer->postTestDateRange!='')
                        {
                            $postDate = explode(" to ",$querycontainer->postTestDateRange);
                            $postStartDate = date('Y-m-d', strtotime($postDate[0]));
                            $postEndDate = date('Y-m-d', strtotime($postDate[1]));
                            $sQuery2 = $sQuery2->where(array("DATE(t.posttest_start_datetime) >='" . $postStartDate . "'", "DATE(t.posttest_start_datetime) <='" . $postEndDate . "'"));
                        }else if(isset($querycontainer->preTestDateRange) && $querycontainer->preTestDateRange!=''){
                            $preDate = explode(" to ",$querycontainer->preTestDateRange);
                            $preStartDate = date('Y-m-d', strtotime($preDate[0]));
                            $preEndDate = date('Y-m-d', strtotime($preDate[1]));
                            $sQuery2 = $sQuery2->where(array("DATE(t.pretest_start_datetime) >='" . $preStartDate . "'", "DATE(t.pretest_start_datetime) <='" . $preEndDate . "'"));

                            $sQuery2 = $sQuery2->where(array('q.question_id' => $aRow['question_id']));
                        }else{
                            $sQuery2 = $sQuery2->where(array('q.question_id' => $aRow['question_id']));
                        }
                        $sQueryStr2 = $sql->getSqlStringForSqlObject($sQuery2);
                        $result = $dbAdapter->query($sQueryStr2, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                        if($result['postQCount'] != ""){

                            $row[] = $result['postQCount'];
                            $row[] = $result['postRCount'];
                          }else{
                            $row[] = '0';
                            $row[] = '0';
                          }
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
                
                $sheet->setCellValue('A1', html_entity_decode('Questions', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('B1', html_entity_decode('No.of times shown to participants pre test', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('C1', html_entity_decode('No. of times people got it right in pre test', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D1', html_entity_decode('No.of times shown to participants post test	', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E1', html_entity_decode('No. of times people got it right in post test', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);

                $sheet->getStyle('A1')->applyFromArray($styleArray);
                $sheet->getStyle('B1')->applyFromArray($styleArray);
                $sheet->getStyle('C1')->applyFromArray($styleArray);
                $sheet->getStyle('D1')->applyFromArray($styleArray);
                $sheet->getStyle('E1')->applyFromArray($styleArray);

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
                $filename = 'question-frequency' . date('d-M-Y-H-i-s') . '.xls';
                $directoryName = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'question-frequency';

                if(!is_dir($directoryName)){
                    mkdir($directoryName, 0755);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'question-frequency' . DIRECTORY_SEPARATOR . $filename);

                }else{
                    chmod($directoryName, 0777);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'question-frequency' . DIRECTORY_SEPARATOR . $filename);
                }
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

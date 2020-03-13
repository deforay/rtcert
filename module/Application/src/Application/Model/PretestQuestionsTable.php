<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class PretestQuestionsTable extends AbstractTableGateway {

    protected $table = 'pretest_questions';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function savePreTestData($params){
       $logincontainer = new Container('credo');
        $testDb = new \Application\Model\TestsTable($this->adapter);
        $questionDb = new \Application\Model\QuestionTable($this->adapter);
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        if(isset($params['questionId']) && trim($params['questionId']) != ""){
            //first check allready start datetime
            $testResult = $testDb->getTestDataByUserId($logincontainer->userId);
            if(($testResult['testStatus']['pretest_start_datetime']=='0000-00-00 00:00:00') || ($testResult['testStatus']['pretest_start_datetime']=='NULL')){
                $data = array(
                    'pretest_start_datetime' => date("Y-m-d H:i:s", time()),
                    'pre_test_status' => 'not completed'
                );
                $testDb->update($data,array('test_id'=>$testResult['testStatus']['test_id']));
            }
            $lastInsertedTestsId = $testResult['testStatus']['test_id'];

            $questionData = $questionDb->fetchQuestionsListById(base64_decode($params['questionId']));

            $correctOptAry = explode(",",$questionData['correct_option']);
            $aryInter = array_intersect($params['optionId'],$correctOptAry);
            if(count($aryInter) == count($params['optionId'])){
                $score = 1;
            }else{
                $score = 0;
            }

            $preData = array(
                'question_text' => $questionData['question'],
                'response_id' => implode(",",$params['optionId']),
                'response_text' => json_encode($params['optionText']),
                'score' => $score,
            );
            $this->update($preData,array('pre_test_id'=>base64_decode($params['pretestId'])));

            $sQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('pre_test_id'))
                        ->join(array('t' => 'tests'), 't.test_id = pt.test_id', array('pre_test_score'))
                        ->where(array('t.user_id' => $logincontainer->userId))
                        ->where('pt.response_id IS NULL');
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $questionResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if(!$questionResult['pre_test_id']){
                $passPercent = $testConfigDb->fetchTestValue('passing-percentage');

                $sQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('score' => new \Zend\Db\Sql\Expression('SUM(score)')))
                                        ->where(array('pt.test_id'=>$lastInsertedTestsId));
                $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                $preTestResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                $preScore = ($preTestResult['score'] / $preTestResult['totalQuestion']);
                $preTotal = round($preScore * 100);
                $data = array(
                    'pretest_end_datetime' => date("Y-m-d H:i:s", time()),
                    'pre_test_status' => "completed",
                    'pre_test_score' => $preTestResult['score']
                );
                if($preTotal>=$passPercent){
                    $data['user_test_status'] = 'pass';
                }else{
                    $data['user_test_status'] = 'fail';
                }
                $testDb->update($data,array('test_id'=>$testResult['testStatus']['test_id']));
            }
        }
        return $lastInsertedTestsId;
    }

    public function fetchPreTestAllDetails(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('pretest_questions');
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $sResult;
    }

    public function fetchPreResultDetails(){
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $testDb = new \Application\Model\TestsTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');

        //get last test id
        $testResult = $testDb->getTestDataByUserId($logincontainer->userId);
        $sQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('totalCount' => new \Zend\Db\Sql\Expression('COUNT(pre_test_id)')))
                                ->join(array('t' => 'tests'), 't.test_id = pt.test_id', array('pre_test_score'))
                                ->where(array('t.user_id' => $logincontainer->userId,'t.test_id'=>$testResult['testStatus']['test_id']))
                                ->order('pt.test_id DESC');
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        // die($sQueryStr);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $maxQuestion = $testConfigDb->fetchTestConfigDetails();
        $sResult['maxQuestion'] = $maxQuestion[1]['test_config_value'];
        return $sResult;
    }
}
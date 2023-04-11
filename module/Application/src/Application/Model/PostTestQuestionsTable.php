<?php
namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Application\Service\CommonService;

class PostTestQuestionsTable extends AbstractTableGateway {

    protected $table = 'posttest_questions';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    public function addPostTestData($params){
        $common = new CommonService();
        $logincontainer = new Container('credo');
        $testDb = new \Application\Model\TestsTable($this->adapter);
        $questionDb = new \Application\Model\QuestionTable($this->adapter);
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        if(isset($params['questionId']) && trim($params['questionId']) != ""){
            //first check allready start datetime
            $testResult = $testDb->getTestDataByUserId($logincontainer->userId);
            if(($testResult['testStatus']['posttest_start_datetime']=='0000-00-00 00:00:00') || ($testResult['testStatus']['posttest_start_datetime']==NULL)){
                $data = array(
                    'posttest_start_datetime' => date("Y-m-d H:i:s", time()),
                    'post_test_status' => 'not completed'
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

            $postData = array(
                'question_text' => $questionData['question'],
                'response_id' => implode(",",$params['optionId']),
                'response_text' => json_encode($params['optionText']),
                'score' => $score,
            );
            $this->update($postData,array('post_test_id'=>base64_decode($params['postTestId'])));

            $sQuery = $sql->select()->from(array('ptq' => 'posttest_questions'))->columns(array('post_test_id'))
                                    ->join(array('t' => 'tests'), 't.test_id = ptq.test_id', array('pre_test_score'))
                                    ->where(array('t.user_id' => $logincontainer->userId))
                                    ->where('ptq.response_id IS NULL');
            $sQueryStr = $sql->buildSqlString($sQuery);
            $questionResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if(!$questionResult['post_test_id']){
                $passPercent = $testConfigDb->getTestConfigValue('passing-percentage');
                $sQuery = $sql->select()->from(array('ptq' => 'posttest_questions'))->columns(array('score' => new \Laminas\Db\Sql\Expression('SUM(score)'),'totalQuestion'=>new \Laminas\Db\Sql\Expression('COUNT(*)')))
                                    ->where(array('ptq.test_id'=>$lastInsertedTestsId));
                $sQueryStr = $sql->buildSqlString($sQuery);
                $postTestResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                $postScore = ($postTestResult['score'] / $postTestResult['totalQuestion']);
                $postTotal = round($postScore * 100);
                
                $data = array(
                    'posttest_end_datetime' => date("Y-m-d H:i:s", time()),
                    'post_test_status' => "completed",
                    'post_test_score' => $postTestResult['score']
                );
                if($postTotal>=$passPercent){
                    $data['user_test_status'] = 'pass';
                }else{
                    $data['user_test_status'] = 'fail';
                }
                $testDb->update($data,array('test_id'=>$testResult['testStatus']['test_id']));
            }
        }
        return $lastInsertedTestsId;
    }

    public function fetchPostTestAllDetails(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('posttest_questions');
        $sQueryStr = $sql->buildSqlString($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $sResult;
    }

    public function fetchPostResultDetails(){
        $logincontainer = new Container('credo');
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $testDb = new \Application\Model\TestsTable($this->adapter);
        $preTestDb = new \Application\Model\PretestQuestionsTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $testResult = $testDb->getTestDataByUserId($logincontainer->userId);
        $sQuery = $sql->select()->from(array('ptq' => 'posttest_questions'))->columns(array('totalCount' => new \Laminas\Db\Sql\Expression('COUNT(post_test_id)')))
                                ->join(array('t' => 'tests'), 't.test_id = ptq.test_id', array('post_test_score','test_id'))
                                ->join(array('b' => 'biosafety_user'), 'b.bs_user_id = t.user_id', array('unique_id','full_name','email_id'))
                                ->where(array('t.user_id' => $logincontainer->userId,'t.test_id'=>$testResult['testStatus']['test_id']))
                                ->order('ptq.test_id DESC');
        $sQueryStr = $sql->buildSqlString($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $maxQuestion = $testConfigDb->fetchTestConfigDetails();
        $sResult['preResult'] = $preTestDb->fetchPreResultDetails();
        $sResult['preTestQuestion'] = $this->getQuestionList($testResult['testStatus']['test_id'],'pretest_questions');
        $sResult['postTestQuestion'] = $this->getQuestionList($testResult['testStatus']['test_id'],'posttest_questions');
        return $sResult;
    }

    public function getQuestionList($testId,$tableName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('ptq' => $tableName))
                                ->join(array('q' => 'questions'), 'q.question_id = ptq.question_id', array('correct_option_text'))
                                ->where(array('ptq.test_id'=>$testId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $sResult;
    }

    public function fetchPostTestCompleteDetails(){
        $logincontainer = new Container('credo');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('response_id'))
                                ->join(array('t' => 'tests'), 't.test_id = pt.test_id', array('pre_test_status','post_test_status'))
                                ->where(array('t.user_id' => $logincontainer->userId))
                                ->order('t.test_id DESC')
                                ->limit('1');
        $sQueryStr = $sql->buildSqlString($sQuery);
        // echo $sQueryStr;die;
        $result['testSatatus'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        $tcQuery = $sql->select()->from('test_config');
        $tcQueryStr = $sql->buildSqlString($tcQuery);
        $tResult = $dbAdapter->query($tcQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $result['testconfig'] = $tResult[2]['test_config_value'];
        return $result;
        
    }
}
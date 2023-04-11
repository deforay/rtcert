<?php

namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;

use Laminas\Db\Sql\Expression;
use Application\Service\CommonService;

class PretestQuestionsTable extends AbstractTableGateway
{

    protected $table = 'pretest_questions';
    protected $writtenExamTable = null;

    public function __construct(Adapter $adapter, $writtenExamTable = '')
    {
        $this->adapter = $adapter;
        $this->writtenExamTable = $writtenExamTable;
    }

    public function savePreTestData($params)
    {
        $logincontainer = new Container('credo');
        $testDb = new \Application\Model\TestsTable($this->adapter);
        $questionDb = new \Application\Model\QuestionTable($this->adapter);
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $lastInsertedTestsId = 0;
        if (isset($params['questionId']) && trim($params['questionId']) != "") {
            //first check allready start datetime
            $testResult = $testDb->getTestDataByUserId($logincontainer->userId);
            if (($testResult['testStatus']['pretest_start_datetime'] == '0000-00-00 00:00:00') || ($testResult['testStatus']['pretest_start_datetime'] == 'NULL')) {
                $data = array(
                    'pretest_start_datetime' => date("Y-m-d H:i:s", time()),
                    'pre_test_status' => 'not completed'
                );
                $testDb->update($data, array('test_id' => $testResult['testStatus']['test_id']));
            }
            $lastInsertedTestsId = $testResult['testStatus']['test_id'];

            $questionData = $questionDb->fetchQuestionsListById(base64_decode($params['questionId']));
            $correctOptAry = explode(",", $questionData['correct_option']);
            $aryInter = array_intersect($params['optionId'], $correctOptAry);
            if (count($aryInter) == count($params['optionId'])) {
                $score = 1;
            } else {
                $score = 0;
            }

            $preData = array(
                'question_text' => $questionData['question'],
                'response_id' => implode(",", $params['optionId']),
                'response_text' => json_encode($params['optionText']),
                'score' => $score,
            );
            $this->update($preData, array('pre_test_id' => base64_decode($params['pretestId'])));

            $sQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('pre_test_id'))
                ->join(array('t' => 'tests'), 't.test_id = pt.test_id', array('pre_test_score'))
                ->where(array('t.user_id' => $logincontainer->userId))
                ->where('pt.response_id IS NULL');
            $sQueryStr = $sql->buildSqlString($sQuery);
            $questionResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

            if (!$questionResult) {
                $passPercent = $testConfigDb->fetchTestValue('passing-percentage');

                $tsQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('score' => new \Laminas\Db\Sql\Expression('SUM(score)'), 'totalQuestion' => new \Laminas\Db\Sql\Expression('COUNT(*)')))
                    ->where(array('pt.test_id' => $lastInsertedTestsId));
                $tsQueryStr = $sql->buildSqlString($tsQuery);
                $preTestResult = $dbAdapter->query($tsQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                $preScore = ($preTestResult['score'] / $preTestResult['totalQuestion']);
                $preTotal = round($preScore * 100);
                $data = array(
                    'pretest_end_datetime' => date("Y-m-d H:i:s", time()),
                    'pre_test_status' => "completed",
                    'pre_test_score' => $preTestResult['score']
                );
                if ($preTotal >= $passPercent) {
                    $data['user_test_status'] = 'pass';
                } else {
                    $data['user_test_status'] = 'fail';
                }
                $testDb->update($data, array('test_id' => $testResult['testStatus']['test_id']));
                $forWrittenQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('pre_test_id', 'score'))
                    ->join(array('t' => 'tests'), 't.test_id = pt.test_id', array('test_id', 'pre_test_score'))
                    ->join(array('tq' => 'test_questions'), 'tq.question_id = pt.question_id', array('correct_option'))
                    ->join(array('ts' => 'test_sections'), 'ts.section_id = tq.section', array('section_name', 'section_slug'))
                    ->where(array('t.user_id' => $logincontainer->userId, 't.test_id' => $testResult['testStatus']['test_id']))
                    ->order('t.test_id DESC');
                $forWrittenQueryStr = $sql->buildSqlString($forWrittenQuery);
                // die($forWrittenQueryStr);
                $forWrittenResult = $dbAdapter->query($forWrittenQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                // For getting total attended test count
                $typeQuery = $sql->select()->from('tests')->columns(array("type" => new Expression("COUNT(*)")))->where(array('user_id' => $logincontainer->userId));
                $typeQueryStr = $sql->buildSqlString($typeQuery);
                $type = $dbAdapter->query($typeQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                // For getting user name for who send the link to provider as admin
                $pQuery = $sql->select()->from(array('p' => 'provider'))->columns(array('link_send_by'))
                    ->join(array('u' => 'users'), 'u.id = p.link_send_by', array('id', 'first_name', 'last_name'))
                    ->where(array('p.id' => $logincontainer->userId));
                $pQueryStr = $sql->buildSqlString($pQuery);
                $pUser = $dbAdapter->query($pQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                $qa = 0;
                $rt = 0;
                $safety = 0;
                $specimen = 0;
                $testingAlgo = 0;
                $reportKeeping = 0;
                $eqaPt = 0;
                $ethics = 0;
                $inventory = 0;
                foreach ($forWrittenResult as $row) {
                    $qa += (isset($row['section_slug']) && $row['section_slug'] == 'qa' && $row['score'] > 0) ? 1 : 0;
                    $rt += (isset($row['section_slug']) && $row['section_slug'] == 'overview-of-rt' && $row['score'] > 0) ? 1 : 0;
                    $safety += (isset($row['section_slug']) && $row['section_slug'] == 'safety' && $row['score'] > 0) ? 1 : 0;
                    $specimen += (isset($row['section_slug']) && $row['section_slug'] == 'specimen-collection' && $row['score'] > 0) ? 1 : 0;
                    $testingAlgo += (isset($row['section_slug']) && $row['section_slug'] == 'serial-alogrithm' && $row['score'] > 0) ? 1 : 0;
                    $reportKeeping += (isset($row['section_slug']) && $row['section_slug'] == 'record-keeping' && $row['score'] > 0) ? 1 : 0;
                    $eqaPt += (isset($row['section_slug']) && $row['section_slug'] == 'eqa-and-dts-pt' && $row['score'] > 0) ? 1 : 0;
                    $ethics += (isset($row['section_slug']) && $row['section_slug'] == 'professional-ethics' && $row['score'] > 0) ? 1 : 0;
                    $inventory += (isset($row['section_slug']) && $row['section_slug'] == 'inventory' && $row['score'] > 0) ? 1 : 0;
                }
                $wQuery = $sql->select()->from('written_exam')->where(array('test_id' => $forWrittenResult[0]['test_id']));
                $wQueryStr = $sql->buildSqlString($wQuery);
                $writtenDataTest = $dbAdapter->query($wQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                $writtenData = array(
                    'id_written_exam'       => ($writtenDataTest) ? $writtenDataTest['id_written_exam'] : null,
                    'test_id'               => $forWrittenResult[0]['test_id'],
                    'exam_type'             => (isset($type) && $type) ? $type['type'] . ' attempt' : '1 attempt',
                    'provider_id'           => $logincontainer->userId,
                    'exam_admin'            => ($pUser) ? ucwords($pUser['first_name'] . ' ' . $pUser['last_name']) : 'provider',
                    'date'                  => date('d-m-Y'),
                    'qa_point'              => $qa,
                    'rt_point'              => $rt,
                    'safety_point'          => $safety,
                    'specimen_point'        => $specimen,
                    'testing_algo_point'    => $testingAlgo,
                    'report_keeping_point'  => $reportKeeping,
                    'EQA_PT_points'         => $eqaPt,
                    'ethics_point'          => $ethics,
                    'inventory_point'       => $inventory
                );
                $this->writtenExamTable->saveWrittenExamByTest((object)$writtenData);
            }
        }
        return $lastInsertedTestsId;
    }

    public function fetchPreTestAllDetails()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('pretest_questions');
        $sQueryStr = $sql->buildSqlString($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $sResult;
    }

    public function fetchPreResultDetails()
    {
        $testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
        $testDb = new \Application\Model\TestsTable($this->adapter);
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');

        //get last test id
        $testResult = $testDb->getTestDataByUserId($logincontainer->userId);
        $sQuery = $sql->select()->from(array('pt' => 'pretest_questions'))->columns(array('totalCount' => new \Laminas\Db\Sql\Expression('COUNT(pre_test_id)')))
            ->join(array('t' => 'tests'), 't.test_id = pt.test_id', array('pre_test_score'))
            ->join(array('p' => 'provider'), 'p.id = t.user_id', array('id', 'first_name', 'last_name', 'email', 'phone', 'test_mail_send'))
            ->where(array('t.user_id' => $logincontainer->userId, 't.test_id' => $testResult['testStatus']['test_id']))
            ->order('pt.test_id DESC');
        $sQueryStr = $sql->buildSqlString($sQuery);
        // die($sQueryStr);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $maxQuestion = $testConfigDb->fetchTestConfigDetails();
        $sResult['preTestQuestion'] = $this->getQuestionList($testResult['testStatus']['test_id'], 'pretest_questions');
        $sResult['maxQuestion'] = $maxQuestion[1]['test_config_value'];
        return $sResult;
    }

    public function getQuestionList($testId, $tableName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('ptq' => $tableName))
            ->join(array('q' => 'test_questions'), 'q.question_id = ptq.question_id', array('correct_option_text'))
            ->where(array('ptq.test_id' => $testId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $sResult;
    }
}

<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Adapter;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;
/*
* To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

/**
 * Description of Countries
 *
 * @author Thanaseelan
 */
class QuestionTable extends AbstractTableGateway
{

	protected $table = 'test_questions';

	public function __construct(Adapter $adapter)
	{
		$this->adapter = $adapter;
	}

	public function addQuestion($param)
	{
		// \Zend\Debug\Debug::dump($param);die;
		$optionDb = new \Application\Model\TestOptionsTable($this->adapter);
		if (isset($param['questionSection']) && trim($param['questionSection']) != "") {
			$data = array(
				'question' 		=> trim($param['questionSection']),
				'section' 		=> base64_decode($param['section']),
				'question_code' => $param['questionCode'],
				'status' 		=> $param['status'],
			);

			$this->insert($data);
			$lastInsertedId = $this->lastInsertValue;
			// To insert to option table data
			$correctOptionId = array();
			$correctOptionText = array();
			$n = count($param['option']);
			if ($n > 0) {
				for ($i = 0; $i < $n; $i++) {
					$optionData = array(
						'question' 	=> $lastInsertedId,
						'option' 	=> trim($param['option'][$i]),
						'status' 	=> $param['optionStatus'][$i],
					);
					$optionDb->insert($optionData);
					$lastOptionId = $optionDb->lastInsertValue;
					if ($param['selectedCheckBox'][$i] == 'yes') {
						$correctOptionId[] = $lastOptionId;
						$correctOptionText[] = $param['option'][$i];
					}
				}
			}
			if (!empty($correctOptionId)) {
				$correctOptionData = array('correct_option' => implode(",", $correctOptionId), 'correct_option_text' => implode("<br/>", $correctOptionText));
				$updateResult = $this->update($correctOptionData, array('question_id' => $lastInsertedId));
			}
			return $lastInsertedId;
		}
	}

	public function updateQuestion($param)
	{
		$optionDb = new \Application\Model\TestOptionsTable($this->adapter);

		if (isset($param['deletedQuestionList']) && trim($param['deletedQuestionList']) != "") {
			$questionIdList = explode(",", $param['deletedQuestionList']);
			if (count($questionIdList) > 0) {
				foreach ($questionIdList as $questionIds) {
					$optionDb->delete("option_id=" . base64_decode($questionIds));
				}
			}
		}

		if (isset($param['questionSection']) && trim($param['questionSection']) != "") {
			$data = array(
				'question' 		=> trim($param['questionSection']),
				'section' 		=> base64_decode($param['section']),
				'question_code' => trim($param['questionCode']),
				'status' 		=> $param['status'],
			);
			$updateResult = $this->update($data, array('question_id' => base64_decode($param['questionId'])));
			$lastUpdatedId = base64_decode($param['questionId']);

			$correctOption = explode("##", $param['correctOption']);
			//Insert to option table data
			$correctOptionId = array();
			$correctOptionText = array();
			$n = count($param['option']);
			if ($n > 0) {
				for ($i = 0; $i < $n; $i++) {
					if (isset($param['optionId'][$i]) && trim($param['optionId'][$i]) != "") {
						$optionData = array(
							'question' 	=> $lastUpdatedId,
							'option' 	=> trim($param['option'][$i]),
							'status' 	=> $param['optionStatus'][$i],
						);
						if ($param['selectedCheckBox'][$i] == 'yes') {
							$correctOptionId[] = base64_decode($param['optionId'][$i]);
							$correctOptionText[] = $param['option'][$i];
						}
						$r = $optionDb->update($optionData, array('option_id' => base64_decode($param['optionId'][$i])));
					} else {
						$optionNewData = array(
							'question' 	=> $lastUpdatedId,
							'option' 	=> trim($param['option'][$i]),
							'status' 	=> $param['optionStatus'][$i],
						);
						$optionDb->insert($optionNewData);
						$lastOptionId = $optionDb->lastInsertValue;
						if ($param['selectedCheckBox'][$i] == 'yes') {
							$correctOptionId[] = $lastOptionId;
							$correctOptionText[] = $param['option'][$i];
						}
					}
				}
			}
			if (!empty($correctOptionId)) {
				$correctOptionData = array('correct_option' => implode(",", $correctOptionId), 'correct_option_text' => implode("<br/>", $correctOptionText));
				$updateResult = $this->update($correctOptionData, array('question_id' => base64_decode($param['questionId'])));
			}
			return $lastUpdatedId;
		}
	}

	public function fetchQuestionList($parameters, $acl)
	{
		/* Array of database columns which should be read and sent back to DataTables. Use a space where
    * you want to insert a non-database field (for example a counter or static image)
    */
		$aColumns = array('q.question_code','q.question', 's.section_name', 'q.correct_option_text', 'q.status');
		$orderColumns = array('q.question_id','q.question_code','q.question', 's.section_name', 'q.correct_option_text', 'q.status');
		/*
    * Paging
    */
		$sLimit = "";
		if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
			$sOffset = $parameters['iDisplayStart'];
			$sLimit = $parameters['iDisplayLength'];
		}

		/*
    * Ordering
    */

		$sOrder = "";
		if (isset($parameters['iSortCol_0'])) {
			for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
				if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
					$sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
				}
			}
			$sOrder = substr_replace($sOrder, "", -1);
		}

		/*
    * Filtering
    * NOTE this does not match the built-in DataTables filtering which does it
    * word by word on any field. It's possible to do here, but concerned about efficiency
    * on very large tables, and MySQL's regex functionality is very limited
    */

		$sWhere = "";
		if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
			$searchArray = explode(" ", $parameters['sSearch']);
			$sWhereSub = "";
			foreach ($searchArray as $search) {
				if ($sWhereSub == "") {
					$sWhereSub .= "(";
				} else {
					$sWhereSub .= " AND (";
				}
				$colSize = count($aColumns);

				for ($i = 0; $i < $colSize; $i++) {
					if ($i < $colSize - 1) {
						$sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
					} else {
						$sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
					}
				}
				$sWhereSub .= ")";
			}
			$sWhere .= $sWhereSub;
		}

		/* Individual column filtering */
		for ($i = 0; $i < count($aColumns); $i++) {
			if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
				if ($sWhere == "") {
					$sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
				} else {
					$sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
				}
			}
		}

		/*
    * SQL queries
    * Get data to display
    */
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$sQuery = $sql->select()->from(array('q' => 'test_questions'))
			->join(array('s' => 'test_sections'), 'q.section = s.section_id', array('section_name'));

		if (isset($sWhere) && $sWhere != "") {
			$sQuery->where($sWhere);
		}

		if (isset($sOrder) && $sOrder != "") {
			$sQuery->order($sOrder);
		}

		if (isset($sLimit) && isset($sOffset)) {
			$sQuery->limit($sLimit);
			$sQuery->offset($sOffset);
		}

		$sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
		// echo $sQueryStr;die;
		$rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

		/* Data set length after filtering */
		$sQuery->reset('limit');
		$sQuery->reset('offset');
		$fQuery = $sql->getSqlStringForSqlObject($sQuery);
		$aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
		$iFilteredTotal = count($aResultFilterTotal);

		/* Total data set length */
		$iTotal = $this->select(array("question_id"))->count();
		$output = array(
			"sEcho" => intval($parameters['sEcho']),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => array()
		);
		$loginContainer = new Container('credo');
		$role = $loginContainer->roleCode;

		foreach ($rResult as $aRow) {
			$row = array();
			$row[] = ucwords($aRow['question_code']);
			$row[] = ucwords($aRow['question']);
			$row[] = ucwords($aRow['section_name']);
			$row[] = ucwords($aRow['correct_option_text']);
			$row[] = ucwords($aRow['status']);
			if ($acl->isAllowed($role, 'Application\Controller\TestQuestion', 'edit')) {
				$row[] = '<a href="/test-question/edit/' . base64_encode($aRow['question_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="fa fa-pencil">Edit</i></a>';
			} else {
				$row[] = "";
			}
			$output['aaData'][] = $row;
		}
		return $output;
	}

	public function fetchQuestionsListById($questionId)
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$sQuery = $sql->select()->from(array('q' => $this->table))
			->join(array('s' => 'test_sections'), 'q.section = s.section_id', array('section_name'))
			->where(array('q.question_id' => $questionId));
		$sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
		// die($sQueryStr);
		return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
	}

	public function fetchOptionListById($questionId)
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$sQuery = $sql->select()->from(array('o' => 'test_options'), array('question', 'option', 'status'))
			->join(array('q' => $this->table), 'o.question = q.question_id', array('question_id'))
			->where(array('q.question_id' => $questionId));
		$sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
		// echo $sQueryStr;die;
		return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
	}

	/* Data Fetch from Front End Question Filder */
	public function getTestConfig()
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$tcQuery = $sql->select()->from('test_config');
		$tcQueryStr = $sql->getSqlStringForSqlObject($tcQuery);
		$testConfigResult = $dbAdapter->query($tcQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		return $testConfigResult;
	}
	public function insertQuestion()
	{
		$preTestDb = new \Application\Model\PretestQuestionsTable($this->adapter);
		$testDb = new \Application\Model\TestsTable($this->adapter);
		$testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
		$testConfigResult = $testConfigDb->fetchTestConfigDetails();
		$logincontainer = new Container('credo');
		$data = array(
			'pretest_start_datetime' => date("Y-m-d H:i:s", time()),
			'pre_test_status' => 'not completed',
			'user_id' => $logincontainer->userId
		);
		$testDb->insert($data);
		$lastInsertedTestsId = $testDb->lastInsertValue;

		$result['question'] = $this->getRandomQuestions(null, $testConfigResult[1]['test_config_value'], 'pretest_questions', 'pre_test_id');
		
		foreach ($result['question'] as $questionList) {
			$preData = array(
				'test_id' => $lastInsertedTestsId,
				'question_id' => $questionList['question_id']
			);
			$preTestDb->insert($preData);
		}
		//get all inserted question option
		$result['option'] = $this->getRandomQuestionsOptions($lastInsertedTestsId, 'pretest_questions', 'pre_test_id');
		return $result;
	}

	public function fetchQuestionAllList()
	{
		$testDb = new \Application\Model\TestsTable($this->adapter);
		$testConfigDb = new \Application\Model\TestConfigTable($this->adapter);
		$logincontainer = new Container('credo');
		$result = array();
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$testResult = $testDb->getTestDataByUserId($logincontainer->userId);
		// \Zend\Debug\Debug::dump($testResult['testStatus']['test_id']);die;
		//global result
		$testConfigResult = $testConfigDb->fetchTestConfigDetails();
		if (!$testResult['testStatus']) {
			$result = $this->insertQuestion();
		} else if (isset($testResult['testStatus']['pre_test_status']) && $testResult['testStatus']['pre_test_status'] == 'completed' && $testConfigResult[2]['test_config_value'] == 'yes') {
			$qQuery = $sql->select()->from('tests')->columns(array('total' => new \Zend\Db\Sql\Expression('COUNT(*)')))->where(array('user_id'=>$logincontainer->userId));
			$qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
			$testCountResult = $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
			if($testCountResult){
				$container = new Container('alert');
				$container->alertMsg = "You are already attended the test in ".$testCountResult['total']." time.";
			}
			$result = $this->insertQuestion();
		} else if (isset($testResult['testStatus']['test_id']) && ($testResult['testStatus']['pre_test_status'] == NULL || $testResult['testStatus']['pre_test_status'] != 'completed')) {
			$result['question'] = $this->getRandomQuestions($testResult['testStatus']['test_id'], null, 'pretest_questions', 'pre_test_id');
			//get all inserted question option
			$result['option'] = $this->getRandomQuestionsOptions($testResult['testStatus']['test_id'], 'pretest_questions', 'pre_test_id');
		} else if (isset($testResult['testStatus']['test_id']) && ($testResult['testStatus']['post_test_status'] == NULL || $testResult['testStatus']['post_test_status'] != 'completed')) {
			$result['posttest-page'] = true;
		} else {
			$result['home-page'] = true;
		}
		$result['testResultStatus'] = $testResult;
		return $result;
	}

	public function getRandomQuestions($testId = null, $limit = null, $tableName, $primary)
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$questionResult = array();
		$testCondifDetailsDB = new TestConfigDetailsTable($this->adapter);
		$configDetails =  $testCondifDetailsDB->select()->toArray();
		// \Zend\Debug\Debug::dump($configDetails);die;
		$configCount = count($configDetails);
        if(isset($configDetails) && count($configDetails) > 0){
			$secLimit = 0;
			foreach($configDetails as $cd){
				if($cd['no_of_questions']>0){
					//get list of all questions
					$qQuery = $sql->select()->from(array('q' => 'test_questions'));
					$secLimit += $cd['no_of_questions'];
					if ($testId == null) {
						$qQuery = $qQuery->order(new Expression('RAND()'))
						->where(array('status' => 'active','section'=>$cd['section_id']));
						if($secLimit<=$limit){
							$qQuery->limit($cd['no_of_questions']);
						}else{
							return $questionResult;
						}
					} else if ($limit == null) {
						$qQuery = $qQuery->join(array('pq' => $tableName), 'pq.question_id=q.question_id', array($primary, 'test_id', 'question_id', 'response_id'))
							->where(array('pq.test_id' => $testId,'section'=>$cd['section_id']))
							->order('pq.' . $primary . ' ASC');
					}
					$qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
					// die($qQueryStr);
					$questions = $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
					foreach($questions as $key=>$q){
						$questionResult[] = array(
							'question_id'           => $q['question_id'],
							'question_code'         => $q['question_code'],
							'question'              => $q['question'],
							'section'               => $q['section'],
							'status'                => $q['status'],
							'correct_option'        => $q['correct_option'],
							'correct_option_text'   => $q['correct_option_text'],
							'pre_test_id'           => (isset($q['pre_test_id']) && $q['pre_test_id'] != '')?$q['pre_test_id']:'',
							'test_id'               => (isset($q['test_id']) && $q['test_id'] != '')?$q['test_id']:'',
							'response_id'           => (isset($q['response_id']) && $q['response_id'] != '')?$q['response_id']:'',
						);
					}
				}
			}
			if($secLimit<=$limit && $secLimit >=$configCount){
				//get list of all questions
				$qQuery = $sql->select()->from(array('q' => 'test_questions'));
				if ($testId == null) {
					$qQuery = $qQuery->order(new Expression('RAND()'))
						->where(array('status' => 'active'))
						->limit(($limit-$secLimit));
				} else if ($limit == null) {
					$qQuery = $qQuery->join(array('pq' => $tableName), 'pq.question_id=q.question_id', array($primary, 'test_id', 'question_id', 'response_id'))
						->where(array('pq.test_id' => $testId))
						->order('pq.' . $primary . ' ASC');
				}
				$qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
				$questions = $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
				foreach($questions as $key=>$q){
					$questionResult[] = array(
						'question_id'           => $q['question_id'],
						'question_code'         => $q['question_code'],
						'question'              => $q['question'],
						'section'               => $q['section'],
						'status'                => $q['status'],
						'correct_option'        => $q['correct_option'],
						'correct_option_text'   => $q['correct_option_text'],
						'pre_test_id'           => (isset($q['pre_test_id']) && $q['pre_test_id'] != '')?$q['pre_test_id']:'',
						'test_id'               => (isset($q['test_id']) && $q['test_id'] != '')?$q['test_id']:'',
						'response_id'           => (isset($q['response_id']) && $q['response_id'] != '')?$q['response_id']:'',
					);
				}
			}
		}else{
			//get list of all questions
			$qQuery = $sql->select()->from(array('q' => 'test_questions'));
			if ($testId == null) {
				$qQuery = $qQuery->order(new Expression('RAND()'))
					->where(array('status' => 'active'))
					->limit($limit);
			} else if ($limit == null) {
				$qQuery = $qQuery->join(array('pq' => $tableName), 'pq.question_id=q.question_id', array($primary, 'test_id', 'question_id', 'response_id'))
					->where(array('pq.test_id' => $testId))
					->order('pq.' . $primary . ' ASC');
			}
			$qQueryStr = $sql->getSqlStringForSqlObject($qQuery);
			$questionResult = $dbAdapter->query($qQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		}
		return $questionResult;
	}
	public function getRandomQuestionsOptions($testId, $tableName, $primary)
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$oQuery = $sql->select()->from(array('o' => 'test_options'))
			->join(array('pq' => $tableName), 'pq.question_id=o.question', array($primary, 'test_id', 'question_id', 'response_id'))
			->where(array('o.status' => 'active'))
			->where(array('pq.test_id' => $testId));
		$oQueryStr = $sql->getSqlStringForSqlObject($oQuery);
		$result = $dbAdapter->query($oQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
		return $result;
	}

	public function fetchPostQuestionList()
	{
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$testDb = new \Application\Model\TestsTable($this->adapter);
		$postTestDb = new \Application\Model\PostTestQuestionsTable($this->adapter);
		$logincontainer = new Container('credo');
		$testResult = $testDb->getTestDataByUserId($logincontainer->userId);
		$result = array();
		$result['question'] = $this->getRandomQuestions($testResult['testStatus']['test_id'], null, 'posttest_questions', 'post_test_id');
		if (count($result['question']) == 0) {
			//insert post test data
			$result['question'] = $this->getRandomQuestions($testResult['testStatus']['test_id'], null, 'pretest_questions', 'pre_test_id');
			foreach ($result['question'] as $preQuestion) {
				$postTestDb->insert(array(
					'question_id' => $preQuestion['question_id'],
					'test_id' => $preQuestion['test_id']
				));
			}
			$result['question'] = $this->getRandomQuestions($testResult['testStatus']['test_id'], null, 'posttest_questions', 'post_test_id');
			$result['option'] = $this->getRandomQuestionsOptions($testResult['testStatus']['test_id'], 'posttest_questions', 'post_test_id');
		} else {
			//get all inserted question option
			$result['option'] = $this->getRandomQuestionsOptions($testResult['testStatus']['test_id'], 'posttest_questions', 'post_test_id');
		}
		return $result;
	}

	public function fetchFrequencyQuestionList($parameters)
	{
		$querycontainer = new Container('query');
		$aColumns = array('question');
		$orderColumns = array('question');

		$sLimit = "";
		if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
			$sOffset = $parameters['iDisplayStart'];
			$sLimit = $parameters['iDisplayLength'];
		}

		$sOrder = "";
		if (isset($parameters['iSortCol_0'])) {
			for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
				if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
					$sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
				}
			}
			$sOrder = substr_replace($sOrder, "", -1);
		}

		$sWhere = "";
		if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
			$searchArray = explode(" ", $parameters['sSearch']);
			$sWhereSub = "";
			foreach ($searchArray as $search) {
				if ($sWhereSub == "") {
					$sWhereSub .= "(";
				} else {
					$sWhereSub .= " AND (";
				}
				$colSize = count($aColumns);

				for ($i = 0; $i < $colSize; $i++) {
					if ($i < $colSize - 1) {
						$sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
					} else {
						$sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
					}
				}
				$sWhereSub .= ")";
			}
			$sWhere .= $sWhereSub;
		}
		/* Individual column filtering */
		for ($i = 0; $i < count($aColumns); $i++) {
			if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
				if ($sWhere == "") {
					$sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
				} else {
					$sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
				}
			}
		}
		$dbAdapter = $this->adapter;
		$sql = new Sql($dbAdapter);
		$sQuery = $sql->select()->from(array('q' => $this->table))->columns(array('question_id', 'question', 'correct_option'))
			->join(array('pre' => 'pretest_questions'), 'q.question_id = pre.question_id', array('preQCount' => new Expression('COUNT(pre_test_id)'), "preRCount" => new Expression("SUM(CASE WHEN (pre.score  = 1) THEN 1 ELSE 0 END)")), 'left')
			->join(array('t' => 'tests'), 'pre.test_id = t.test_id', array('pretest_start_datetime', 'posttest_start_datetime'))
			->group("q.question_id");

		if (isset($parameters['preTestDateRange']) && $parameters['preTestDateRange'] != '') {
			$preDate = explode(" to ", $parameters['preTestDateRange']);
			$preStartDate = date('Y-m-d', strtotime($preDate[0]));
			$preEndDate = date('Y-m-d', strtotime($preDate[1]));
			$sQuery = $sQuery->where(array("DATE(t.pretest_start_datetime) >='" . $preStartDate . "'", "DATE(t.pretest_start_datetime) <='" . $preEndDate . "'"));
		}
		/* if (isset($parameters['postTestDateRange']) && $parameters['postTestDateRange'] != '') {
			$postDate = explode(" to ", $parameters['postTestDateRange']);
			$postStartDate = date('Y-m-d', strtotime($postDate[0]));
			$postEndDate = date('Y-m-d', strtotime($postDate[1]));
			$sQuery = $sQuery->where(array("DATE(t.posttest_start_datetime) >='" . $postStartDate . "'", "DATE(t.posttest_start_datetime) <='" . $postEndDate . "'"));
			// \Zend\Debug\Debug::dump($sQuery2);die;
		} */
		$querycontainer->questionPreQueryStr =  $sQuery;
		if (isset($sWhere) && $sWhere != "") {
			$sQuery->where($sWhere);
		}
		if (isset($sOrder) && $sOrder != "") {
			$sQuery->order($sOrder);
		}
		if (isset($sLimit) && isset($sOffset)) {
			$sQuery->limit($sLimit);
			$sQuery->offset($sOffset);
		}
		$sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
		$rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
		/* Data set length after filtering */
		$sQuery->reset('limit');
		$sQuery->reset('offset');

		$fQuery = $sql->getSqlStringForSqlObject($sQuery);
		$aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
		$iFilteredTotal = count($aResultFilterTotal);

		/* Total data set length */
		$iTotal = $this->select()->count();
		$iQuery = $sql->select()->from(array('q' => $this->table))->columns(array('question_id', 'question', 'correct_option'))
			->join(array('pre' => 'pretest_questions'), 'q.question_id = pre.question_id', array('preQCount' => new Expression('COUNT(pre_test_id)'), "preRCount" => new Expression("SUM(CASE WHEN (pre.score  = 1) THEN 1 ELSE 0 END)")), 'left')
			->join(array('t' => 'tests'), 'pre.test_id = t.test_id', array('pretest_start_datetime', 'posttest_start_datetime'))
			->group("q.question_id");

		if (isset($parameters['preTestDateRange']) && $parameters['preTestDateRange'] != '') {
			$preDate = explode(" to ", $parameters['preTestDateRange']);
			$preStartDate = date('Y-m-d', strtotime($preDate[0]));
			$preEndDate = date('Y-m-d', strtotime($preDate[1]));
			$iQuery = $iQuery->where(array("DATE(t.pretest_start_datetime) >='" . $preStartDate . "'", "DATE(t.pretest_start_datetime) <='" . $preEndDate . "'"));
		}
		$iQuery = $sql->getSqlStringForSqlObject($iQuery);
		$iTotal = $dbAdapter->query($iQuery, $dbAdapter::QUERY_MODE_EXECUTE);
		$output = array(
			"sEcho" => intval($parameters['sEcho']),
			"iTotalRecords" => count($iTotal),
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => array()
		);

		foreach ($rResult as $aRow) {

			$row = array();
			$row[] = ucwords($aRow['question']);
			$row[] = $aRow['preQCount'];
			$row[] = $aRow['preRCount'];
			$output['aaData'][] = $row;
		}
		return $output;
	}
}

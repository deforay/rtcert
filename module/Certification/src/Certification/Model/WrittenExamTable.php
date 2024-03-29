<?php

namespace Certification\Model;

use Laminas\Session\Container;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;

use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Paginator\Adapter\DbSelect;
use Laminas\Paginator\Paginator;
use Laminas\Db\Adapter\AdapterInterface;
use \Application\Model\GlobalTable;
use \Application\Model\TestConfigTable;
use \Application\Service\CommonService;
use Laminas\Db\TableGateway\TableGateway;

class WrittenExamTable extends AbstractTableGateway
{

    public $sm = null;
    public $tableGateway = null;
    public $adapter = null;
    public $table = 'written_exam';

    public function __construct(Adapter $adapter, $sm = null)
    {
        $this->adapter = $adapter;
        $this->sm = $sm;

        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new WrittenExam());
        $this->tableGateway = new TableGateway($this->table, $this->adapter, null, $resultSetPrototype);
    }

    public function fetchAll()
    {
        $sessionLogin = new Container('credo');
        $sqlSelect = $this->tableGateway->getSql()->select();
        $sqlSelect->columns(array(
            'id_written_exam', 'test_id', 'exam_type', 'provider_id', 'exam_admin', 'date', 'qa_point', 'rt_point',
            'safety_point', 'specimen_point', 'testing_algo_point', 'report_keeping_point', 'EQA_PT_points', 'ethics_point', 'inventory_point', 'total_points', 'final_score'
        ));
        $sqlSelect->join('provider', ' provider.id= written_exam.provider_id ', array('last_name', 'first_name', 'middle_name', 'district'), 'left')
            ->where(array('display' => 'yes'));
        $sqlSelect->join('location_details', 'provider.district=location_details.location_id', array('location_name'));

        if (isset($sessionLogin->district) && count($sessionLogin->district) > 0) {
            $sqlSelect->where('provider.district IN(' . implode(',', $sessionLogin->district) . ')');
        } else if (isset($sessionLogin->region) && count($sessionLogin->region) > 0) {
            $sqlSelect->where('provider.region IN(' . implode(',', $sessionLogin->region) . ')');
        }
        $sqlSelect->order('id_written_exam desc');

        $resultSet = $this->tableGateway->selectWith($sqlSelect);
        return $resultSet;
    }

    public function getWrittenExam($id_written_exam)
    {
        $id_written_exam = (int) $id_written_exam;
        $rowset = $this->tableGateway->select(array('id_written_exam' => $id_written_exam));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id_written_exam");
        }
        return $row;
    }

    public function saveWrittenExam(WrittenExam $written_exam)
    {

        $sessionLogin = new Container('credo');
        $date = $written_exam->date;
        $date_explode = explode("-", $date);
        $newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];

        $data = array(
            'test_id' => (isset($written_exam->test_id) && $written_exam->test_id != '') ? $written_exam->test_id : '',
            'exam_type' => $written_exam->exam_type,
            'provider_id' => $written_exam->provider_id,
            'exam_admin' => strtoupper($written_exam->exam_admin),
            'date' => $newsdate,
            'qa_point' => $written_exam->qa_point,
            'rt_point' => $written_exam->rt_point,
            'safety_point' => $written_exam->safety_point,
            'specimen_point' => $written_exam->specimen_point,
            'testing_algo_point' => $written_exam->testing_algo_point,
            'report_keeping_point' => $written_exam->report_keeping_point,
            'EQA_PT_points' => $written_exam->EQA_PT_points,
            'ethics_point' => $written_exam->ethics_point,
            'inventory_point' => $written_exam->inventory_point,
            'total_points' => $written_exam->qa_point + $written_exam->rt_point + $written_exam->safety_point + $written_exam->specimen_point + $written_exam->testing_algo_point + $written_exam->report_keeping_point + $written_exam->EQA_PT_points + $written_exam->ethics_point + $written_exam->inventory_point,
            'final_score' => (($written_exam->qa_point + $written_exam->rt_point + $written_exam->safety_point + $written_exam->specimen_point + $written_exam->testing_algo_point + $written_exam->report_keeping_point + $written_exam->EQA_PT_points + $written_exam->ethics_point + $written_exam->inventory_point) * 100) / 25,
            'training_id' => $written_exam->training_id,

        );

        $id_written_exam = (int) $written_exam->id_written_exam;
        if ($id_written_exam == 0) {
            $data['added_on'] = \Application\Service\CommonService::getDateTime();
            $data['added_by'] = $sessionLogin->userId;
            $data['updated_on'] = \Application\Service\CommonService::getDateTime();
            $data['updated_by'] = $sessionLogin->userId;
            $this->tableGateway->insert($data);
        } else {
            if ($this->getWrittenExam($id_written_exam)) {
                $data['updated_on'] = \Application\Service\CommonService::getDateTime();
                $data['updated_by'] = $sessionLogin->userId;
                $this->tableGateway->update($data, array('id_written_exam' => $id_written_exam));
            } else {
                throw new \Exception('Written Exam id does not exist');
            }
        }
    }

    public function saveWrittenExamByTest($written_exam)
    {
        $dbAdapter = $this->adapter;
        $testConfigDb = new TestConfigTable($dbAdapter);
        $date = $written_exam->date;
        $date_explode = explode("-", $date);
        $newsdate = $date_explode[2] . '-' . $date_explode[1] . '-' . $date_explode[0];
        $noOfQuestion = $testConfigDb->fetchTestValue('maximum-question-per-test');
        $noOfQuestion = (isset($noOfQuestion) && $noOfQuestion > 0) ? $noOfQuestion : 25;
        // echo $noOfQuestion;die;
        $data = array(
            'test_id' => (isset($written_exam->test_id) && $written_exam->test_id != '') ? $written_exam->test_id : '',
            'exam_type' => $written_exam->exam_type,
            'provider_id' => $written_exam->provider_id,
            'exam_admin' => strtoupper($written_exam->exam_admin),
            'date' => $newsdate,
            'qa_point' => $written_exam->qa_point,
            'rt_point' => $written_exam->rt_point,
            'safety_point' => $written_exam->safety_point,
            'specimen_point' => $written_exam->specimen_point,
            'testing_algo_point' => $written_exam->testing_algo_point,
            'report_keeping_point' => $written_exam->report_keeping_point,
            'EQA_PT_points' => $written_exam->EQA_PT_points,
            'ethics_point' => $written_exam->ethics_point,
            'inventory_point' => $written_exam->inventory_point,
            'total_points' => $written_exam->qa_point + $written_exam->rt_point + $written_exam->safety_point + $written_exam->specimen_point + $written_exam->testing_algo_point + $written_exam->report_keeping_point + $written_exam->EQA_PT_points + $written_exam->ethics_point + $written_exam->inventory_point,
            'final_score' => ((($written_exam->qa_point + $written_exam->rt_point + $written_exam->safety_point + $written_exam->specimen_point + $written_exam->testing_algo_point + $written_exam->report_keeping_point + $written_exam->EQA_PT_points + $written_exam->ethics_point + $written_exam->inventory_point) * 100) / $noOfQuestion),
            'training_id' => $written_exam->training_id,
        );
        $id_written_exam = (int) $written_exam->id_written_exam;
        if ($id_written_exam == 0) {
            $data['added_on'] = \Application\Service\CommonService::getDateTime();
            $data['added_by'] = $written_exam->added_by;
            // $data['updated_on'] = \Application\Service\CommonService::getDateTime();
            // $data['updated_by'] = $sessionLogin->userId;
            $this->tableGateway->insert($data);
        } else {
            if ($this->getWrittenExam($id_written_exam)) {
                $data['updated_on'] = \Application\Service\CommonService::getDateTime();
                $data['updated_by'] = $written_exam->updated_by;
                $this->tableGateway->update($data, array('id_written_exam' => $id_written_exam));
            } else {
                throw new \Exception('Written Exam id does not exist');
            }
        }
    }

    public function last_id()
    {
        $last_id = $this->tableGateway->lastInsertValue;
        //        die($last_id);
        return $last_id;
    }

    /**
     * insert written exam id to  examination
     * @param type $last_id
     */
    public function insertToExamination($last_id)
    {
        $db = $this->adapter;
        $sql1 = 'select provider_id from written_exam where id_written_exam=' . $last_id;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $provider = $res['provider_id'];
        }

        $sql2 = 'SELECT count(*) as nombre FROM examination WHERE provider=' . $provider . ' and id_written_exam is null and practical_exam_id is not null';
        $statement2 = $db->query($sql2);
        $result2 = $statement2->execute();
        foreach ($result2 as $res2) {
            $nombre = $res2['nombre'];
        }

        if ($nombre == 0) {
            $sql2 = 'insert into examination (id_written_exam,provider) values (' . $last_id . ',' . $provider . ')';
            $statement2 = $db->query($sql2);
            $result2 = $statement2->execute();
        } else {
            $sql = 'UPDATE examination SET id_written_exam=' . $last_id . ' WHERE provider=' . $provider;
            $db->getDriver()->getConnection()->execute($sql);
        }
    }

    /**
     * insert written and practical exam id to examination
     * @param type $written
     * @param type $last_id
     */
    public function examination($last_id, $practical)
    {

        $db = $this->adapter;
        $sql1 = 'select provider_id from written_exam where id_written_exam=' . $last_id;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $provider = $res['provider_id'];
        }

        $sql2 = 'insert into examination (provider,id_written_exam,practical_exam_id) values (' . $provider . ',' . $last_id . ',' . $practical . ')';
        $statement2 = $db->query($sql2);
        $result2 = $statement2->execute();
    }

    /**
     * count the number of  attempt 
     * @return type $nombre integer
     */
    public function attemptNumber($provider)
    {
        $db = $this->adapter;
        $sql1 = 'select date_certificate_issued, date_end_validity, certification_id from certification, examination, provider WHERE certification.examination=examination.id and examination.provider=provider.id and approval_status="approved" and final_decision="certified" and provider=' . $provider . ' ORDER BY date_certificate_issued DESC LIMIT 1';
        $statement1 = $db->query($sql1);
        $result1 = $statement1->execute();
        foreach ($result1 as $res1) {
            $date_certificate_issued = $res1['date_certificate_issued'];
            $date_end_validity = $res1['date_end_validity'];
            $certification_id = $res1['certification_id'];
        }

        if (!isset($date_certificate_issued)) {
            $date_certificate_issued = '0000-00-00';
            $date_end_validity = '0000-00-00';
        }
        if (isset($certification_id) && $certification_id != null) {
            $dbAdapter = $this->adapter;
            $globalDb = new GlobalTable($dbAdapter);
            $monthPriortoCertification = $globalDb->getGlobalValue('month-prior-to-certification');
            $startdate = strtotime(date('Y-m-d'));
            $enddate = strtotime($date_end_validity);
            $startyear = date('Y', $startdate);
            $endyear = date('Y', $enddate);
            $startmonth = date('m', $startdate);
            $endmonth = date('m', $enddate);
            $remmonths = (($endyear - $startyear) * 12) + ($endmonth - $startmonth);
            if ($remmonths > 0 && $remmonths > $monthPriortoCertification) {
                $date_after = date("Y-m-d", strtotime($date_end_validity . '- ' . $monthPriortoCertification . ' month'));
                $monthFlexLimit = $globalDb->getGlobalValue('month-flex-limit');
                $date_before = date("Y-m-d", strtotime($date_end_validity . '+ ' . $monthFlexLimit . ' month'));
                return '##' . $certification_id . '##' . \Application\Service\CommonService::humanReadableDateFormat($date_certificate_issued) . '##' . \Application\Service\CommonService::humanReadableDateFormat($date_after) . '##' . \Application\Service\CommonService::humanReadableDateFormat($date_before);
            }
        }
        $sql = 'SELECT COUNT(*) as nombre from (select  certification.id ,examination, final_decision, certification_issuer, date_certificate_issued, 
                date_certificate_sent, certification_type, provider,last_name, first_name, middle_name, certification_id,
                certification_reg_no, professional_reg_no,email,date_end_validity,facility_in_charge_email from certification, examination, provider where examination.id = certification.examination and provider.id = examination.provider and (approval_status in("rejected","Rejected") or final_decision in ("failed","pending")) and date_certificate_issued >' . $date_certificate_issued . ' and provider=' . $provider . ') as tab';
        //        die($sql);
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        //        die($nombre);
        return $nombre;
    }

    public function counWritten($provider)
    {
        $db = $this->adapter;
        $sql = 'SELECT count(*) as nombre FROM examination WHERE id_written_exam is not null and practical_exam_id is null and  provider=' . $provider . ' and add_to_certification="no"';
        //        die($sql3);
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        //        die($nombre);
        return $nombre;
    }

    public function getProviderName($practical)
    {
        $db = $this->adapter;
        $sql1 = 'select id, last_name, first_name, middle_name, phone, email  from provider , practical_exam where provider.id=practical_exam.provider_id and practice_exam_id=' . $practical;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        $selectData = array();

        foreach ($result as $res) {
            $selectData['name'] = $res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name'];
            $selectData['id'] = $res['id'];
        }
        return $selectData;
    }

    public function countPractical2($practical)
    {
        $db = $this->adapter;
        $sql = 'SELECT count(*) as nombre FROM examination WHERE practical_exam_id is not null and id_written_exam is null and practical_exam_id=' . $practical . ' and add_to_certification="no"';
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        return $nombre;
    }

    /**
     * find number of date before another attempt
     * @param type $provider
     * @return type
     */
    public function numberOfDays($provider)
    {
        $db = $this->adapter;
        $sql = 'SELECT DATEDIFF(now(),MAX(date_certificate_issued)) as nb_days from (SELECT provider, final_decision, date_certificate_issued, written_exam.id_written_exam , practical_exam.practice_exam_id ,last_name, first_name, middle_name, provider.id from examination, certification, written_exam,practical_exam, provider WHERE examination.id= certification.examination and examination.id_written_exam=written_exam.id_written_exam and practical_exam.practice_exam_id=examination.practical_exam_id and written_exam.provider_id=provider.id and final_decision in ("pending","failed") and provider.id=' . $provider . ') as tab';
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nb_days = $res['nb_days'];
        }
        return $nb_days;
    }

    public function getExamType($written)
    {
        $db = $this->adapter;
        $sql = 'SELECT exam_type from written_exam WHERE id_written_exam=' . $written;
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $exam_type = $res['exam_type'];
        }
        return $exam_type;
    }

    public function getProviderName2($written)
    {
        $db = $this->adapter;
        $sql1 = 'select id, last_name, first_name, middle_name from provider , written_exam where provider.id=written_exam.provider_id and id_written_exam=' . $written;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        $selectData = array();

        foreach ($result as $res) {
            $selectData['name'] = $res['last_name'] . ' ' . $res['first_name'] . ' ' . $res['middle_name'];
            $selectData['id'] = $res['id'];
        }
        return $selectData;
    }

    public function CountWritten($id_written_exam)
    {
        $db = $this->adapter;
        $sql1 = 'SELECT count(id_written_exam) as nombre FROM examination WHERE  id_written_exam=' . $id_written_exam;
        //        die($sql1);
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        return $nombre;
    }

    public function deleteWritten($id_written_exam)
    {
        $db = $this->adapter;
        $sql1 = 'SELECT max(id) as examination FROM  examination where id_written_exam=' . $id_written_exam;
        $statement = $db->query($sql1);
        $result = $statement->execute();
        foreach ($result as $res) {
            $examination = $res['examination'];
        }

        $sql4 = 'select id_written_exam,practical_exam_id from examination where id=' . $examination;
        $statement4 = $db->query($sql4);
        $result4 = $statement4->execute();
        foreach ($result4 as $res4) {
            $id_written_exam = $res4['id_written_exam'];
            $practical_exam_id = $res4['practical_exam_id'];
        }
        if (empty($practical_exam_id)) {
            $sql2 = 'delete from examination WHERE id=' . $examination;
            $db->getDriver()->getConnection()->execute($sql2);
        } else {
            $sql2 = 'UPDATE examination SET id_written_exam=null WHERE id=' . $examination;
            $db->getDriver()->getConnection()->execute($sql2);
        }
        $sql3 = 'Delete from written_exam where id_written_exam=' . $id_written_exam;
        $db->getDriver()->getConnection()->execute($sql3);
    }

    public function examToValidate($provider)
    {
        $db = $this->adapter;
        $sql = 'SELECT count(*) as nombre FROM examination WHERE id_written_exam is not null and practical_exam_id is not null and add_to_certification="no" and provider=' . $provider;
        //        die($sql);
        $statement = $db->query($sql);
        $result = $statement->execute();
        foreach ($result as $res) {
            $nombre = $res['nombre'];
        }
        //        die($nombre);
        return $nombre;
    }

    public function fetchWrittenExamAverageRadarResults()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $query = $sql->select()->from(array('we' => 'written_exam'))
            ->columns(
                array(
                    "qa_point_avg" => new Expression('AVG(qa_point)'),
                    "rt_point_avg" => new Expression('AVG(rt_point)'),
                    "safety_point_avg" => new Expression('AVG(safety_point)'),
                    "specimen_point_avg" => new Expression('AVG(specimen_point)'),
                    "testing_algo_point_avg" => new Expression('AVG(testing_algo_point)'),
                    "report_keeping_point_avg" => new Expression('AVG(report_keeping_point)'),
                    "EQA_PT_points_avg" => new Expression('AVG(EQA_PT_points)'),
                    "ethics_point_avg" => new Expression('AVG(ethics_point)'),
                    "inventory_point_avg" => new Expression('AVG(inventory_point)')
                )
            );
        $queryStr = $sql->buildSqlString($query);

        $examResults = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $examResultsScores = '';
        if (count($examResults[0]) > 0) {
            foreach (array_values($examResults[0]) as $key => $result) {

                if ($key < (count($examResults[0]) - 1)) {
                    $examResultsScores .= $result . ',';
                } else {
                    $examResultsScores .= $result;
                }
            }
        } else {
            $examResultsScores = '0,0,0,0,0,0,0,0,0';
        }
        return $examResultsScores;
    }




    public function getTrainingName($written)
    {
        $db = $this->adapter;

        $sql1 = 'SELECT 
        id_written_exam,
        training.training_id,
        training.Provider_id,
        type_of_competency,
        last_training_date,
        type_of_training,
        length_of_training,
        facilitator,
        training_certificate,
        date_certificate_issued,
        Comments,
        last_name,
        first_name,
        middle_name, 
        professional_reg_no, 
        certification_id, 
        certification_reg_no,
        training_organization_name,
        type_organization
        FROM written_exam,training,provider,training_organization WHERE  written_exam.training_id=training.training_id and training.Provider_id= provider.id and training_organization.training_organization_id=training.training_organization_id and written_exam.id_written_exam=' . $written;

        $statement = $db->query($sql1);
        $result = $statement->execute();
        $selectData = array();

        foreach ($result as $res) {
            $selectData['name'] = $res['type_of_competency'] . ' ' . $res['training_organization_name'] . ' ' . $res['type_organization'];
            $selectData['id'] = $res['training_id'];
        }
        return $selectData;
    }
}

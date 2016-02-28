<?php
namespace Usaged;

use Usaged\Database;
use Usaged\Machine;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Slim\Slim;

Class Logger {

    private $db;
    private $_db;
    private $app;

    public function __construct(Database $db) {
        $this->_db = $db;
        $this->db = $db->getPDO();
        $this->app = Slim::getInstance();
    }

    public function getAll() {
        $query = $this->db->prepare('SELECT inducteeuid,machineuid,starttime,endtime FROM logs');
        $results = array();
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array('inducteeuid'=>$result['inducteeuid'],'starttime'=>$result['starttime'],'endtime'=>$result['endtime']);
            }
            return $results;
        } else {
            return null;
        }
    }

    public function getByInducteeId($inducteeuid) {
        $query = $this->db->prepare('SELECT inducteeuid,machineuid,starttime,endtime FROM logs WHERE inducteeuid = :inducteeuid');
        $query->bindParam(':inducteeuid', $inducteeuid);
        $results = array();
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array('inducteeuid'=>$result['inducteeuid'],'starttime'=>$result['starttime'],'endtime'=>$result['endtime']);
            }
            return $results;
        } else {
            return null;
        }
    }

    public function getByMachineId($machineuid, $limit = 10) {
        $querystring = 'SELECT inducteeuid,machineuid,starttime,endtime FROM logs WHERE machineuid = :machineuid ORDER BY starttime DESC';
        if (is_numeric($limit) && $limit > 0) {
            $querystring .= ' LIMIT '.$limit;
        }
        $this->app->log->debug($querystring);
        $this->app->log->debug($machineuid);
        $query = $this->db->prepare($querystring);
        $query->bindParam(':machineuid', $machineuid);
        $results = array();
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $thisresult = array('inducteeuid'=>$result['inducteeuid'],'starttime'=>$result['starttime'],'endtime'=>$result['endtime']);
                $startDatetime = new \DateTime($result['starttime']);
                $endDatetime = new \DateTime($result['endtime']);
                $interval = $startDatetime->diff($endDatetime);
                $secs = $interval->format('%s');
                $thisresult['seconds'] = $secs;
                $thisresult['nicetime'] = $secs.' seconds';
                if ($secs > 60) {
                   list($mins,$secs) = explode(',',$interval->format('%m,%s'),2);
                   if ($mins > 60) {
                       list($hours,$mins,$secs) = explode(',',$interval->format('%h,%m,%s'),3);
                       $thisresult['nicetime'] = $hours.' hours, '.$mins.' minutes and '.$secs.' seconds';
                   } else {
                       $thisresult['nicetime'] = $mins.' minutes and '.$secs.' seconds';
                   }
		}
                $results[] = $thisresult;
            }
            return $results;
        } else {
            return null;
        }
    }

    public function cardCreateLog($cardid,$machineuid,$starttime,$endtime) {
        $inductee = new Inductee($this->_db);
        $uid = $inductee->getUidByCard($cardid);
        if ($inductee->cardCanUseMachine($cardid,$machineuid)) {
            return $this->createLog($uid,$machineuid,$starttime,$endtime);
        } else {
            throw (new \RuntimeException('Invalid machine id ('.$machineuid.') specified.'));
        }
    }

    public function createLog($inducteeuid,$machineuid,$starttime,$endtime) {
        $machine = new Machine($this->_db);
        if (!$machine->getById($machineuid)) {
            throw (new \RuntimeException('Invalid machine id specified.'));
        }
        $query = $this->db->prepare('INSERT INTO logs (inducteeuid,machineuid,starttime,endtime) VALUES (:inducteeuid,:machineuid,:starttime,:endtime)');
        $this->app->log->debug($query->queryString);
        $query->bindParam(':inducteeuid',$inducteeuid);
        $query->bindParam(':machineuid',$machineuid);
        $query->bindParam(':starttime',$starttime);
        $query->bindParam(':endtime',$endtime);
        if ($query->execute()) {
            return(true);
        } else {
            throw (new RuntimeException('Some database error occurred'));
        }
    }
}

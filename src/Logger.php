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
        $querystring = 'SELECT inducteeuid,machineuid,starttime,endtime,endtime-starttime seconds FROM logs WHERE machineuid = :machineuid ORDER BY starttime DESC';
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
                $phpendtime = strtotime($result['endtime']);
                $phpstarttime = strtotime($result['starttime']);
                $totalsecs = $phpendtime - $phpstarttime;
                $mysqlsecs = $result['seconds'];
                $thisresult['seconds'] = $secs;
                $this->app->log->debug($secs);

                list($hours,$mins,$secs) = explode(',',date('H,i,s',$secs),3);

                $thisresult['nicetime'] = sprintf('%u seconds',$secs);

                if ($totalsecs >= 60) {
                       $thisresult['nicetime'] = sprintf('%u minutes and %u seconds',$mins,$secs);
                }
                if ($totalsecs >= 3600) {
                       $thisresult['nicetime'] = sprintf('%u hours, %u minutes and %u seconds',$hours,$mins,$secs);
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

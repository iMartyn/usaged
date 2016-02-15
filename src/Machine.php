<?php
namespace Usaged;

use Usaged\Database;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

Class Machine {

    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getPDO();
    }

    public function getAll() {
        $query = $this->db->prepare('SELECT uid,machinename FROM machines');
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results[] = array('uid'=>$result['uid'],'machinename'=>$result['machinename']);
            }
        }
        return $results;
    }

    public function createMachine($machinename) {
        $response = array();
        try {
            $response['uid'] = UUid::uuid5(Uuid::NAMESPACE_DNS, 'usage.ranyard.info')->toString();
        } catch (UnsatisfiedDependencyException $e) {
            $response['error'] = $e->getMessage();
            $status = 500;
        }
        $response['machinename'] = $machinename;
        $query = $this->db->prepare('INSERT INTO machines (uid,machinename) VALUES (:uid,:machinename)');
        $query->bindParam(':machinename',$machinename);
        $query->bindParam(':uid',$response['uid']);
        if ($query->execute()) {
            return($response);
        } else {
            throw (new RuntimeException('Some database error occurred'));
        }
    }

    public function getByName($machinename) {
        $results = null;
        $query = $this->db->prepare('SELECT uid,machinename FROM machines WHERE machinename = :machinename LIMIT 1');
        $query->bindParam(':machinename',$machinename);
        if ($query->execute()) {
            while ($result = $query->fetch()) {
                $results = array('uid'=>$result['uid'],'machinename'=>$result['machinename']);
            }
        }
        return $results;
    }
}

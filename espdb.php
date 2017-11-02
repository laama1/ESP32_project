<?php

// AUTHOR: Lasse Pihlainen
// CREATED: 22.10.2017
// Edited: 23.10.2017,
// LICENSE: BSD


class espdb extends SQLite3 {
    protected $dbpath = "esp.sqlite";
    protected $db;
    protected $DEBUG = 0;

    public function __construct($arg = null) {
        if (!file_exists($this->dbpath)) {
            if ($this->createDB()) {
                echo "Database created.<br>";
            } else {
                echo "Error creating database.<br>";
            }
        } else {
            $this->pi("SQLite table exist.");
        }
    }

    # print errors
    private function pe($arg = null) {
        if ($arg === null || $this->DEBUG == 0) return;
        echo "Error: ".$arg . "<br>\n";
    }

    # print info
    private function pi($arg = null) {
        if ($arg === null || $this->DEBUG == 0) return;
        echo "Info: ".$arg ."<br>\n";
    }

    # debug print ARRAY
    private function pa($arg = null) {
        if ($arg == null || $this->DEBUG == 0) return;
        print_r($arg);
        echo "<br>\n";
    }


    private function createDB () {
        $this->pi("Creating Database for ESP32 Temp measurements...");

        $sqlString = "CREATE TABLE DEVICES (TYPE TEXT NOT NULL COLLATE NOCASE, NAME TEXT NOT NULL COLLATE NOCASE, CREATOR TEXT DEFAULT null, DATETIME INT NOT NULL, NICK TEXT DEFAULT NULL, DELETED INT DEFAULT 0);
        CREATE TABLE MEASUREMENTS (TYPE TEXT, VALUE TEXT, DEVICEID TEXT NOT NULL, SENSORID TEXT, DATETIME INT NOT NULL, DELETED INT DEFAULT 0, DEVICETIME TEXT, BOOTCOUNT INT);";
        #CREATE VIRTUAL TABLE MEASUREMENTS (TYPE, VALUE, DEVICEID, SENSORID, DATETIME, DELETED, DEVICETIME, BOOTCOUNT);";
        $ret = 0;
        try {
            $this->db = new PDO("sqlite:$this->dbpath");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $ret = $this->db->exec($sqlString);
        } catch(PDOException $e) {
            $this->pe("PDO exception: ".$e->getMessage());
        } catch(Exception $e) {
            $this->pe("Exception: " .$e->getMessage());
        }
        /*
        if(!$ret){
            print_r($this->db->errorInfo());
        } else {
            echo "Table created successfully\n";
        }
        */
        $this->db = null;
        return $ret;
    }


    # Add new Temperature measurement to table.
    public function addNewMeasurementToDB($device = null, $value = null, $esptime = null, $espbc = null, $espSensorID = null) {
        if ($device === null || $value === null) return -1;
        if ($this->searchDeviceFromDB($device)) {
            # TEMP, DEVICE, DATETIME, DELETED, ESPTIME, BOOTCOUNT
            # MEASUREMENTS (TYPE, VALUE, DEVICEID, SENSORID, DATETIME, DELETED, DEVICETIME, BOOTCOUNT);";
            $newtime = time();
            $this->pi("Adding new measurement: $value, device: $device, time: $newtime, esptime: $esptime, bootcount: $espbc");
            $sqlString ="INSERT INTO MEASUREMENTS (VALUE, DEVICEID, DATETIME, BOOTCOUNT, SENSORID) VALUES('$value', '$device', '$newtime', '$espbc', '$espSensorID')";
            return $this->insertIntoDB($sqlString);
        } else {
            # add device?
            $this->addDevice($device);
        }
    }

    public function searchDeviceFromDB($searchword = null) {
        if ($searchword === null ) return -1;
        $sqlString = "SELECT * from DEVICES where name = '$searchword'";
        $this->pi("searchDeviceFromDB: $searchword");
        return $this->getResultsFromDBQuery($sqlString);
    }

    # add new device to DB
    private function addDevice($device = null, $creator = null) {
        if ($device === null) return -1;
        # TYPE, NAME, CREATOR, DATETIME, NICK, DELETED
        $timenow = time();
        $sqlString = "INSERT INTO DEVICES (TYPE, NAME, CREATOR, DATETIME, DELETED) VALUES('esp', '$device', 'MATTI', '$timenow', 0)";
        //$sqlString2 = "CREATE VIRTUAL TABLE ? using FTS4(indexnbr, word, extrafield1);";
        $rtvalue = $this->insertIntoDB($sqlString);
        $this->pi("addDevice: $rtvalue");
    }


    private function insertIntoDB($sqlString = null) {
        if ($sqlString === null) return -1;
        try {
			$this->db = new PDO("sqlite:".$this->dbpath);
			$pdostmt = $this->db->prepare($sqlString);
			if ($pdostmt != null && $pdostmt->execute()) {
                $this->db = null;
				return true;
			} else {
                $this->pe("insertIntoDB ERROR.. $sqlString");
                $this->pa($pdostmt);
            }
        } catch(PDOException $e) {
            $this->pe("insertIntoDB: ".$e);
        } catch(EXCeption $e) {
            $this->pe("insertIntoDB: ".$e);
        }
        $this->db = null;
        return -2;
    }


	private function getResultsFromDBQuery($querystr = null) {
		if ($querystr === null) return -1;
		
		$this->db = new PDO("sqlite:$this->dbpath");
		try {
			if ($pdostmt = $this->db->prepare($querystr)) {
				if ($pdostmt->execute()) {
                    $results = $pdostmt->fetchAll();
                    $this->db = null;
					return $results;
				}
			}
		} catch(PDOException $e) {
            $this->pe("getResultsFromDBQuery: ".$e);
		} catch(Exception $e) {
			$this->pe("getResultsFromDBQuery: ".$e);
        }
        $this->db = null;
		return -2;
	}
}


?>

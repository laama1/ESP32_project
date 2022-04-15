<?php
require_once(__DIR__.'/espdb.php');
class esp_server {
	private $espdb;
	private $debug = 1;

	public function __construct()
	{
		
		$this->espdb = new espdb;
		if ($this->check_post() == false) {
			return;
		}
	}

	public function check_post() {
		if (!isset($_GET)) return false;
		$device = "";
		$value = -1000;
		$sensorID = -1000;
		$valueType = "undef";
		$bootcount = -1;
		$mytime = time();

		if (isset($_GET['device']) && $this->alphaNumericCheck($_GET['device'])) {
			// usually wifi MAC address of the device
			$device = $_GET['device'];
		} else return;
		if (isset($_GET['addtemp']) ) {
			$value = $_GET['addtemp'];
		}
		if (isset($_GET['sensorid']) && $this->alphaNumericCheck($_GET['sensorid'])) {
			$sensorID = $_GET['sensorid'];
		}

		if (isset($_GET['type']) && $this->alphaNumericCheck($_GET['type'])) {
			// what type, eg. level, temp
			$valueType = $_GET['type'];
		} else return;
		
		if (isset($_GET['time'])) {
			// exact time of measurement
			$mytime = $_GET['time'];
		}

		if (isset($_GET['bc']) && is_numeric($_GET['bc'])) {
			// bootcount
			$bootcount = $_GET['bc'];
		}
		if (isset($_GET['value']) && is_numeric($_GET['value'])) {
			// value of measurement
			$value = $_GET['value'];
		}
		$this->dp("Device: $device, Value: $value, bootcount: $bootcount\n");
		echo $this->espdb->addNewMeasurementToDB($valueType, $device, $value, $mytime, $bootcount, $sensorID);
		echo "Ding";
		return;
	}

	private function alphaNumericCheck($arg = null) {
		if ($arg === null) return;

		return preg_match('/^[a-zA-Z0-9]*$/', $arg);
	}

	# debug print string
	private function dp($arg = null) {
		if ($arg === null || $this->debug == 0) return;
		echo $arg . "<br>\n";
	}
}

$pum = new esp_server();
?>
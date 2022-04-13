<?php

class esp_server {
	private $espdb;

	public function __construct()
	{
		require_once(__DIR__.'/espdb.php');
		$this->espdb = new espdb;
		if ($this->check_post() == false) {
			return;
		}
	}

	public function check_post() {
		if (!isset($_POST)) return false;
			$device = "";
			$value = -1000;
			$sensorID = -1000;
			$valueType = "undef";
			$bootcount = -1;
			$mytime = time();

			if (isset($_POST['device']) && $this->alphaNumericCheck($_POST['device'])) {
				$device = $_POST['device'];
			} else return;
			if (isset($_POST['addtemp']) ) {
				$value = $_POST['addtemp'];
			}
			if (isset($_POST['sensorid']) && $this->alphaNumericCheck($_POST['sensorid'])) {
				$sensorID = $_POST['sensorid'];
			}

			if (isset($_POST['type']) && $this->alphaNumericCheck($_POST['type'])) {
				$valueType = $_POST['type'];
			} else return;
			
			if (isset($_POST['time'])) {
				// exact time of measurement
				$mytime = $_POST['time'];
			}

			if (isset($_POST['bc']) && is_numeric($_POST['bc'])) {
				// bootcount
				$bootcount = $_POST['bc'];
			}
			if (isset($_POST['value']) && is_numeric($_POST['value'])) {
				// value of measurement
				$value = $_POST['value'];
			}
			$this->dp("Device: $device, Value: $value, bootcount: $bootcount\n");
			echo $this->espdb->addNewMeasurementToDB($valueType, $device, $value, $mytime, $bootcount, $sensorID);
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
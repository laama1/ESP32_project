<?php
class UIForESP {
    
    protected $currentpage;
    protected $debug = 1;
    protected $espdb;
    

    public function __construct($arg = null) {
        $this->currentpage = "searchdevice";
        require_once('espdb.php');
        $this->espdb = new espdb;

        if (isset($_GET['page'])) {
            if ($_GET['page'] == 'searchdevice') {
                $this->currentpage = 'searchdevice';
            } else if ($_GET['page'] == 'addtemp') {
                $this->currentpage = 'addtemp';
            }
        }

        if (isset($_POST)) {
            # $this->dp("POST");
            # $this->da($_POST);
            if (isset($_POST['addtemp'])) {
                # add new word pair...
                $device = "";
                $value = -1000;
                $sensorID = -1000;
                $valueType = "undef";
                $bootcount;
                $mytime = time();

                if (isset($_POST['device']) && $this->alphaNumericCheck($_POST['device'])) {
                    $device = $_POST['device'];
                }
                if (isset($_POST['addtemp']) ) {
                    $value = $_POST['addtemp'];
                }
                if (isset($_POST['sensorid']) && $this->alphaNumericCheck($_POST['sensorid'])) {
                    $sensorID = $_POST['sensorid'];
                }

                if (isset($_POST['type']) && $this->alphaNumericCheck($_POST['type'])) {
                    $valueType = $_POST['type'];
                }
                
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
            } else if (isset($_POST['hakusana'])) {
                $searchword = $_POST['hakusana'];
                echo "Hakusanasi: $searchword <br><br>";
                $this->espdb->searchDeviceFromDB($searchword);
            }
        }
        if (isset($_GET)) {
            $this->dp("GET");
            $this->da($_GET);
            if (isset($_GET['devi'])) {
                # print all measurements of device
                $device = $_GET['devi'];
                #$this->dp("Device: $device");
                $newdevice = $this->espdb->searchDeviceFromDB($device);
                #$this->dp("Results from DB");
                #$this->da($newdevice);
                $this->drawMeasurementsOfDevice($device);
            }

        }

        # TODO: Print available devices
        $this->drawHeader();
        $this->drawForm($this->currentpage);
        $devices = $this->getAllDevices();
        $this->drawDeviceTable($devices);
        $this->drawFooter();
    }

    # debug print string
    private function dp($arg = null) {
        if ($arg === null || $this->debug == 0) return;
        echo $arg . "<br>\n";
    }

    # debug print array
    private function da($arg = null) {
        if ($arg === null || $this->debug == 0) return;
        echo "Debug arg: ";
        print_r($arg);
        echo "<br>\n";
    }

    private function alphaNumericCheck($arg = null) {
        if ($arg === null) return;
        #$this->dp("alphanumeric arg: $arg");
        return preg_match('/^[a-zA-Z0-9]*$/', $arg);
    }

    private function drawFooter() {
        echo "\n</body>
</html>\n";
    }
    private function drawHeader() {
        echo '<!DOCTYPE html>
<html>
<head>
    <title>IoT DataZ</title>';
    $this->drawCSS();
echo'</head>
<body>
';
    }

    private function getAllDevices() {
        return $this->espdb->getAllDevicesFromDB();
    }

    private function drawMeasurementsOfDevice($arg = null) {
        if ($arg === null) return;
        #$this->da($arg);
        $measurements = $this->espdb->getAllMeasurementsOfDevice($arg);
        #$this->da($measurements);
        #MEASUREMENTS (TYPE, VALUE, DEVICEID, SENSORID, DATETIME, DELETED, DEVICETIME, BOOTCOUNT
        echo'<br>';
        echo'<h2>Measurements of device</h2>';
        echo'<table class="measurementtable">
        <tr class="trheader"><th>Type</th><th>Value</th><th>Sensor ID</th><th>Bootcount</th><th>Date</th></tr>' . "\n";  # First row
        foreach ($measurements as $value) {
            $gmttime = gmdate("H:i:s, d.m.Y", $value['DATETIME']);
            echo'<tr>';
            echo'<td>'.$value['TYPE'].'</td>';
            echo'<td>'.$value['VALUE'].'</td>';
            echo'<td>'.$value['SENSORID'].'</td>';
            echo'<td>'.$value['BOOTCOUNT'].'</td>';
            echo'<td>'.$gmttime.'</td>';
            echo"</tr>\n";         
        }
        echo'
        </table>
        <br>';
    }


    # draw table with DEVICES from $arg
    private function drawDeviceTable($arg = null) {
        if ($arg === null) return;
        #$this->da($value);
        echo'<br>';
        echo'<h2>All devices</h2>';
        echo'<table class="devicetable">
        <tr class="trheader"><th>Device ID</th><th>Type</th><th>Nick</th></tr>' . "\n";  # First row
        foreach ($arg as $value) {
            echo'<tr>';
            echo'<td><a href="?devi='.$value['NAME'].'">'.$value['NAME'].'</a></td>';
            echo'<td>'.$value['TYPE'].'</td>';
            echo'<td>'.$value['NICK'].'</td>';
            echo"</tr>\n";
        }
        echo'
        </table>';
    }

    # draw html form for searching devices
    private function drawForm($arg = null) {
        if ($arg === null) return;

        echo '<form action = "esp_ui.php?page='.$arg.'" method="post">
        <fieldset>
        
        <!-- Form Name -->
        <legend>Lämpötilanen</legend>
        <br>
        <div class="form-group">
          <label class="col-md-4 control-label" for="textinput">Etsi laitteen nimellä</label>  
          <input id="textinput" name="hakusana" placeholder="Laite" class="form-control input-md" type="text">
        </div>
        <!-- Button -->
        <div class="form-group">
            <input value ="Etsi" id="etsibutton" name="etsibutton" class="btn btn-primary" type="submit">
        </div>
        
        </fieldset>
    </form>
        ';
        
    }

    private function drawCSS() {
        echo'
        <style>
        table.devicetable {
            background: #f6f6f6;
            border: 1px solid black;
            border-collapse: collapse;
        }
        tr.trheader {
            background: #f1f1f1;
            border: 1px solid black;
        }
        td {
            border: 1px solid black;
            padding-right: 1rem;
            padding-left: 1rem;
        }
        </style>';
    }
}

$esppi = new UIForESP;

?>
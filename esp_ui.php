<?php
class UIForESP {
    
    protected $currentpage;
    protected $debug = 0;
    protected $espdb;
    

    public function __construct($arg = null) {
        $this->currentpage = "searchdevice";
        require_once('espdb.php');
        $this->espdb = new espdb;

        if (isset($_GET['page'])) {
            if ($_GET['page'] == 'searchdevice') {
                $this->currentpage = 'searchdevice';
                #$this->dp("SEARCH");
            } else if ($_GET['page'] == 'addtemp') {
                $this->currentpage = 'addtemp';
                #$this->dp("ADD TEMP");
            }
        }

        if (isset($_POST)) {
            $this->dp("POST");
            $this->da($_POST);
            if (isset($_POST['addtemp'])) {
                # add new word pair...
                $device = $_POST['device'];
                $value = $_POST['addtemp'];
                $sensorID = $_POST['sensorid'];
                $bootcount;
                $mytime;
                if (isset($_POST['time'])) {
                    $mytime = $_POST['time'];
                }
                if (isset($_POST['bc']) && is_numeric($_POST['bc'])) {
                    $bootcount = $_POST['bc'];
                }
                
                $this->dp("Device: $device, Value: $value, bootcount: $bootcount\n");
                echo $this->espdb->addNewMeasurementToDB($device, $value, $mytime, $bootcount, $sensorID);
                return;
            } else if (isset($_POST['hakusana'])) {
                $searchword = $_POST['hakusana'];
                echo "Hakusanasi: $searchword <br><br>";
                $this->espdb->searchWordFromDB($searchword);
            }
        }
        # TODO: Print available devices
        $this->drawHeader();
        $this->drawForm($this->currentpage);
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
        print_r($arg);
        echo "<br>\n";
    }

    private function drawFooter() {
        echo'</body>
        </html>';
    }
    private function drawHeader() {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>IoT DataZ</title>
        </head>
        <body>
        ';
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
}

$esppi = new UIForESP;

?>
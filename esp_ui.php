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
            #$this->dp("GET SOMETHING");
            #$this->da($_GET);
            if ($_GET['page'] == 'searchdevice') {
                $this->currentpage = 'searchdevice';
                #$this->dp("SEARCH");
            } else if ($_GET['page'] == 'addtemp') {
                $this->currentpage = 'addtemp';
                #$this->dp("ADD WORDS");
            }
        }

        if (isset($_POST)) {
            $this->dp("POST");
            $this->da($_POST);
            if (isset($_POST['addtemp'])) {
                # add new word pair...
                $device = $_POST['device'];
                $value = $_POST['addtemp'];
                if (isset($_POST['time'])) {
                    $mytime = $_POST['time'];
                }
                
                echo "Device: $device, Temp: $value\n";
                echo $this->espdb->addNewMeasurementToDB($device, $value);
                //echo $this->sanakirja->addNewWordToDB($myword2, $_POST['lang2']);
                return;
            } else if (isset($_POST['hakusana'])) {
                $searchword = $_POST['hakusana'];
                echo "Hakusanasi: $searchword <br><br>";
                $this->espdb->searchWordFromDB($searchword);
            }
        }
        # TODO: Print available devices
        $this->drawForm($this->currentpage);
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

    private function printSearchWordBox($arg = null) {
        echo'

        ';
    }

}

$esppi = new UIForESP;

?>
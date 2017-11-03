#include <HTTPClient.h>

#include <OneWire.h>

#include <WiFiClient.h>
#include <WiFi.h>
 
const char* ssid = "----";
const char* password = "----";


/// SLEEP STUFF
#define uS_TO_S_FACTOR 1000000      /* Conversion factor for micro seconds to seconds */
#define TIME_TO_SLEEP  50         /* Time ESP32 will go to sleep (in seconds) */
RTC_DATA_ATTR int bootCount = 0;
RTC_DATA_ATTR unsigned long lastConnectionTime = 0;            // last time you connected to the server, in milliseconds

//const char* serverip = "10.42.2.57";
bool debug = 1;
bool disconnectBit = false;     // abort connecting when this is "true"

const unsigned long postingInterval = 20L * 1000L; // delay between updates, in milliseconds

int ledPin = 13; // GPIO13
OneWire  ds(2);  // on pin 2 (a 4.7K resistor is necessary)
uint64_t chipid;


/// TIMER STUFF
volatile SemaphoreHandle_t timerSemaphore;
hw_timer_t * timer = NULL;
portMUX_TYPE timerMux = portMUX_INITIALIZER_UNLOCKED;

void IRAM_ATTR onTimer(){
  portENTER_CRITICAL_ISR(&timerMux);
  
  digitalWrite(ledPin, LOW);
  portEXIT_CRITICAL_ISR(&timerMux);
  // Give a semaphore that we can check in the loop
  xSemaphoreGiveFromISR(timerSemaphore, NULL);
  
}

void printSerial(String printable) {
  if (debug == 0) return;
  Serial.println(printable);
  delay(15);
}

void printSerial2(String printable) {
  if (debug == 0) return;
  Serial.print(printable);
  //delay(20);
}

void setup() {
  
  if (debug) {
    Serial.begin(115200);
    while (!Serial) {
      ; // wait for serial port to connect. Needed for native USB port only
    }
  }
    
  //Increment boot number and print it every reboot
  ++bootCount;

  printSerial("");
  printSerial("Hello!\n Boot number: " + String(bootCount));
  //Print the wakeup reason for ESP32
  print_wakeup_reason();

  /*
  First we configure the wake up source
  We set our ESP32 to wake up every TIME_TO_SLEEP seconds
  */
  esp_sleep_enable_timer_wakeup(TIME_TO_SLEEP * uS_TO_S_FACTOR);
  printSerial("Setup ESP32 to wake up " + String(TIME_TO_SLEEP) + " seconds after sleep.");


  /// TIMER STUFF
  // Create semaphore to inform us when the timer has fired
  timerSemaphore = xSemaphoreCreateBinary();
  // Use 1st timer of 4 (counted from zero).
  // Set 80 divider for prescaler (see ESP32 Technical Reference Manual for more info).
  timer = timerBegin(0, 80, true);
  // Attach onTimer function to our timer.
  timerAttachInterrupt(timer, &onTimer, true);

  // delete old config
  WiFi.disconnect(true);
  WiFi.onEvent(WiFiEvent);
  
  pinMode(ledPin, OUTPUT);
  
  blinkLed(200);

}

// Param: delaytime in milliseconds
void blinkLed(int delaytime) {
  printSerial("Blink LED!");
  // Set alarm to call onTimer function every second (value in microseconds).
  // Repeat the alarm (third parameter)
  timerAlarmWrite(timer, 1000 * delaytime, false);

  // Start an alarm
  timerAlarmEnable(timer);

  digitalWrite(ledPin, HIGH);
}

/* LIST of WIFI EVENTS
typedef enum {
    SYSTEM_EVENT_WIFI_READY = 0,           < ESP32 WiFi ready
    SYSTEM_EVENT_SCAN_DONE,                < ESP32 finish scanning AP
    SYSTEM_EVENT_STA_START,                < ESP32 station start
    SYSTEM_EVENT_STA_STOP,                 < ESP32 station stop
    SYSTEM_EVENT_STA_CONNECTED,            < ESP32 station connected to AP
    SYSTEM_EVENT_STA_DISCONNECTED,         < ESP32 station disconnected from AP
    SYSTEM_EVENT_STA_AUTHMODE_CHANGE,      < the auth mode of AP connected by ESP32 station changed
    SYSTEM_EVENT_STA_GOT_IP,               < ESP32 station got IP from connected AP
    SYSTEM_EVENT_STA_LOST_IP,              < ESP32 station lost IP and the IP is reset to 0
    SYSTEM_EVENT_STA_WPS_ER_SUCCESS,       < ESP32 station wps succeeds in enrollee mode
    SYSTEM_EVENT_STA_WPS_ER_FAILED,        < ESP32 station wps fails in enrollee mode
    SYSTEM_EVENT_STA_WPS_ER_TIMEOUT,       < ESP32 station wps timeout in enrollee mode
    SYSTEM_EVENT_STA_WPS_ER_PIN,           < ESP32 station wps pin code in enrollee mode
    SYSTEM_EVENT_AP_START,                 < ESP32 soft-AP start
    SYSTEM_EVENT_AP_STOP,                  < ESP32 soft-AP stop
    SYSTEM_EVENT_AP_STACONNECTED,          < a station connected to ESP32 soft-AP
    SYSTEM_EVENT_AP_STADISCONNECTED,       < a station disconnected from ESP32 soft-AP
    SYSTEM_EVENT_AP_PROBEREQRECVED,        < Receive probe request packet in soft-AP interface
    SYSTEM_EVENT_AP_STA_GOT_IP6,           < ESP32 station or ap interface v6IP addr is preferred
    SYSTEM_EVENT_ETH_START,                < ESP32 ethernet start
    SYSTEM_EVENT_ETH_STOP,                 < ESP32 ethernet stop
    SYSTEM_EVENT_ETH_CONNECTED,            < ESP32 ethernet phy link up
    SYSTEM_EVENT_ETH_DISCONNECTED,         < ESP32 ethernet phy link down
    SYSTEM_EVENT_ETH_GOT_IP,               < ESP32 ethernet got IP from connected AP
    SYSTEM_EVENT_MAX
} system_event_id_t;

 */


void WiFiEvent(WiFiEvent_t event) {
    //Serial.printf("[WiFi-event] event: %d\n", event);

    switch(event) {

    case SYSTEM_EVENT_WIFI_READY:
      printSerial("ESP32 WiFi ready.");
      break;
    case SYSTEM_EVENT_SCAN_DONE:
        printSerial("ESP32 finish scanning AP.");
        break;
    case SYSTEM_EVENT_STA_START:
      printSerial("ESP32 station start.");
      break;
    case SYSTEM_EVENT_STA_STOP:
      printSerial("ESP32 station stop.");
      break;
    case SYSTEM_EVENT_STA_CONNECTED:
      printSerial("ESP32 station connected to AP.");
      disconnectBit = false;
      break;
    case SYSTEM_EVENT_STA_DISCONNECTED:
      printSerial("ESP32 station disconnected from AP.");
      // TODO: Reconnect couple of times
      //WiFi.reconnect();
      disconnectBit = true;
      break;
    case SYSTEM_EVENT_STA_AUTHMODE_CHANGE:
      printSerial("the auth mode of AP connected by ESP32 station changed.");
      break;
    
    case SYSTEM_EVENT_STA_GOT_IP:
        printSerial2("ESP32 station got IP from connected AP. ");
        printSerial2("IP address: ");
        if (debug) {
          Serial.println(WiFi.localIP());
        }
        break;
    case SYSTEM_EVENT_STA_LOST_IP:
      printSerial("ESP32 station lost IP and the IP is reset to 0.");
      break;
    case SYSTEM_EVENT_STA_WPS_ER_SUCCESS:
      printSerial("ESP32 station wps succeeds in enrollee mode.");
      break;
    case SYSTEM_EVENT_STA_WPS_ER_FAILED:
      printSerial("ESP32 station wps fails in enrollee mode.");
      break;
    case SYSTEM_EVENT_STA_WPS_ER_TIMEOUT:
      printSerial("ESP32 station wps timeout in enrollee mode.");
      break;
    case SYSTEM_EVENT_STA_WPS_ER_PIN:
      printSerial("ESP32 station wps pin code in enrollee mode.");
      break;
    case SYSTEM_EVENT_AP_START:
      printSerial("ESP32 soft-AP start.");
      break;
    case SYSTEM_EVENT_AP_STOP:
      printSerial("ESP32 soft-AP stop.");
      break;
    case SYSTEM_EVENT_AP_STACONNECTED:
      printSerial("a station connected to ESP32 soft-AP.");
      break;
    case SYSTEM_EVENT_AP_STADISCONNECTED:
      printSerial("a station disconnected from ESP32 soft-AP.");
      break;    
    case SYSTEM_EVENT_AP_PROBEREQRECVED:
      printSerial("Receive probe request packet in soft-AP interface.");
      break;
    case SYSTEM_EVENT_AP_STA_GOT_IP6:
      printSerial("ESP32 station or ap interface v6IP addr is preferred.");
      break;
    case SYSTEM_EVENT_ETH_START:
      printSerial("ESP32 ethernet start.");
      break;
    case SYSTEM_EVENT_ETH_STOP:
      printSerial("ESP32 ethernet stop.");
      break;
    case SYSTEM_EVENT_ETH_CONNECTED:
      printSerial("ESP32 ethernet phy link up.");
      break;
    case SYSTEM_EVENT_ETH_DISCONNECTED:
      printSerial("ESP32 ethernet phy link down.");
      break;
    case SYSTEM_EVENT_ETH_GOT_IP:
      printSerial("ESP32 ethernet got IP from connected AP");
      break;
    case SYSTEM_EVENT_MAX:
      printSerial("Event: MAX");
      break;
    
    default:
        break;
    }
}

#define ENC_TYPE_WEP 5
#define ENC_TYPE_TKIP 2
#define ENC_TYPE_CCMP 4
#define ENC_TYPE_NONE 7
#define ENC_TYPE_AUTO 8


void printEncryptionType(int thisType) {
  // read the encryption type and print out the name:
  switch (thisType) {
    case ENC_TYPE_WEP:
      printSerial("WEP");
      break;
    case ENC_TYPE_TKIP:
      printSerial("WPA");
      break;
    case ENC_TYPE_CCMP:
      printSerial("WPA2");
      break;
    case ENC_TYPE_NONE:
      printSerial("None");
      break;
    case ENC_TYPE_AUTO:
      printSerial("Auto");
      break;
  }
}

float measureTemp(byte* addr) {
  printSerial("Measuring temperature next.");
  delay(350); // oli: 350
  byte i;
  byte present = 0;
  byte type_s;
  byte data[12];
  //byte addr[8];
  float celsius, fahrenheit;
    
  //for (int icount = 0; icount<3; icount++) {
    if ( !ds.search(addr)) {
      printSerial("No more addresses.");
      printSerial("");
      ds.reset_search();
      delay(250);
      //return (-1000.0);
    } else {
      /**/
      if (debug) {
        printSerial2("ROM =");
        for( i = 0; i < 8; i++) {
          printSerial2(" ");
          printSerial2(String(addr[i], HEX));
        }
        printSerial("");
        if (OneWire::crc8(addr, 7) != addr[7]) {
          printSerial("CRC is not valid!");
          return -1000.0;
        }
        printSerial("");
   
        // the first ROM byte indicates which chip
        switch (addr[0]) {
          case 0x10:
            printSerial("  Chip = DS18S20");  // or old DS1820
            type_s = 1;
          break;
          case 0x28:
            printSerial("  Chip = DS18B20");
            type_s = 0;
          break;
          case 0x22:
            printSerial("  Chip = DS1822");
            type_s = 0;
          break;
          default:
            printSerial("Device is not a DS18x20 family device.");
            return -1000.0;
          break;
        }      
    
      } // end of debug
      

      ds.reset();
      ds.select(addr);
      ds.write(0x44, 0);      // start conversion, with parasite power off at the end
      delay(800);             // maybe 750ms is enough, maybe not
      //delay(1000);
  
      present = ds.reset();
      ds.select(addr);    
      ds.write(0xBE);         // Read Scratchpad

      printSerial2("  Data = ");
      printSerial2(String(present, HEX));
      printSerial2(" ");
  
      for ( i = 0; i < 9; i++) {           // we need 9 bytes
        data[i] = ds.read();
        printSerial2(String(data[i], HEX));
        printSerial2(" ");
      }
      
      printSerial2(" CRC=");
      printSerial2(String(OneWire::crc8(data, 8), HEX));
      printSerial2("");
      
      // Convert the data to actual temperature
      // because the result is a 16 bit signed integer, it should
      // be stored to an "int16_t" type, which is always 16 bits
      // even when compiled on a 32 bit processor.
      int16_t raw = (data[1] << 8) | data[0];
      if (type_s) {
        raw = raw << 3; // 9 bit resolution default
        if (data[7] == 0x10) {
          // "count remain" gives full 12 bit resolution
          raw = (raw & 0xFFF0) + 12 - data[6];
        }
      } else {
        byte cfg = (data[4] & 0x60);
        // at lower res, the low bits are undefined, so let's zero them
        if (cfg == 0x00) raw = raw & ~7;  // 9 bit resolution, 93.75 ms
        else if (cfg == 0x20) raw = raw & ~3; // 10 bit res, 187.5 ms
        else if (cfg == 0x40) raw = raw & ~1; // 11 bit res, 375 ms
        //// default is 12 bit resolution, 750 ms conversion time
      }
      celsius = (float)raw / 16.0;
      fahrenheit = celsius * 1.8 + 32.0;
      printSerial("");
      printSerial2("  Temp: ");
      //Serial.print(i);
      //Serial.print(" ");
      printSerial2(String(celsius));
      printSerial2(" C, ");
      printSerial2(String(fahrenheit));
      printSerial(" F");
      if (celsius < 300 && celsius > -300) {
        return celsius;  
      }
      
    }
  //}
  return -1000.0;
}

#define SENSOR_TEMP 1
#define SENSOR_HUMIDITY 2
#define SENSOR_XYZ 3
 
void loop() {
  static byte addr[8];
  for (int counter = 0; counter < 3; counter++) {
    printSerial("Loop counter: " + String(counter));
    float celsius = measureTemp(&addr[0]);
    // for later use if (millis() - lastConnectionTime > postingInterval && celsius != -1000) {
    if (celsius != -1000.0) {
      putInfo(celsius, &addr[0], SENSOR_TEMP);
    } else {
      //return;
    }
  }
  WiFi.disconnect();
  digitalWrite(ledPin, LOW);
  printSerial("Going to sleep now");
  delay(100);
  timerEnd(timer);
  timer = NULL;
  esp_deep_sleep_start();
  
  printSerial("This will never be printed");
}

String returnDeviceIDasString() {
  //chipid=ESP.getEfuseMac();       //The chip ID is essentially its MAC address(length: 6 bytes).
  //String returnString = printf("%04X",(uint16_t)(chipid>>32));  // print High 2bytes
  //returnString += printf("%08X",(uint32_t)chipid);              // print Low 4bytes.
  
  byte mac[6] = {'\0'};
  WiFi.macAddress(mac);
  String returnString = String(mac[6],HEX) + String(mac[5], HEX) + String(mac[4], HEX) + String(mac[3],HEX) + String(mac[2],HEX) + String(mac[1],HEX) + String(mac[0],HEX);
  return returnString;
}

String returnSensorIDasString(byte* addr) {
  String returnvalue;
  for( int i = 0; i < 8; i++) {
     returnvalue += (String(addr[i], HEX));
  }
  return returnvalue;
}

void putInfo(float temperature, byte* addr, uint8_t type) {
  HTTPClient http;
  printSerial("Send info to server...");

  if (WiFi.status() != WL_CONNECTED) {
    WiFi.begin(ssid, password);  
  }
  
  int wait_time = 0;
  while (WiFi.status() != WL_CONNECTED && wait_time < 1000 && disconnectBit == false) {
    delay(100);
    printSerial2(".");
    wait_time++;
  }
  printSerial("");
  if (disconnectBit == true) return;
  /*
  if (!client.connect(host, httpPort)) {
    Serial.println("Connection to server failed.");
    delay(100);
    return;
  }
  */
  char * sensortype;
  switch(type) {
    case SENSOR_TEMP:
      sensortype = "temp";
    break;
    case SENSOR_HUMIDITY:
      sensortype = "humi";
    break;
    default:
    break;
  }
  String postString ="&addtemp=" + String(temperature) + "&device=" + returnDeviceIDasString() + "&bc="
    + String(bootCount) + "&sensorid=" + returnSensorIDasString(addr) + "&type=" + sensortype;
  
  http.begin("http://10.42.2.57/~laama/esp/esp_ui.php");
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  printSerial("POST line: " + postString);
  int httpCode = http.POST(postString);
  // httpCode will be negative on error
  if(httpCode > 0) {
      // HTTP header has been send and Server response header has been handled
      if (debug) Serial.printf("[HTTP] POST... code: %d\n", httpCode);
  
      // file found at server
      if(httpCode == HTTP_CODE_OK && debug == 1) {
          String payload = http.getString();
          printSerial(payload);
      }
  } else {
      if (debug) Serial.printf("[HTTP] POST... failed, error: %s\n", http.errorToString(httpCode).c_str());
  }

  lastConnectionTime = lastConnectionTime + millis();
  printSerial("Closing connection. Time: " + String(lastConnectionTime));
}


//
//  SLEEP CODE
//

 /*
Method to print the reason by which ESP32
has been awaken from sleep
*/
void print_wakeup_reason() {
  esp_sleep_wakeup_cause_t wakeup_reason;

  wakeup_reason = esp_sleep_get_wakeup_cause();

  switch(wakeup_reason)
  {
    case 1  : printSerial("Wakeup caused by external signal using RTC_IO."); break;
    case 2  : printSerial("Wakeup caused by external signal using RTC_CNTL."); break;
    case 3  : printSerial("Wakeup caused by timer."); break;
    case 4  : printSerial("Wakeup caused by touchpad."); break;
    case 5  : printSerial("Wakeup caused by ULP program."); break;
    default : printSerial("Wakeup was not caused by deep sleep."); break;
  }
}

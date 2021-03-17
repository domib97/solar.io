#!/usr/bin/php
<?php
/******************************************************************************
//  Mit diesem Script wird der richtige USB Port initialisiert.
//  Bitte nur Änderungen vornehmen, wenn man sich auskennt.
//  Version 16.8.2018
******************************************************************************/
$path_parts = pathinfo($argv[0]);
$Pfad = $path_parts['dirname'];
require($Pfad."/user.config.php");
require($Pfad."/phpinc/funktionen.inc.php");
$Tracelevel = 10; //  1 bis 10  10 = Debug

$funktionen = new funktionen();

/******************************************************************************
//  Zuerst prüfen, ob der Regler bekannt ist.
//
******************************************************************************/
if (isset($USBDevice) and !empty($USBDevice)) {
  $funktionen->log_schreiben("Device: ".$USBDevice." wurde in der user.config angegeben.","   ",6);
}
$USB_Ausgabe = shell_exec ( "usb-devices");
$funktionen->log_schreiben($USB_Ausgabe,"   ",8);
$USB_Ausgabe = explode("\n\n",shell_exec ( "hwinfo --usb --partition"));

$m = 1;
$n = 0;

for ($i=0; $i < count($USB_Ausgabe); $i++) {
  //  Wieviele Einträge für USB Devices gibt es in der Hardware Info?
    $Teile = explode("\n",$USB_Ausgabe[$i]);
  for ($k=0; $k < count($Teile); $k++) {
    $Daten[$i][trim(substr($Teile[$k],0,strpos($Teile[$k], ":")))] = trim(substr($Teile[$k],(strpos($Teile[$k], ":")+1)));
    //  Sind Einträge mit "USB" und "HOTPLUG" vorhanden, dann könnte es sich auch um
    //  eine GPS Maus handeln.

    if (isset($Daten[$i]["Hotplug"]) and $Daten[$i]["Hotplug"] == "USB" and isset($Daten[$i]["Device File"])) {

      if (isset($Daten[$i]["Device File"])) {

        $USB_Devices[$m]["File"] = $Daten[$i]["Device File"];
      }
      if (isset($Daten[$i]["Vendor"])) {
        $USB_Devices[$m]["Vendor"] = trim($Daten[$i]["Vendor"]);
      }
      if (isset($Daten[$i]["Device"])) {
        $Device = explode(" ",trim($Daten[$i]["Device"]));

        $USB_Devices[$m]["Device"] = trim($Device[1]);
        if ($Device[1] == "0x2303") {
          // Einfacher serierller Adapter
          $USB_Regler = $USB_Devices[$m]["File"];
        }
        elseif ($Device[1] == "0x6015") {
          // Victron Energy
          $USB_Devices[$m]["Serial ID"] = trim($Daten[$i]["Serial ID"], "\t\n\r\0\x0B\"");
          if (substr($USB_Devices[$m]["Serial ID"],0,2) == "HQ") {
	        // MK3 MultiPlus Adapter
            $USB_WR = $USB_Devices[$m]["File"];
            $funktionen->log_schreiben("MK3 MultiPlus Adapter erkannt.","   ",5);
          }
          if (substr($USB_Devices[$m]["Serial ID"],0,2) == "VE" ) {
            //  VE.direct Kabel
            $USB_Regler = $USB_Devices[$m]["File"];
            $funktionen->log_schreiben("VE.Direct Adapterkabel erkannt.","   ",5);
          }
        }
        elseif ($Device[1] == "0x6001") {
          // Micro-Wechselrichter von AEconversion
          // Pylotech US2000 Plus BMS
          // RS485 Adapter
          $USB_Regler = $USB_Devices[$m]["File"];
        }
        elseif ($Device[1] == "0x0005") {
          // SCPlus von ivt-Hirschau
          $USB_Regler = $USB_Devices[$m]["File"];
        }
        elseif ($Device[1] == "0x1400") {
          // AutarcTech BMS  Batterie Balancer
          $USB_Regler = $USB_Devices[$m]["File"];
        }
        elseif ($Device[1] == "0x10c4") {
          //  Pylontech US2000Plus Batteriespeicher
          //  Tracer Serie
          //  RS485 Adapter
          $USB_Regler = $USB_Devices[$m]["File"];
        }
        elseif ($Device[1] == "0xea60") {
          //  OEM
          //  RS485 Adapter
          $USB_Regler = $USB_Devices[$m]["File"];
        }
        elseif ($Device[1] == "0x7523") {
          //  esmart3 Ladereler
          //  RS485 Adapter
          $USB_Regler = $USB_Devices[$m]["File"];
        }
      }
      if (isset($Daten[$i]["Model"])) {
        $USB_Devices[$m]["Model"] = trim($Daten[$i]["Model"], "\t\n\r\0\x0B\"");
      }
      if (isset($Daten[$i]["Driver Modules"])) {
        $USB_Devices[$m]["Driver Modules"] = str_replace ("\"", "", trim($Daten[$i]["Driver Modules"]));
      }
      if (isset($Daten[$i]["Driver"])) {
        $USB_Devices[$m]["Driver"] = trim($Daten[$i]["Driver"], "\t\n\r\0\x0B\"");
      }
      if (isset($Daten[$i]["Attached to"])) {
        $m++;
      }
    }
    elseif (isset($Daten[$i]["Hotplug"]) and $Daten[$i]["Hotplug"] == "USB" ) {
      if (isset($Daten[$i]["Device"])) {
        $Device = explode(" ",trim($Daten[$i]["Device"]));
        $USB_Devices[$m]["Device"] = trim($Device[1]);
        if ($Device[1] == "0x5161") {
          // Solarix PLI 5000-48 von Steca
          $USB_hidraw = explode("\n",shell_exec ("cat /sys/class/hidraw/hidraw".$n."/device/uevent"));
          $funktionen->log_schreiben("USB HIDRAW:   /dev/hidraw".$n."   ".$USB_hidraw[2],"   ",5);
          if (substr(trim($USB_hidraw[2]),-9) == "0665:5161") {
            $USB_Regler = "/dev/hidraw".$n;
            $funktionen->log_schreiben("OK. Das ist er:   /dev/hidraw".$n."   ".$USB_hidraw[2],"   ",5);
          }
          $n++;
        }
      }
    }
  }
  $funktionen->log_schreiben("Daten:\n".var_export($Daten[$i],1),"   ",6);
}
$funktionen->log_schreiben("USB Devices: \n".var_export($USB_Devices,1),"   ",6);

$funktionen->log_schreiben("Regler: ".$Regler,"   ",6);

if (isset($USB_WR)) {
  $funktionen->log_schreiben("Wechselrichter erkannt: ".$USB_WR,"   ",6);
}

switch ($Regler) {
  case 1:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyACM0";
    }

    // ivt-Hirschau Regler SCPlus und SCDPlus
    $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 2:
    if (isset($USB_Regler)) {
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
      $USBDevice = $USB_Regler;
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Steca Regler Tarom 4545 und Tarom 6000-S
    $rc = exec("stty -F  ".$USBDevice."  38400 cs8 raw -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 3:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Tracer Serie
    $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
  break;
  case 4:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Victron-energy Regler der Serie BlueSolar
    $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    if (isset($USB_WR)) {
      // Das MK3 Interface für den MultiPlus Wechselrichter einrichten
      $rc = exec("stty -F  ".$USB_WR."  raw speed 2400 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    }
  break;
  case 5:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Micro-Wechselrichter von AEconversion
    if ($HF2211 === false) {
      $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    }
  break;
  case 6:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Victron-energy Batterie Wächter BMV 700
    $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 7:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/hidraw0";
    }
    //  Für baugleiche Geräte mit seriellem Anschluss
    if (substr($USB_Regler, 0, 11)   == "/dev/ttyUSB") {
      $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    }
    // Steca Solarix PLI 5000-48
  break;
  case 8:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/hidraw0";
    }
    // InfiniSolar V Serie
  break;
  case 9:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/hidraw0";
    }
    // MPPSolar MPI Hybrid 3 Phasen
  break;
  case 10:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // SolarMax Ethernet Anschluss
  break;
  case 11:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Victron-energy Phoenix Wechselrichter
    $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 12:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Fronius Symo Serie - Ethernet Anschluss
  break;
  case 13:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Autarctech BMS
      $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 14:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }

    // Rover von Renogy
      $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 15:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Pylontech US20000B
      $rc = exec("stty -F  ".$USBDevice."  raw speed 1200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 16:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // SolarEdge Ethernet LAN Anschluss
  break;
  case 17:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // KOSTAL Plenticore Ethernet LAN Anschluss
  break;
  case 18:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // S10 Wechselrichter von E3/DC mit Ethernet LAN Anschluss
  break;
  case 19:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    //
    if ($HF2211 === false) {
      $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    }
    // eSmart3 Laderegler mit RS485 Interface
  break;
  case 20:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // SolarEdge Ethernet LAN Anschluss
  break;
  case 21:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Kostal Pico mit RS485 Anschluss
    if ($HF2211 === false) {
      $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
    }
  break;
  case 22:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // SolarEdge Ethernet LAN Anschluss
  break;
  case 23:
    $USBRegler = "MQTT";     // Dummy
    $USBDevice = "MQTT";
    // Sonoff POW R2
  break;
  case 24:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/hidraw0";
    }
    // Infini 3KW Hybrid Wechselrichter
  break;
  case 25:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Sonnen Batterie Ethernet LAN Anschluss
  break;
  case 26:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "/dev/hidraw0";
    }
    // MPPSolar 5048 MK und GK Serie
  break;
  case 27:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // SMA  Sunny Tripower mit LAN Anschluss
  break;
  case 28:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // HRDi marlec Laderegler
      $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 29:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // go-e Charger Wallbox
  break;
  case 30:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Keba Wallbox
  break;
  case 31:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Shelly 3EM
  break;
  case 32:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // KACO Wechselrichter
  break;
  case 33:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Labornetzteil
      $rc = exec("stty -F  ".$USBDevice."  raw speed 38400 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 34:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // SDM630  Smart Meter
    $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 35:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // go-e Charger Wallbox
  break;
  case 36:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Delta Wechselrichter RS485
    $rc = exec("stty -F  ".$USBDevice." raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;
  case 37:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Simple EVSE WiFi Wallbox
  break;
  case 38:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // ALPHA ESS T10 Wechselrichter
    $rc = exec("stty -F  ".$USBDevice." raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 39:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // openWB Wallbox
  break;

  case 40:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Phocos Wechselrichter RS485
    $rc = exec("stty -F  ".$USBDevice." raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 41:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Pylontech US3000A
    $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 42:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Pv18 VHM Wechselrichter mit RS485 Schnittstelle
    $rc = exec("stty -F  ".$USBDevice." raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 43:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Senec Stromspeicher
  break;

  case 44:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Webasto Wallbox
  break;

  case 45:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Phocos Any-Grid
    $rc = exec("stty -F  ".$USBDevice." raw speed 2400 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;


  case 46:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Huawei Wechselrichter
    $rc = exec("stty -F  ".$USBDevice." raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 47:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Phoenix Contact Wallbox
  break;

  case 48:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // Growatt Wechselrichter
    $rc = exec("stty -F  ".$USBDevice." raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;

  case 49:
    $USBRegler = "Ethernet";     // Dummy
    $USBDevice = "Ethernet";
    // Huawei SmartLogger 3000
  break;

  case 50:
    if (isset($USB_Regler)) {
      $USBDevice = $USB_Regler;
      $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
    }
    elseif (!isset($USBDevice) or empty($USBDevice) ) {
      $USBDevice = "/dev/ttyUSB0";
    }
    // SDM630  Smart Meter
    $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
  break;


  default:
    // echo "Fehler! Es muss ein gültiger Regler angegeben werden.";
  break;
}


//  Auf welcher Platine läuft die Software?
$Platine = file_get_contents("/sys/firmware/devicetree/base/model");

if (!empty($USBDevice)) {

  $funktionen->log_schreiben("Device: ".$USBDevice." wird in die user.config.php geschrieben.","   ",6);

  $INI = file($Pfad."/user.config.php");

  foreach($INI as $index => $item){
    if(strpos($item,'$USBRegler')!== false){
      $funktionen->log_schreiben("Zeile gefunden. Device kann ausgetauscht werden. Index: ".$index."   ".$INI[$index],"   ",5);
      $Zeile1 = $index;
    }
    elseif(strpos($item,'$USBWechselrichter')!== false){
      $funktionen->log_schreiben("Zeile gefunden. Device kann ausgetauscht werden. Index: ".$index."   ".$INI[$index],"   ",5);
      $Zeile2 = $index;
    }
    elseif(strpos($item,'$Platine')!== false){
      $funktionen->log_schreiben("Zeile gefunden. Platine kann ausgetauscht werden. Index: ".$index."   ".$INI[$index],"   ",5);
      $Zeile3 = $index;
    }
  }

  $INI[$Zeile1] = "\$USBRegler         = \"".$USBDevice."\";\n";
  if (isset($USB_WR)) {
    $INI[$Zeile2] = "\$USBWechselrichter = \"".$USB_WR."\";\n";
  }
  if (isset($Zeile3)) {
    $INI[$Zeile3] = "\$Platine = \"".trim($Platine)."\";\n";
  }

  $rc = file_put_contents ($Pfad."/user.config.php", $INI ) ;

}

exit;
?>
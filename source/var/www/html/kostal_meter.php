#!/usr/bin/php
<?php

/*****************************************************************************
//  Solaranzeige Projekt             Copyright (C) [2016-2020]  [Ulrich Kunz]
//
//  Dieses Programm ist freie Software. Sie können es unter den Bedingungen
//  der GNU General Public License, wie von der Free Software Foundation
//  veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
//  Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.
//
//  Die Veröffentlichung dieses Programms erfolgt in der Hoffnung, daß es
//  Ihnen von Nutzen sein wird, aber OHNE IRGENDEINE GARANTIE, sogar ohne
//  die implizite Garantie der MARKTREIFE oder der VERWENDBARKEIT FÜR EINEN
//  BESTIMMTEN ZWECK. Details finden Sie in der GNU General Public License.
//
//  Ein original Exemplar der GNU General Public License finden Sie hier:
//  http://www.gnu.org/licenses/
//
//  Dies ist ein Programmteil des Programms "Solaranzeige"
//
//  Es dient dem Auslesen des KOSTAL Smart Meter über die LAN Schnittstelle
//  Das Auslesen wird hier mit einer Schleife durchgeführt. Wie oft die Daten
//  ausgelesen und gespeichert werden steht in der user.config.php
//
//
*****************************************************************************/
$path_parts = pathinfo($argv[0]);
$Pfad = $path_parts['dirname'];
if (!is_file($Pfad."/1.user.config.php")) {
  // Handelt es sich um ein Multi Regler System?
  require($Pfad."/user.config.php");
}

require_once($Pfad."/phpinc/funktionen.inc.php");
if (!isset($funktionen)) {
  $funktionen = new funktionen();
}
// Im Fall, dass man die Device manuell eingeben muss
if (isset($USBDevice) and !empty($USBDevice)) {
  $USBRegler = $USBDevice;
}

$Tracelevel = 7;  //  1 bis 10  10 = Debug
$RemoteDaten = true;
$Device = "MT"; // MT = Meter
$Version = "";
$Start = time();  // Timestamp festhalten
setlocale(LC_TIME,"de_DE.utf8");


$funktionen->log_schreiben("-------------   Start  kostal_meter.php    -------------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",8);
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;


//  Hardware Version ermitteln.
$Teile =  explode(" ",$Platine);
if ($Teile[1] == "Pi") {
  $Version = trim($Teile[2]);
  if ($Teile[3] == "Model") {
    $Version .= trim($Teile[4]);
    if ($Teile[5] == "Plus") {
      $Version .= trim($Teile[5]);
    }
  }
}
$funktionen->log_schreiben("Hardware Version: ".$Version,"o  ",1);

switch($Version) {
  case "2B":
  break;
  case "3B":
  break;
  case "3BPlus":
  break;
  case "4B":
  break;
  default:
  break;
}


$COM1 = fsockopen($WR_IP, $WR_Port, $errno, $errstr, 5);   // 5 = Timeout in Sekunden
if (!is_resource($COM1)) {
  $funktionen->log_schreiben("Kein Kontakt zum Wechselrichter ".$WR_IP."  Port: ".$WR_Port,"XX ",3);
  $funktionen->log_schreiben("Exit.... ","XX ",3);
  goto Ausgang;
}

$i = 1;
do {
  $funktionen->log_schreiben("Die Daten werden ausgelesen...","+  ",9);



  /****************************************************************************
  //  Ab hier wird der Zähler ausgelesen.
  //
  ****************************************************************************/

  $rc = $funktionen->kostal_register_lesen($COM1,"2003","0001","");
  $aktuelleDaten["Firmware"] = hexdec($rc["Wert"]);
  $rc = $funktionen->kostal_register_lesen($COM1,"2014","0010","String2");
  $aktuelleDaten["Produkt"] = trim($rc["Wert"]);
  $rc = $funktionen->kostal_register_lesen($COM1,"2024","0010","String2");
  $aktuelleDaten["Seriennummer"] = trim($rc["Wert"]);



  $rc = $funktionen->kostal_register_lesen($COM1,"0018","0002","U32");
  $aktuelleDaten["Leistungsfaktor"] = $rc["Wert"]/1000;
  $rc = $funktionen->kostal_register_lesen($COM1,"0000","0002","U32");
  $aktuelleDaten["Active_powerP"] = $rc["Wert"]/10;
  $rc = $funktionen->kostal_register_lesen($COM1,"0002","0002","U32");
  $aktuelleDaten["Active_powerM"] = $rc["Wert"]/10;

  $rc = $funktionen->kostal_register_lesen($COM1,"0004","0002","U32");
  $aktuelleDaten["Reactive_powerP"] = $rc["Wert"]/10;
  $rc = $funktionen->kostal_register_lesen($COM1,"0006","0002","U32");
  $aktuelleDaten["Reactive_powerM"] = $rc["Wert"]/10;

  $rc = $funktionen->kostal_register_lesen($COM1,"0010","0002","U32");
  $aktuelleDaten["Apparent_powerP"] = $rc["Wert"]/10;
  $rc = $funktionen->kostal_register_lesen($COM1,"0012","0002","U32");
  $aktuelleDaten["Apparent_powerM"] = $rc["Wert"]/10;



  $rc = $funktionen->kostal_register_lesen($COM1,"0200","0004","U64");
  $aktuelleDaten["Active_energyP"] = $rc["Wert"]/10;
  $rc = $funktionen->kostal_register_lesen($COM1,"0204","0004","U64");
  $aktuelleDaten["Active_energyM"] = $rc["Wert"]/10;

  $rc = $funktionen->kostal_register_lesen($COM1,"0208","0004","U64");
  $aktuelleDaten["Reactive_energyP"] = $rc["Wert"]/10;
  $rc = $funktionen->kostal_register_lesen($COM1,"020C","0004","U64");
  $aktuelleDaten["Reactive_energyM"] = $rc["Wert"]/10;

  $rc = $funktionen->kostal_register_lesen($COM1,"0220","0004","U64");
  $aktuelleDaten["Apparent_energyP"] = $rc["Wert"]/10;
  $rc = $funktionen->kostal_register_lesen($COM1,"0224","0004","U64");
  $aktuelleDaten["Apparent_energyM"] = $rc["Wert"]/10;


  // $rc = $funktionen->kostal_register_lesen($COM1,"0040","0002","U32");
  // $Faktor = round($rc["Wert"]/1000);
  $rc = $funktionen->kostal_register_lesen($COM1,"003C","0002","U32");
  $aktuelleDaten["Current_L1"] = $rc["Wert"]/1000;

  // $rc = $funktionen->kostal_register_lesen($COM1,"0068","0002","U32");
  // $Faktor = round($rc["Wert"]/1000);
  $rc = $funktionen->kostal_register_lesen($COM1,"0064","0002","U32");
  $aktuelleDaten["Current_L2"] = $rc["Wert"]/1000;

  // $rc = $funktionen->kostal_register_lesen($COM1,"0090","0002","U32");
  // $Faktor = round($rc["Wert"]/1000);
  $rc = $funktionen->kostal_register_lesen($COM1,"008C","0002","U32");
  $aktuelleDaten["Current_L3"] = $rc["Wert"]/1000;

  $rc = $funktionen->kostal_register_lesen($COM1,"001A","0002","U32");
  $aktuelleDaten["Frequency"] = ($rc["Wert"]/1000);

  $rc = $funktionen->kostal_register_lesen($COM1,"003E","0002","U32");
  $aktuelleDaten["Voltage_L1"] = ($rc["Wert"]/1000);
  $rc = $funktionen->kostal_register_lesen($COM1,"0066","0002","U32");
  $aktuelleDaten["Voltage_L2"] = ($rc["Wert"]/1000);
  $rc = $funktionen->kostal_register_lesen($COM1,"008E","0002","U32");
  $aktuelleDaten["Voltage_L3"] = ($rc["Wert"]/1000);


  // print_r($aktuelleDaten);
  // echo "\n";

  /****************************************************************************
  //  ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN
  ****************************************************************************/



  /**************************************************************************
  //  Falls ein ErrorCode vorliegt, wird er hier in einen lesbaren
  //  Text umgewandelt, sodass er als Fehlermeldung gesendet werden kann.
  //  Die Funktion ist noch nicht überall implementiert.
  **************************************************************************/
  $FehlermeldungText = "";


  /****************************************************************************
  //  Die Daten werden für die Speicherung vorbereitet.
  ****************************************************************************/
  $aktuelleDaten["Regler"] = $Regler;
  $aktuelleDaten["Objekt"] = $Objekt;
  $aktuelleDaten["WattstundenGesamtHeute"] = 0;  // Dummy



  /**************************************************************************
  //  Alle ausgelesenen Daten werden hier bei Bedarf als mqtt Messages
  //  an den mqtt-Broker Mosquitto gesendet.
  //  Achtung! Die Übertragung dauert ca. 30 Sekunden!
  **************************************************************************/
  if ($MQTT and $i == 1) {
    $funktionen->log_schreiben("MQTT Daten zum [ $MQTTBroker ] senden.","   ",1);
    require($Pfad."/mqtt_senden.php");
  }

  /****************************************************************************
  //  Zeit und Datum
  ****************************************************************************/
  //  Der Regler hat keine interne Uhr! Deshalb werden die Daten vom Raspberry benutzt.
  $aktuelleDaten["Timestamp"] = time();
  $aktuelleDaten["Monat"]     = date("n");
  $aktuelleDaten["Woche"]     = date("W");
  $aktuelleDaten["Wochentag"] = strftime("%A",time());
  $aktuelleDaten["Datum"]     = date("d.m.Y");
  $aktuelleDaten["Uhrzeit"]   = date("H:i:s");
  $aktuelleDaten["zentralerTimestamp"] = ($aktuelleDaten["zentralerTimestamp"]+10);


  /****************************************************************************
  //  InfluxDB  Zugangsdaten ...stehen in der user.config.php
  //  falls nicht, sind das hier die default Werte.
  ****************************************************************************/
  $aktuelleDaten["InfluxAdresse"] = $InfluxAdresse;
  $aktuelleDaten["InfluxPort"] = $InfluxPort;
  $aktuelleDaten["InfluxUser"] =  $InfluxUser;
  $aktuelleDaten["InfluxPassword"] = $InfluxPassword;
  $aktuelleDaten["InfluxDBName"] = $InfluxDBName;
  $aktuelleDaten["InfluxDaylight"] = $InfluxDaylight;
  $aktuelleDaten["InfluxDBLokal"] = $InfluxDBLokal;
  $aktuelleDaten["Demodaten"] = false;





  $funktionen->log_schreiben(print_r($aktuelleDaten,1),"*- ",8);


  /*********************************************************************
  //  Daten werden in die Influx Datenbank gespeichert.
  //  Lokal und Remote bei Bedarf.
  *********************************************************************/
  if ($InfluxDB_remote) {
    // Test ob die Remote Verbindung zur Verfügung steht.
    if ($RemoteDaten) {
      $rc = $funktionen->influx_remote_test();
      if ($rc) {
        $rc = $funktionen->influx_remote($aktuelleDaten);
        if ($rc) {
          $RemoteDaten = false;
        }
      }
      else {
        $RemoteDaten = false;
      }
    }
    if ($InfluxDB_local) {
      $rc = $funktionen->influx_local($aktuelleDaten);
    }
  }
  else {
    $rc = $funktionen->influx_local($aktuelleDaten);
  }


  if (is_file($Pfad."/1.user.config.php")) {
    // Ausgang Multi-Regler-Version
    $Zeitspanne = (7 - (time() - $Start));
    $funktionen->log_schreiben("Multi-Regler-Ausgang. ".$Zeitspanne,"   ",2);
    if ($Zeitspanne > 0) {
      sleep($Zeitspanne);
    }
    break;
  }
  else {
    $funktionen->log_schreiben("Schleife: ".($i)." Zeitspanne: ".(floor((54 - (time() - $Start))/($Wiederholungen-$i+1))),"   ",9);
    sleep(floor((54 - (time() - $Start))/($Wiederholungen-$i+1)));
  }
  if ($Wiederholungen <= $i or $i >= 6) {
    $funktionen->log_schreiben("OK. Daten gelesen.","   ",9);
    $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
    break;
  }

  $i++;
} while (($Start + 54) > time());


if (isset($aktuelleDaten["Firmware"]) and isset($aktuelleDaten["Regler"])) {


  /*********************************************************************
  //  Jede Minute werden bei Bedarf einige Werte zur Homematic Zentrale
  //  übertragen.
  *********************************************************************/
  if (isset($Homematic) and $Homematic == true) {
    $aktuelleDaten["Solarspannung"] = $aktuelleDaten["Solarspannung1"];
    $funktionen->log_schreiben("Daten werden zur HomeMatic übertragen...","   ",8);
    require($Pfad."/homematic.php");
  }

  /*********************************************************************
  //  Sollen Nachrichten an einen Messenger gesendet werden?
  //
  *********************************************************************/
  if (isset($Messenger) and $Messenger == true) {
    $funktionen->log_schreiben("Nachrichten versenden...","   ",8);
    require($Pfad."/meldungen_senden.php");
  }

}
else {
  $funktionen->log_schreiben("Keine gültigen Daten empfangen.","!! ",6);
}

Ausgang:

$funktionen->log_schreiben("-------------   Stop   kostal_meter.php     ------------------- ","|--",6);

return;



?>

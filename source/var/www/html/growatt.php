#!/usr/bin/php
<?php
/*****************************************************************************
//  Solaranzeige Projekt             Copyright (C) [2015-2020]  [Ulrich Kunz]
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
//  Es dient dem Auslesen des Wechselrichters Growatt über eine RS485
//  Schnittstelle mit USB Adapter. Protokoll Version 1  V3.5
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
$Start = time();  // Timestamp festhalten
$funktionen->log_schreiben("----------------------   Start  growatt.php   --------------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",8);
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;

setlocale(LC_TIME,"de_DE.utf8");


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
$funktionen->log_schreiben("Hardware Version: ".$Version,"o  ",8);

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

if (empty($WR_Adresse)) {
  $WR_ID = "01";
}
elseif(strlen($WR_Adresse) == 1)  {
  $WR_ID = str_pad(dechex($WR_Adresse),2,"0",STR_PAD_LEFT);
}
else {
  $WR_ID = str_pad(dechex(substr($WR_Adresse,-2)),2,"0",STR_PAD_LEFT);
}

$funktionen->log_schreiben("WR_ID: ".$WR_ID,"+  ",8);


$USB1 = $funktionen->openUSB($USBRegler);
if (!is_resource($USB1)) {
  $funktionen->log_schreiben("USB Port kann nicht geöffnet werden. [1]","XX ",7);
  $funktionen->log_schreiben("Exit.... ","XX ",7);
  goto Ausgang;
}

$i = 1;
do {
  $funktionen->log_schreiben("Die Daten werden ausgelesen...","+  ",9);

  /****************************************************************************
  //  Ab hier wird der Regler ausgelesen.
  //
  //  Ergebniswerte:
  //
  ****************************************************************************/

  // Holding Register
  $Befehl["DeviceID"] = $WR_ID;
  $Befehl["BefehlFunctionCode"] = "03";
  $Befehl["RegisterAddress"] = "0009";   // Dezimal 9
  $Befehl["RegisterCount"] = "0003";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Firmware"] = trim($funktionen->Hex2String($rc["data"]),"\0");


  if ($rc["ok"] == false) {
    $funktionen->log_schreiben("Keine Antwort vom Wechselrichter. Zu dunkel?","   ",7);
    goto Ausgang;
  }
  if ( strtoupper(substr($aktuelleDaten["Firmware"],0,2)) == "AL") {
    $aktuelleDaten["Protokollversion"] = 2;
  }
  elseif(strtoupper(substr($aktuelleDaten["Firmware"],0,2)) == "G.") {
    $aktuelleDaten["Protokollversion"] = 1;  
  }
  else {
    $funktionen->log_schreiben("Dieses Growatt Modell ist noch nicht bekannt. Bitte melden: support@solaranzeige.de","   ",2);
    goto Ausgang;
  }

  $Befehl["RegisterAddress"] = "002C";   // Dezimal 44
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Anz.MPPT"] = substr(($rc["data"]),0,2);
  $aktuelleDaten["Anz.Phasen"] = substr(($rc["data"]),2,2);

  // Read Register
  $Befehl["BefehlFunctionCode"] = "04";
  $Befehl["RegisterAddress"] = "0000";   // Dezimal 0
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Status"] = $rc["data"]+0;
  
  if ( $aktuelleDaten["Status"] == 3 ) {
    $funktionen->log_schreiben("Fehlermeldung. Zu dunkel?","   ",5);
    goto Ausgang;
  }
  elseif ( $aktuelleDaten["Status"] == 0 ) {
    $funktionen->log_schreiben("Es ist zu dunkel. Keine Daten mehr vorhanden.","   ",8);
    goto Ausgang;
  }
  elseif ($rc["ok"] == false) {
    $funktionen->log_schreiben("Keine Antwort vom Wechselrichter. Zu dunkel?","   ",7);
    goto Ausgang;
  }

  $Befehl["RegisterAddress"] = "0001";   // Dezimal 1
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Leistung"] = hexdec($rc["data"])/10;


  $Befehl["RegisterAddress"] = "0003";   // Dezimal 3
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Spannung1"] = hexdec($rc["data"])/10;

  $Befehl["RegisterAddress"] = "0004";   // Dezimal 4
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Strom1"] = hexdec($rc["data"])/10;

  $Befehl["RegisterAddress"] = "0005";   // Dezimal 5
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Leistung1"] = hexdec($rc["data"])/10;
  // -----------------
 
  $Befehl["RegisterAddress"] = "0007";   // Dezimal 7
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Spannung2"] = hexdec($rc["data"])/10;

  $Befehl["RegisterAddress"] = "0008";   // Dezimal 8
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Strom2"] = hexdec($rc["data"])/10;

  $Befehl["RegisterAddress"] = "0009";   // Dezimal 9
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV_Leistung2"] = hexdec($rc["data"])/10;


  if ($aktuelleDaten["Protokollversion"] == 1) {


    $Befehl["RegisterAddress"] = "000B";   // Dezimal 11
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung"] = $funktionen->hexdecs($rc["data"])/10;
    $funktionen->log_schreiben("AC_Leistung: ".print_r($rc,1),"   ",8);

    if ($aktuelleDaten["AC_Leistung"] < 0) { 
      $aktuelleDaten["AC_Leistung"] = 0;
    }

    $Befehl["RegisterAddress"] = "000D";   // Dezimal 13
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Frequenz"] = hexdec($rc["data"])/100;

    $Befehl["RegisterAddress"] = "000E";   // Dezimal 14
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_R"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "000F";   // Dezimal 15
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Strom_R"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0010";   // Dezimal 16
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung_R"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0012";   // Dezimal 18
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_S"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0013";   // Dezimal 19
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Strom_S"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0014";   // Dezimal 20
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung_S"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0016";   // Dezimal 22
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_T"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0017";   // Dezimal 23
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Strom_T"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0018";   // Dezimal 24
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung_T"] = hexdec($rc["data"])/10;



    $Befehl["RegisterAddress"] = "001A";   // Dezimal 26
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["WattstundenGesamtHeute"] = hexdec($rc["data"])*100;
 
    $Befehl["RegisterAddress"] = "001C";   // Dezimal 28
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["WattstundenGesamt"] = hexdec($rc["data"])*100;
  
    $Befehl["RegisterAddress"] = "0020";   // Dezimal 32
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["Temperatur"] = hexdec($rc["data"])/10;



    $Befehl["RegisterAddress"] = "0028";   // Dezimal 40
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["FehlerCode"] = $rc["data"];

    $Befehl["RegisterAddress"] = "0040";   // Dezimal 64
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["Warnungen"] = $rc["data"];
 
  }

  if ($aktuelleDaten["Protokollversion"] == 2) {

    $Befehl["RegisterAddress"] = "0023";   // Dezimal 35
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung"] = $funktionen->hexdecs($rc["data"])/10;

    $funktionen->log_schreiben("AC_Leistung: ".print_r($rc,1),"   ",8);

    $funktionen->log_schreiben("AC_Leistung: ".$aktuelleDaten["AC_Leistung"],"   ",8);

    if ($aktuelleDaten["AC_Leistung"] < 0) { 
      $aktuelleDaten["AC_Leistung"] = 0;
    }

    $Befehl["RegisterAddress"] = "0025";   // Dezimal 37
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Frequenz"] = hexdec($rc["data"])/100;

    $Befehl["RegisterAddress"] = "0026";   // Dezimal 38
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_R"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0027";   // Dezimal 39
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Strom_R"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0028";   // Dezimal 40
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung_R"] = hexdec($rc["data"])/10;
  
    $Befehl["RegisterAddress"] = "002A";   // Dezimal 42
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_S"] = hexdec($rc["data"])/10;
 
    $Befehl["RegisterAddress"] = "002B";   // Dezimal 43
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Strom_S"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "002C";   // Dezimal 44
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung_S"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "002E";   // Dezimal 46
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_T"] = hexdec($rc["data"])/10;
  
    $Befehl["RegisterAddress"] = "002F";   // Dezimal 47
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Strom_T"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0030";   // Dezimal 48
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Leistung_T"] = hexdec($rc["data"])/10;



    $Befehl["RegisterAddress"] = "0035";   // Dezimal 53
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["WattstundenGesamtHeute"] = hexdec($rc["data"])*100;

    $Befehl["RegisterAddress"] = "0037";   // Dezimal 55
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["WattstundenGesamt"] = hexdec($rc["data"])*100;
  
    $Befehl["RegisterAddress"] = "005D";   // Dezimal 93
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["Temperatur"] = hexdec($rc["data"])/10;

    $Befehl["RegisterAddress"] = "0068";   // Dezimal 104
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["DeratingMode"] = hexdec($rc["data"]);

    $Befehl["RegisterAddress"] = "0069";   // Dezimal 105
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["FehlerCode"] = $rc["data"];

    $Befehl["RegisterAddress"] = "006E";   // Dezimal 110
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["Warnungen"] = $rc["data"];

  }

  $funktionen->log_schreiben("Firmware: ".$aktuelleDaten["Firmware"]."  Warnungen: ".$aktuelleDaten["Warnungen"],"   ",6);



  $funktionen->log_schreiben("Auslesen des Gerätes beendet.","   ",7);



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
  $aktuelleDaten["Modell"] = "Growatt 1500-S";


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
  $aktuelleDaten["Uhrzeit"]      = date("H:i:s");
  $aktuelleDaten["zentralerTimestamp"] = ($aktuelleDaten["zentralerTimestamp"]+10);


  $funktionen->log_schreiben(print_r($aktuelleDaten,1),"   ",8);




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
  $aktuelleDaten["InfluxSSL"] = $InfluxSSL;
  $aktuelleDaten["Demodaten"] = false;


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




  if ($Wiederholungen <= $i or $i >= 6) {
      $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
      break;
  }
  if (is_file($Pfad."/1.user.config.php")) {
    // Ausgang Multi-Regler-Version
    $Zeitspanne = (9 - (time() - $Start));
    $funktionen->log_schreiben("Multi-Regler-Ausgang. ".$Zeitspanne,"   ",2);
    if ($Zeitspanne > 0) {
      sleep($Zeitspanne);
    }
    break;
  }
  else {
    $funktionen->log_schreiben("Schleife: ".($i)." Zeitspanne: ".(floor((56 - (time() - $Start))/($Wiederholungen-$i+1))),"   ",9);
    sleep(floor((56 - (time() - $Start))/($Wiederholungen-$i+1)));
  }
  $i++;
} while (($Start + 54) > time());


if (isset($aktuelleDaten["Temperatur"]) and isset($aktuelleDaten["Regler"])) {


  /*********************************************************************
  //  Jede Minute werden bei Bedarf einige Werte zur Homematic Zentrale
  //  übertragen.
  *********************************************************************/
  if (isset($Homematic) and $Homematic == true) {
    $funktionen->log_schreiben("Daten werden zur HomeMatic übertragen...","   ",8);
    require($Pfad."/homematic.php");
  }

  /*********************************************************************
  //  Sollen Nachrichten an einen Messenger gesendet werden?
  //  Bei einer Multi-Regler-Version sollte diese Funktion nur bei einem
  //  Gerät aktiviert sein.
  *********************************************************************/
  if (isset($Messenger) and $Messenger == true) {
    $funktionen->log_schreiben("Nachrichten versenden...","   ",8);
    require($Pfad."/meldungen_senden.php");
  }

  $funktionen->log_schreiben("OK. Datenübertragung erfolgreich.","   ",7);
}
else {
  $funktionen->log_schreiben("Keine gültigen Daten empfangen.","!! ",6);
}




Ausgang:

$funktionen->log_schreiben("----------------------   Stop   growatt.php   --------------------- ","|--",6);

return;



?>
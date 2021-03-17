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
//  Es dient dem Auslesen des SENEC Batterie-Speicher-Management über LAN
//  mit Port 80
//  
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

$Tracelevel = 8;  //  1 bis 10  10 = Debug
$RemoteDaten = true;
$Start = time();  // Timestamp festhalten
$funktionen->log_schreiben("-----------------   Start  senec.php   --------------------- ","|--",6);

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

/*****************************************************************************
//  Die Status Datei wird dazu benutzt, um die Leistung des Reglers
//  pro Tag zu speichern.
//
*****************************************************************************/
$StatusFile = $Pfad."/database/".$GeraeteNummer.".WhProTag.txt";
if (!file_exists($StatusFile)) {
  /***************************************************************************
  //  Inhalt der Status Datei anlegen, wenn nicht existiert.
  ***************************************************************************/
  $rc = file_put_contents($StatusFile,"0");
  if ($rc === false) {
    $funktionen->log_schreiben("Konnte die Datei whProTag_delta.txt nicht anlegen.",5);
  }
  $aktuelleDaten["WattstundenGesamtHeute"] = 0;
}
else {
  $aktuelleDaten["WattstundenGesamtHeute"] = file_get_contents($StatusFile);
  $funktionen->log_schreiben("WattstundenGesamtHeute: ".$aktuelleDaten["WattstundenGesamtHeute"],"   ",8);
}






$http_daten = array("URL" => "http://".$WR_IP."/lala.cgi",
                    "Port" => $WR_Port,
                    "Header" => array('Content-Type: application/json'));



$i = 1;
do {
  $funktionen->log_schreiben("Die Daten werden ausgelesen...","+  ",9);

  /****************************************************************************
  //  Ab hier wird der BMS  ausgelesen.
  //
  //  Ergebniswerte:
  //
  ****************************************************************************/
  $http_daten["Data"] = '{"PV1":{"POWER_RATIO":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["PV_Begrenzung"] = $funktionen->senec($rc["PV1"]["POWER_RATIO"]);

  $http_daten["Data"] = '{"PM1OBJ1":{"P_TOTAL":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["AC_Leistung"] = round($funktionen->senec($rc["PM1OBJ1"]["P_TOTAL"]),2);

  $http_daten["Data"] = '{"PM1OBJ1":{"FREQ":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Frequenz"] = round($funktionen->senec($rc["PM1OBJ1"]["FREQ"]),2);

  $http_daten["Data"] = '{"PM1OBJ1":{"U_AC":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["AC_Spannung_R"] = round($funktionen->senec($rc["PM1OBJ1"]["U_AC"][0]),2);
  $aktuelleDaten["AC_Spannung_S"] = round($funktionen->senec($rc["PM1OBJ1"]["U_AC"][1]),2);
  $aktuelleDaten["AC_Spannung_T"] = round($funktionen->senec($rc["PM1OBJ1"]["U_AC"][2]),2);

  $http_daten["Data"] = '{"PM1OBJ1":{"I_AC":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["AC_Strom_R"] = round($funktionen->senec($rc["PM1OBJ1"]["I_AC"][0]),2);
  $aktuelleDaten["AC_Strom_S"] = round($funktionen->senec($rc["PM1OBJ1"]["I_AC"][1]),2);
  $aktuelleDaten["AC_Strom_T"] = round($funktionen->senec($rc["PM1OBJ1"]["I_AC"][2]),2);

  $http_daten["Data"] = '{"PM1OBJ1":{"P_AC":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["AC_Leistung_R"] = round($funktionen->senec($rc["PM1OBJ1"]["P_AC"][0]),2);
  $aktuelleDaten["AC_Leistung_S"] = round($funktionen->senec($rc["PM1OBJ1"]["P_AC"][1]),2);
  $aktuelleDaten["AC_Leistung_T"] = round($funktionen->senec($rc["PM1OBJ1"]["P_AC"][2]),2);



  $http_daten["Data"] = '{"ENERGY":{"GUI_BAT_DATA_FUEL_CHARGE":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Bat_SOC"] = round($funktionen->senec($rc["ENERGY"]["GUI_BAT_DATA_FUEL_CHARGE"]),0);

  $http_daten["Data"] = '{"ENERGY":{"GUI_BAT_DATA_POWER":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Bat_Leistung"] = round($funktionen->senec($rc["ENERGY"]["GUI_BAT_DATA_POWER"]),2);

  $http_daten["Data"] = '{"ENERGY":{"GUI_BAT_DATA_VOLTAGE":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Bat_Spannung"] = round($funktionen->senec($rc["ENERGY"]["GUI_BAT_DATA_VOLTAGE"]),2);

  $http_daten["Data"] = '{"ENERGY":{"GUI_HOUSE_POW":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Hausverbrauch"] = round($funktionen->senec($rc["ENERGY"]["GUI_HOUSE_POW"]),2);

  $http_daten["Data"] = '{"ENERGY":{"GUI_INVERTER_POWER":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["PV_Leistung"] = round($funktionen->senec($rc["ENERGY"]["GUI_INVERTER_POWER"]),2);

  $http_daten["Data"] = '{"ENERGY":{"GUI_GRID_POW":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Netz_Leistung"] = round($funktionen->senec($rc["ENERGY"]["GUI_GRID_POW"]),2);

  $http_daten["Data"] = '{"ENERGY":{"STAT_STATE":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Status"] = round($funktionen->senec($rc["ENERGY"]["STAT_STATE"]),2);

  $http_daten["Data"] = '{"ENERGY":{"STAT_HOURS_OF_OPERATION":""}}';
  $rc = $funktionen->http_read($http_daten);  
  $aktuelleDaten["Betriebsstunden"] = round($funktionen->senec($rc["ENERGY"]["STAT_HOURS_OF_OPERATION"]),2);




  $http_daten["Data"] = '{"BMS":{"NR_INSTALLED":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Anz_Batterien"] = $funktionen->senec($rc["BMS"]["NR_INSTALLED"]);

  $http_daten["Data"] = '{"BMS":{"TOTAL_CURRENT":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Gesamtstrom"] = $funktionen->senec($rc["BMS"]["TOTAL_CURRENT"]);




  $http_daten["Data"] = '{"STATISTIC":{"LIVE_GRID_IMPORT":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["NetzbezugGesamt"] = round($funktionen->senec($rc["STATISTIC"]["LIVE_GRID_IMPORT"])*1000,1);

  $http_daten["Data"] = '{"STATISTIC":{"LIVE_GRID_EXPORT":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["EinspeisungGesamt"] = round($funktionen->senec($rc["STATISTIC"]["LIVE_GRID_EXPORT"])*1000,1);

  $http_daten["Data"] = '{"STATISTIC":{"LIVE_PV_GEN":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["PV_Gesamtleistung"] = round($funktionen->senec($rc["STATISTIC"]["LIVE_PV_GEN"])*1000,1);


  $http_daten["Data"] = '{"BAT1":{"NSP2_FW":""}}';
  $rc = $funktionen->http_read($http_daten);
  $aktuelleDaten["Firmware"] = $funktionen->senec($rc["BAT1"]["NSP2_FW"]);



  if ($aktuelleDaten["Netz_Leistung"] < 0 ) {
      $aktuelleDaten["Einspeisung"] = abs($aktuelleDaten["Netz_Leistung"]);
      $aktuelleDaten["Bezug"] = 0;
  }
  else {
    $aktuelleDaten["Einspeisung"] = 0;
    $aktuelleDaten["Bezug"] = $aktuelleDaten["Netz_Leistung"];
  }



  /****************************************************************************
  //  ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN
  ****************************************************************************/


  /**************************************************************************
  //  Falls ein ErrorCode / Statusmeldung vorliegt, wird er hier in einen
  //  lesbaren Text umgewandelt, sodass er als Fehlermeldung gesendet werden
  //  kann. Die Funktion ist noch nicht überall implementiert.
  **************************************************************************/
  $FehlermeldungText = "";

  switch ($aktuelleDaten["Status"]) {

    case 0:
      $FehlermeldungText =  "INITIALZUSTAND";
      break;
    case 1:
      $FehlermeldungText = "KEINE KOMMUNIKATION LADEGERAET";
      break;
    case 2:
      $FehlermeldungText = "FEHLER LEISTUNGSMESSGERAET";
      break;
    case 3:
      $FehlermeldungText = "RUNDSTEUEREMPFAENGER";
      break;
    case 4:
      $FehlermeldungText = "ERSTLADUNG";
      break;
    case 5:
      $FehlermeldungText = "WARTUNGSLADUNG";
      break;
    case 6:
      $FehlermeldungText = "WARTUNGSLADUNG FERTIG";
      break;
    case 7:
      $FehlermeldungText = "WARTUNG NOTWENDIG";
      break;
    case 8:
      $FehlermeldungText = "MAN. SICHERHEITSLADUNG";
      break;
    case 9:
      $FehlermeldungText = "SICHERHEITSLADUNG FERTIG";
      break;
    case 10:
      $FehlermeldungText = "VOLLLADUNG";
      break;
    case 11:
      $FehlermeldungText = "AUSGLEICHSLADUNG: LADEN";
      break;
    case 12:
      $FehlermeldungText = "SULFATLADUNG: LADEN";
      break;
    case 13:
      $FehlermeldungText = "AKKU VOLL";
      break;
    case 14:
      $FehlermeldungText = "LADEN";
      break;
    case 15:
      $FehlermeldungText = "AKKU LEER";
      break;
    case 16:
      $FehlermeldungText = "ENTLADEN";
      break;
    case 17:
      $FehlermeldungText = "PV + ENTLADEN";
      break;
    case 18:
      $FehlermeldungText = "NETZ + ENTLADEN";
      break;
    case 19:
      $FehlermeldungText = "PASSIV";
      break;
    case 20:
      $FehlermeldungText = "AUSGESCHALTET";
      break;
    case 21:
      $FehlermeldungText = "EIGENVERBRAUCH";
      break;
    case 22:
      $FehlermeldungText = "NEUSTART";
      break;
    case 23:
      $FehlermeldungText = "MAN. AUSGLEICHSLADUNG: LADEN";
      break;
    case 24:
      $FehlermeldungText = "MAN. SULFATLADUNG: LADEN";
      break;
    case 25:
      $FehlermeldungText = "SICHERHEITSLADUNG";
      break;
    case 26:
    $FehlermeldungText = "AKKU-SCHUTZBETRIEB";
      break;
    case 27:
      $FehlermeldungText = "EG FEHLER";
      break;
    case 28:
      $FehlermeldungText = "EG LADEN";
      break;
    case 29:
      $FehlermeldungText = "EG ENTLADEN";
      break;
    case 30:
      $FehlermeldungText = "EG PASSIV";
      break;
    case 31:
      $FehlermeldungText = "EG LADEN VERBOTEN";
      break;
    case 32:
      $FehlermeldungText = "EG ENTLADEN VERBOTEN";
      break;
    case 33:
      $FehlermeldungText = "NOTLADUNG";
      break;
    case 34:
      $FehlermeldungText = "SOFTWAREAKTUALISIERUNG";
      break;
    case 35:
      $FehlermeldungText = "FEHLER: NA-SCHUTZ";
      break;
    case 36:
      $FehlermeldungText = "FEHLER: NA-SCHUTZ NETZ";
      break;
    case 37:
      $FehlermeldungText = "FEHLER: NA-SCHUTZ HARDWARE";
      break;
    case 38:
      $FehlermeldungText = "KEINE SERVERVERBINDUNG";
      break;
    case 39:
      $FehlermeldungText = "BMS FEHLER";
      break;
    case 40:
      $FehlermeldungText = "WARTUNG: FILTER";
      break;
    case 41:
      $FehlermeldungText = "SCHLAFMODUS";
      break;
    case 42:
      $FehlermeldungText = "WARTE AUF ÜBERSCHUSS";
      break;
    case 43:
      $FehlermeldungText = "KAPAZITÄTSTEST: LADEN";
      break;
    case 44:
      $FehlermeldungText = "KAPAZITÄTSTEST: ENTLADEN";
      break;
    case 45:
      $FehlermeldungText = "MAN. SULFATLADUNG: WARTEN";
      break;
    case 46:
      $FehlermeldungText = "MAN. SULFATLADUNG: FERTIG";
      break;
    case 47:
      $FehlermeldungText = "MAN. SULFATLADUNG: FEHLER";
      break;
    case 48:
      $FehlermeldungText = "AUSGLEICHSLADUNG: WARTEN";
      break;
    case 49:
      $FehlermeldungText = "NOTLADUNG: FEHLER";
      break;
    case 50:
      $FehlermeldungText = "MAN: AUSGLEICHSLADUNG: WARTEN";
      break;
    case 51:
      $FehlermeldungText = "MAN: AUSGLEICHSLADUNG: FEHLER";
      break;
    case 52:
      $FehlermeldungText = "MAN: AUSGLEICHSLADUNG: FERTIG";
      break;
    case 53:
      $FehlermeldungText = "AUTO: SULFATLADUNG: WARTEN";
      break;
    case 54:
      $FehlermeldungText = "LADESCHLUSSPHASE";
      break;
    case 55:
      $FehlermeldungText = "BATTERIETRENNSCHALTER AUS";
      break;
    case 56:
      $FehlermeldungText = "PEAK-SHAVING: WARTEN";
      break;
    case 57:
      $FehlermeldungText = "FEHLER LADEGERAET";
      break;
    case 58:
      $FehlermeldungText = "NPU-FEHLER";
      break;
    case 59:
      $FehlermeldungText = "BMS OFFLINE";
      break;
    case 60:
      $FehlermeldungText = "WARTUNGSLADUNG FEHLER";
      break;
    case 61:
      $FehlermeldungText = "MAN. SICHERHEITSLADUNG FEHLER";
      break;
    case 62:
      $FehlermeldungText = "SICHERHEITSLADUNG FEHLER";
      break;
    case 63:
      $FehlermeldungText = "KEINE MASTERVERBINDUNG";
      break;
    case 64:
      $FehlermeldungText = "LITHIUM SICHERHEITSMODUS AKTIV";
      break;
    case 65:
      $FehlermeldungText = "LITHIUM SICHERHEITSMODUS BEENDET";
      break;
    case 66:
      $FehlermeldungText = "FEHLER BATTERIESPANNUNG";
      break;
    case 67:
      $FehlermeldungText = "BMS DC AUSGESCHALTET";
      break;
    case 68:
      $FehlermeldungText = "NETZINITIALISIERUNG";
      break;
    case 69:
      $FehlermeldungText = "NETZSTABILISIERUNG";
      break;
    case 70:
      $FehlermeldungText = "FERNABSCHALTUNG";
      break;
    case 71:
      $FehlermeldungText = "OFFPEAK-LADEN";
      break;
    case 72:
      $FehlermeldungText = "FEHLER HALBBRÜCKE";
      break;
    case 73:
      $FehlermeldungText = "BMS: FEHLER BETRIEBSTEMPERATUR";
      break;
    case 74:
      $FehlermeldungText = "FACOTRY SETTINGS NICHT GEFUNDEN";
      break;
    case 75:
      $FehlermeldungText = "NETZERSATZBETRIEB";
      break;
    case 76:
      $FehlermeldungText = "NETZERSATZBETRIEB AKKU LEER";
      break;
    case 77:
      $FehlermeldungText = "NETZERSATZBETRIEB FEHLER";
      break;
    case 78:
      $FehlermeldungText = "INITIALISIERUNG";
      break;
    case 79:
      $FehlermeldungText = "INSTALLATIONSMODUS";
      break;
    case 80:
      $FehlermeldungText = "NETZAUSFALL";
      break;
    case 81:
      $FehlermeldungText = "BMS UPDATE ERFORDERLICH";
      break;
    case 82:
      $FehlermeldungText = "BMS KONFIGURATION ERFORDERLICH";
      break;
    default:
      $FehlermeldungText = "UNBEKANNT";
      break;

  }
  $aktuelleDaten["Statusmeldung"] = $FehlermeldungText;


  /****************************************************************************
  //  Die Daten werden für die Speicherung vorbereitet.
  ****************************************************************************/
  $aktuelleDaten["Regler"] = $Regler;
  $aktuelleDaten["Objekt"] = $Objekt;


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


if (isset($aktuelleDaten["Firmware"]) and isset($aktuelleDaten["Regler"])) {


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


/*****************************************************************************
//  Die Status Datei wird dazu benutzt, um die Leistung des Reglers
//  pro Tag zu speichern.
//  Der Aufwand wird betrieben, da der Wechselrichter mit sehr wenig Licht
//  tagsüber sich ausschaltet und der Zähler sich zurück setzt.
//  Achtung! Dieser Wert wird jeden Tag um Mitternacht auf 0 gesetzt.
//  Leistung in Watt / 60 Minuten, da 60 mal in der Stunde addiert wird.
*****************************************************************************/
if (file_exists($StatusFile)) {

  /***************************************************************************
  //  Die Status Datei wird dazu benutzt, um die Leistung des Reglers
  //  pro Tag zu speichern.
  //  Jede Nacht 0 Uhr Tageszähler auf 0 setzen
  ***************************************************************************/
  if (date("H:i") == "00:00" or date("H:i") == "00:01") {
    $rc = file_put_contents($StatusFile,"0");  
    $funktionen->log_schreiben("WattstundenGesamtHeute  gesetzt.","o- ",5);
  }

  /***************************************************************************
  //  Daten einlesen ...   ( Watt * Stunden ) pro Tag = Wh
  ***************************************************************************/
  $whProTag = file_get_contents($StatusFile);
  $whProTag = ($whProTag + ($aktuelleDaten["PV_Leistung"])/60);
  $rc = file_put_contents($StatusFile,round($whProTag,2));
  $funktionen->log_schreiben("WattstundenGesamtHeute: ".round($whProTag,2),"   ",5);
}



Ausgang:

$funktionen->log_schreiben("-----------------   Stop   senec.php   -------------------- ","|--",6);

return;



?>
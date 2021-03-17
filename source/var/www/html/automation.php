#!/usr/bin/php
<?php
/*****************************************************************************
//  Solaranzeige Projekt             Copyright (C) [2016-2019]  [Ulrich Kunz]
//
//  Dieses Programm ist freie Software. Sie können es unter den Bedingungen
//  der GNU General Public License, wie von der Free Software Foundation
//  veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
//  Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.
//
//  Die Veröffentlichung dieses Programms erfolgt in der Hoffnung, dass es
//  Ihnen von Nutzen sein wird, aber OHNE IRGENDEINE GARANTIE, sogar ohne
//  die implizite Garantie der MARKTREIFE oder der VERWENDBARKEIT FÜR EINEN
//  BESTIMMTEN ZWECK. Details finden Sie in der GNU General Public License.
//
//  Ein original Exemplar der GNU General Public License finden Sie hier:
//  http://www.gnu.org/licenses/
//
//  Dies ist ein Programmteil des Programms "Solaranzeige"
//
//  Es dient der Steuerung von Wärmepumpen und Heizungselementen
//  In der SQLite3 Datenbank "automation.sqlite3" sind die nötigen Parameter
//
//  Dieser Script kann folgendermaßen aufgerufen werden:  php automation.php.
//  Er läuft unabhängig von der Solaranzeigen Software und wird mit einem cron Job
//  jede Minute gestartet. 
//
//
*****************************************************************************+
//  Tracelevel:
//  0 = keine LOG Meldungen
//  1 = Nur Fehlermeldungen
//  2 = Fehlermeldungen und Warnungen
//  3 = Fehlermeldungen, Warnungen und Informationen
//  4 = Debugging
*****************************************************************************/
$Tracelevel = 3;

//
//

$path_parts = pathinfo($argv[0]);
$Pfad = $path_parts['dirname'];
$Datenbankname = "/var/www/html/database/automation.sqlite3";
$LRaktiv = false;
$WRaktiv = false;
$SMaktiv = false;
$BMSaktiv = false;
$WRVar = 0; 
$MeterVar = 0; 
$BMSVar = 0; 
$Brokermeldung ="";
$Relais1[1] = "";
$Relais1[2] = "";
$Relais1[3] = "";
$Relais1[4] = "";
$Relais2[1] = "";
$Relais2[2] = "";
$Relais2[3] = "";
$Relais2[4] = "";

$Relais1WertON = "on";
$Relais1WertOFF = "off";
$Relais2WertON = "on";
$Relais2WertOFF = "off";

$PVLeistung = 0;
$ACLeistung = 0;
$Bezug = 0;
$Einspeisung = 0;
$SOC = 0;
$MQTTDaten = array();


log_schreiben("- - - - - - - - - -    Start Automation   - - - - - - - - -","|-->",1);


/****************************************************************************
//  SQLite Datenbank starten
//
****************************************************************************/

try {

  $db = db_connect($Datenbankname);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}
catch(PDOException $e) {
  // Print PDOException message
  log_schreiben($e->getMessage(),"",1);
}


$sql = "SELECT * FROM waermepumpen";
$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
//  Alle Felder auslesen
$var = $result[0];
log_schreiben(print_r($var,1),"",4);

switch (strtoupper($var["Relais1Typ"])) {

  case "SONOFF POW":
  case "SONOFF BASIC":
  case "SONOFF TH16/TH10":
  case "GOSUND SP1/SP111":
    $Relais1[1] = "cmnd/".$var["Relais1Topic"]."/power";
    $var["Relais1AnzKontakte"] = 1;
  break;

  case "SONOFF 4CH":
    $Relais1[1] = "cmnd/".$var["Relais1Topic"]."/power1";
    $Relais1[2] = "cmnd/".$var["Relais1Topic"]."/power2";
    $Relais1[3] = "cmnd/".$var["Relais1Topic"]."/power3";
    $Relais1[4] = "cmnd/".$var["Relais1Topic"]."/power4";
  break;

  case "GOSUND SP211":
  case "Shelly 2.5":
    $Relais1[1] = "cmnd/".$var["Relais1Topic"]."/power1";
    $Relais1[2] = "cmnd/".$var["Relais1Topic"]."/power2";
    if ($var["Relais1AnzKontakte"] > 2) {
      $var["Relais1AnzKontakte"] = 2;
    }
  break;

  default:
    $Relais1[1] = "cmnd/".$var["Relais1Topic"]."/power";
    $var["Relais1AnzKontakte"] = 1;
  break;

}



switch (strtoupper($var["Relais2Typ"])) {

  case "SONOFF POW":
  case "SONOFF BASIC":
  case "SONOFF TH16/10":
  case "GOSUND SP1/SP111":
    $Relais2[1] = "cmnd/".$var["Relais2Topic"]."/power";
  break;

  case "SONOFF 4CH":
    $Relais2[1] = "cmnd/".$var["Relais2Topic"]."/power1";
    $Relais2[2] = "cmnd/".$var["Relais2Topic"]."/power2";
    $Relais2[3] = "cmnd/".$var["Relais2Topic"]."/power3";
    $Relais2[4] = "cmnd/".$var["Relais2Topic"]."/power4";
  break;

  case "Shelly 2.5":
  case "GOSUND SP211":
    $Relais2[1] = "cmnd/".$var["Relais2Topic"]."/power1";
    $Relais2[2] = "cmnd/".$var["Relais2Topic"]."/power2";
    if ($var["Relais2AnzKontakte"] > 2) {
      $var["Relais2AnzKontakte"] = 2;
    }
  break;

  default:
    $Relais2[1] = "cmnd/".$var["Relais2Topic"]."/power";
  break;

}


if ($var["LRReglerNr"] > 0) {
  echo "Laderegler ist konfiguriert\n";
  //  Measurement PV
  $LRVar = influxDB_lesen($var["LRDB"],$var["LRMeasurement"]); 
  $PVLeistung = $LRVar[$var["LRFeldname"]];
  $LRaktiv = true;
  log_schreiben("Laderegler ist konfiguriert.","",4);
}

if ($var["WRReglerNr"] > 0) {
  echo "Wechselrichter ist konfiguriert\n";
  //  Measurement AC
  $WRVar = influxDB_lesen($var["WRDB"],$var["WRMeasurement"]); 
  $ACLeistung = $WRVar[$var["WRFeldname"]];
  log_schreiben("Wechselrichter ist konfiguriert.","",4);
  $WRaktiv = true;
}

if ($var["SMReglerNr"] > 0) {
  echo "SmartMeeter ist konfiguriert\n";
  //  Measurement AC
  $MeterVar = influxDB_lesen($var["SMDB"],$var["SMMeasurement"]); 
  $Bezug = $MeterVar[$var["SMBezug"]];
  $Einspeisung = $MeterVar["SMEinspeisung"];
  log_schreiben("SmartMeter ist konfiguriert.","",4);
  $SMaktiv = true;
}

if ($var["BMSReglerNr"] > 0) {
  echo "BMS ist konfiguriert\n";
  //  Measurement Batterie
  $BMSVar = influxDB_lesen($var["BMSDB"],$var["BMSMeasurement"]); 
  $SOC = $BMSVar[$var["BMSFeldname"]];
  log_schreiben("BMS ist konfiguriert.","",4);
  $BMSaktiv = true;
}

if (1 == 2) {
  // Hier können noch weitere Measurements von den verschiedenen Datenbanken 
  // abgefrgt werden, die dann in der Datei auto-math.php zur Berechnung
  // benutzt werden können.
  // Das ist nur ein Template und auch inaktiviert. Es muss erst mit den 
  // richtigen Namen ausgefüllt werden.
  //
  $ZusatzVar = influxDB_lesen($Datenbank,$Measurement); 
  $Sonderwerte = $ZusatzVar["Feldname"];
  log_schreiben("Sonderwerte:\n".print_r($ZusatzVar,1),"",4);
}




// Hier stehen die Variablen zur Verfügung.
// Array's = $LRVar, $WRVar, $MeterVar, $BMSVar, $ZusatzVar


/****************************************************************************
//  Ist ein Laderegler, Wechselrichter usw. konfigurier?
//
****************************************************************************/

if ($LRaktiv == false and $WRaktiv == false and $SMaktiv == false and $BMSaktiv == false) {
  log_schreiben("Es ist kein Überwachungsgerät konfiguriert. Ende!","",1);
  log_schreiben("Bitte die Konfiguration überprüfen.","",2);
  echo "Kein Gerät konfiguriert\n";
  goto Ausgang;
}



/*****************************************************************************
//  Ist ein Relais aktiviert?
//  Wenn nein, dann Ausgang.
*****************************************************************************/
if ($var["Relais1aktiv"] == 0 and $var["Relais2aktiv"] == 0) {
  log_schreiben("Es ist kein Relais konfiguriert. Ende!","",1);
  echo "Kein Relais konfiguriert\n";
  goto Ausgang;
}



/****************************************************************************
//  Moscitto Client starten
//
****************************************************************************/

$client = new Mosquitto\Client();
$client->onConnect('connect');
$client->onDisconnect('disconnect');
$client->onSubscribe('subscribe');
$client->onMessage('message');
$client->onLog('logger');
$client->onPublish('publish');
$client->connect($var["BrokerIP"], $var["BrokerPort"], 5);
for ($i=1;$i<20;$i++) {
  // Warten bis der connect erfolgt ist.
  if (empty($Brokermeldung)) {
    $client->loop(100);
  }
  else {
    break;
  }
}




/****************************************************************************
//  User PHP Script, falls gewünscht oder nötig
****************************************************************************/
include 'auto-math.php';  // Falls etwas neu berechnet werden muss.







/**********************************************************************
//  Relais Steuerkreis 1
//  Hier beginnt die Auswertung und Steuerung
//
//  *****************************************
**********************************************************************/
if ( $var["Relais1aktiv"] == 1) {
  log_schreiben("Relais 1 ist aktiviert.","",3);

  echo "Relais 1 ist aktiv\n";

  echo "PV Leistung: ".$PVLeistung." W\n";
  echo "AC Leistung: ".$ACLeistung." W\n";
  echo "Bezug: ".$Bezug." W\n";
  echo "Einspeisung: ".$Einspeisung." W\n";
  echo "Kapazität: ".$SOC." %\n";




  /************************************************************************
  //  Abfrage der Kontakte aus den aktiven Relais
  //  $db,$client,$relais,$topic,$wert
  ************************************************************************/
  if ($var["Relais1TopicFormat"] == 0) {
    $Relais1Kontakte = relais_abfragen($db,$client,1,$var["Relais1Topic"]."/cmnd/status", null);
  }
  if ($var["Relais1TopicFormat"] == 1) {
    $Relais1Kontakte = relais_abfragen($db,$client,1,"cmnd/".$var["Relais1Topic"]."/status", null);
  }
  if(count($Relais1Kontakte) == 0) {
    log_schreiben("Keine Antwort vom Relais 1","",2);
    goto weiter;
  }
  $MQTTDaten = array();


  if (!isset($Relais1Kontakte[2]))   {
    $Relais1Kontakte[2] = 0;
  }
  if (!isset($Relais1Kontakte[3]))   {
    $Relais1Kontakte[3] = 0;
  }
  if (!isset($Relais1Kontakte[4]))   {
    $Relais1Kontakte[4] = 0;
  }




  /***************************************************************************
  //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN 
  //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN 
  ***************************************************************************/

  if ($Relais1Kontakte[1] == 0) {
    echo "Relais 1 Kontakt 1 ist ausgeschaltet.\n";
    log_schreiben("Relais 1 Kontakt 1 ist ausgeschaltet","",3);
    /********************************************************************
    //  Start Logik
    //  Relais 1 Kontakt 1 ist zur Zeit ausgeschaltet
    //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     
    ********************************************************************/
    $Relais1Kontakt1Auswertung = 0;
    $i = 1;
    $Geraete = 0;
    if ($LRaktiv and $var["Relais1K1PVein"] != null ) {
      if ($PVLeistung >= $var["Relais1K1PVein"] ) {
        log_schreiben("PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K1PVein"],"",3);
        echo "PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K1PVein"]."\n";
        $Relais1Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K1PVein"],"",2);
        $Ergebnis[$i] = false;
      }
      $Bedingung[$i] = $var["Relais1K1PVBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($WRaktiv and $var["Relais1K1ACein"] != null) {
      if ($ACLeistung >= $var["Relais1K1ACein"] ) {
        log_schreiben("AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K1ACein"],"",3);
        echo "AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K1ACein"]."\n";
        $Relais1Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K1ACein"],"",2);
      }
      $Bedingung[$i] = $var["Relais1K1ACBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($SMaktiv and $var["Relais1K1SMein"] != null) {
      if ($Einspeisung >= $var["Relais1K1SMein"] ) {
        log_schreiben("Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais1K1SMein"],"",3);
        echo "Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais1K1SMein"]."\n";
        $Relais1Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("Einspeisung ".$Einspeisung." ist niedriger als die Vorgabe: ".$var["Relais1K1SMein"],"",2);
      }
      $Bedingung[$i] = $var["Relais1K1SMBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($BMSaktiv and $var["Relais1K1BMSein"] != null) {
      if ($SOC >= $var["Relais1K1BMSein"] ) {
        log_schreiben("SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais1K1BMSein"]."%","",3);
        echo "SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais1K1BMSein"]."%\n";
        $Relais1Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais1K1BMSein"]."%","",2);
      }
      $i++;
      $Geraete++;
    }


    if ($Geraete > 1) {
      $Relais1Kontakt1Auswertung = auswertung($Ergebnis,$Bedingung,$Relais1Kontakt1Auswertung,$Geraete);
    }


    if ($Relais1Kontakt1Auswertung == 1) {
      /********************************************************************
      //  Das Relais 1 Kontakt 1 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 1 Kontakt 1 wird eingeschaltet: ".$Relais1Kontakt1Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais1Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 1 Kontakt 1 wird jetzt ein geschaltet.","",2);
      $rc = relais_schalten($db,$client,"Relais1Kontakt1",$Relais1[1],$Relais1WertON,1);

    }

  }

  /****************************************************************************/

  if ($Relais1Kontakte[2] == 0 and $var["Relais1AnzKontakte"] > 1) {
    echo "Relais 1 Kontakt 2 ist ausgeschaltet.\n";
    log_schreiben("Relais 1 Kontakt 2 ist ausgeschaltet","",3);
    /********************************************************************
    //  Start Logik
    //  Relais 1 Kontakt 2 ist zur Zeit ausgeschaltet
    //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     
    ********************************************************************/
    $Relais1Kontakt2Auswertung = 0;
    $i = 1;
    $Geraete = 0;
    if ($LRaktiv and $var["Relais1K2PVein"] != null ) {
      if ($PVLeistung >= $var["Relais1K1PVein"] ) {
        log_schreiben("PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K2PVein"],"",3);
        echo "PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K2PVein"]."\n";
        $Relais1Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K2PVein"],"",2);
        $Ergebnis[$i] = false;
      }
      $Bedingung[$i] = $var["Relais1K2PVBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($WRaktiv and $var["Relais1K2ACein"] != null) {
      if ($ACLeistung >= $var["Relais1K2ACein"] ) {
        log_schreiben("AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K2ACein"],"",3);
        echo "AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais1K2ACein"]."\n";
        $Relais1Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K2ACein"],"",2);
      }
      $Bedingung[$i] = $var["Relais1K2ACBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($SMaktiv and $var["Relais1K2SMein"] != null) {
      if ($Einspeisung >= $var["Relais1K2SMein"] ) {
        log_schreiben("Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais1K2SMein"],"",3);
        echo "Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais1K2SMein"]."\n";
        $Relais1Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("Einspeisung ".$Einspeisung." ist niedriger als die Vorgabe: ".$var["Relais1K2SMein"],"",2);
      }
      $Bedingung[$i] = $var["Relais1K2SMBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($BMSaktiv and $var["Relais1K2BMSein"] != null) {
      if ($SOC >= $var["Relais1K2BMSein"] ) {
        log_schreiben("SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais1K2BMSein"]."%","",3);
        echo "SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais1K2BMSein"]."%\n";
        $Relais1Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais1K2BMSein"]."%","",2);
      }
      $i++;
      $Geraete++;
    }


    if ($Geraete > 1) {
      $Relais1Kontakt2Auswertung = auswertung($Ergebnis,$Bedingung,$Relais1Kontakt2Auswertung,$Geraete);
    }


    if ($Relais1Kontakt2Auswertung == 1) {
      /********************************************************************
      //  Das Relais 1 Kontakt 2 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 1 Kontakt 2 wird eingeschaltet: ".$Relais1Kontakt2Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais1Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 1 Kontakt 2 wird jetzt ein geschaltet.","",2);
      $rc = relais_schalten($db,$client,"Relais1Kontakt2",$Relais1[2],$Relais1WertON,1);

    }

  }






  /***************************************************************************
  //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
  //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
  ***************************************************************************/

  if ($Relais1Kontakte[1] == 1) {
    //  Soll der Kontakt per Zeiteinstellung ausgeschaltet werden? ( 0 = nein )
    if ($var["Relais1K1ausMinuten"] == 0) {

      log_schreiben("Relais 1 Kontakt 1 ist eingeschaltet","",3);
      /********************************************************************
      //  Start Logik
      //  Relais 1 Kontakt 1 ist zur Zeit eingeschaltet
      //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
      ********************************************************************/
      $Relais1Kontakt1Auswertung = 0;
      $i = 1;
      $Geraete = 0;
      if ($LRaktiv and $var["Relais1K1PVaus"] != null ) {
        if ($PVLeistung < $var["Relais1K1PVaus"] ) {
          log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K1PVaus"],"",3);
          echo "PV Leistung ".$PVLeistung." ist niedriger der Vorgabe: ".$var["Relais1K1PVaus"]."\n";
          $Relais1Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("PV Leistung ".$PVLeistung." ist größer als die Vorgabe: ".$var["Relais1K1PVaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais1K1PVBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($WRaktiv and $var["Relais1K1ACaus"] != null) {
        if ($ACLeistung < $var["Relais1K1ACaus"] ) {
          log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K1ACaus"],"",3);
          echo "AC Leistung ".$ACLeistung." ist kleiner Vorgabe: ".$var["Relais1K1ACaus"]."\n";
          $Relais1Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("AC Leistung ".$ACLeistung." ist größer als die Vorgabe: ".$var["Relais1K1ACaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais1K1ACBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($SMaktiv and $var["Relais1K1SMaus"] != null) {
        if ($Bezug < $var["Relais1K1SMaus"] ) {
          log_schreiben("Einspeisung ".$Bezug." ist niedriger als die Vorgabe: ".$var["Relais1K1SMaus"],"",3);
          echo "Bezug ".$Bezug." ist niedriger der Vorgabe: ".$var["Relais1K1SMaus"]."\n";
          $Relais1Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("Bezug ".$Bezug." ist größer als die Vorgabe: ".$var["Relais1K1SMaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais1K1SMBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($BMSaktiv and $var["Relais1K1BMSaus"] != null) {
        if ($SOC < $var["Relais1K1BMSaus"] ) {
          log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais1K1BMSaus"]."%","",3);
          echo "SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais1K1BMSaus"]."%\n";
          $Relais1Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("SOC ".$SOC."% ist größer als die Vorgabe: ".$var["Relais1K1BMSaus"]."%","",2);
        }
        $i++;
        $Geraete++;
      }

      if ($Geraete > 1) {
        $Relais1Kontakt1Auswertung = auswertung($Ergebnis,$Bedingung,$Relais1Kontakt1Auswertung,$Geraete);
      }

    }
    else {
      // Der Kontakt 1 soll per Zeitsteuerung ausgeschaltet werden.
      // Ist der Zeitstempel vorhanden?
      if ($var["Relais1Kontakt1Timestamp"] == 0)  {
        // Falls nicht eintragen.
        $sql = "Update waermepumpen set Relais1Kontakt1Timestamp = ".time()." where Id = 1";
        $statement = $db->query($sql);
        $Startzeit = time();
      }
      else {
        $Startzeit = $var["Relais1Kontakt1Timestamp"];

        if (($Startzeit + ($var["Relais1K1ausMinuten"] * 60) - 20) <= time())  {
          $Relais1Kontakt1Auswertung = 1;
        }
        else {
          $Relais1Kontakt1Auswertung = 0;
          log_schreiben("es dauert noch ".(($Startzeit + ($var["Relais1K1ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung","",3);
          echo "es dauert noch ".(($Startzeit + ($var["Relais1K1ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung\n";
        }
      }

    }


    if ($Relais1Kontakt1Auswertung == 1) {
      /********************************************************************
      //  Das Relais 1 Kontakt 1 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 1 Kontakt 1 wird ausgeschaltet: ".$Relais1Kontakt1Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais1Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 1 Kontakt 1 wird aus geschaltet.","",2);

      $rc = relais_schalten($db,$client,"Relais1Kontakt1",$Relais1[1],$Relais1WertOFF,1);

    }


  }


  if ($Relais1Kontakte[2] == 1) {
    //  Soll der Kontakt per Zeiteinstellung ausgeschaltet werden? ( 0 = nein )
    if ($var["Relais1K2ausMinuten"] == 0 and $var["Relais1AnzKontakte"] > 1) {

      log_schreiben("Relais 1 Kontakt 2 ist eingeschaltet","",3);
      /********************************************************************
      //  Start Logik
      //  Relais 1 Kontakt 1 ist zur Zeit eingeschaltet
      //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
      ********************************************************************/
      $Relais1Kontakt2Auswertung = 0;
      $i = 1;
      $Geraete = 0;
      if ($LRaktiv and $var["Relais1K2PVaus"] != null ) {
        if ($PVLeistung < $var["Relais1K2PVaus"] ) {
          log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K2PVaus"],"",3);
          echo "PV Leistung ".$PVLeistung." ist niedriger der Vorgabe: ".$var["Relais1K2PVaus"]."\n";
          $Relais1Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("PV Leistung ".$PVLeistung." ist größer als die Vorgabe: ".$var["Relais1K2PVaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais1K2PVBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($WRaktiv and $var["Relais1K2ACaus"] != null) {
        if ($ACLeistung < $var["Relais1K2ACaus"] ) {
          log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais1K2ACaus"],"",3);
          echo "AC Leistung ".$ACLeistung." ist kleiner Vorgabe: ".$var["Relais1K2ACaus"]."\n";
          $Relais1Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("AC Leistung ".$ACLeistung." ist größer als die Vorgabe: ".$var["Relais1K2ACaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais1K2ACBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($SMaktiv and $var["Relais1K2SMaus"] != null) {
        if ($Bezug < $var["Relais1K2SMaus"] ) {
          log_schreiben("Einspeisung ".$Bezug." ist niedriger als die Vorgabe: ".$var["Relais1K2SMaus"],"",3);
          echo "Bezug ".$Bezug." ist niedriger der Vorgabe: ".$var["Relais1K2SMaus"]."\n";
          $Relais1Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("Bezug ".$Bezug." ist größer als die Vorgabe: ".$var["Relais1K2SMaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais1K2SMBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($BMSaktiv and $var["Relais1K2BMSaus"] != null) {
        if ($SOC < $var["Relais1K2BMSaus"] ) {
          log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais1K2BMSaus"]."%","",3);
          echo "SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais1K2BMSaus"]."%\n";
          $Relais1Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("SOC ".$SOC."% ist größer als die Vorgabe: ".$var["Relais1K2BMSaus"]."%","",2);
        }
        $i++;
        $Geraete++;
      }

      if ($Geraete > 1) {
        $Relais1Kontakt1Auswertung = auswertung($Ergebnis,$Bedingung,$Relais1Kontakt2Auswertung,$Geraete);
      }

    }
    else {
      // Der Kontakt 1 soll per Zeitsteuerung ausgeschaltet werden.
      // Ist der Zeitstempel vorhanden?
      if ($var["Relais1Kontakt2Timestamp"] == 0)  {
        // Falls nicht eintragen.
        $sql = "Update waermepumpen set Relais1Kontakt2Timestamp = ".time()." where Id = 1";
        $statement = $db->query($sql);
        $Startzeit = time();
      }
      else {
        $Startzeit = $var["Relais1Kontakt2Timestamp"];

        if (($Startzeit + ($var["Relais1K2ausMinuten"] * 60) - 20) <= time())  {
          $Relais1Kontakt2Auswertung = 1;
        }
        else {
          $Relais1Kontakt2Auswertung = 0;
          log_schreiben("es dauert noch ".(($Startzeit + ($var["Relais1K2ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung","",3);
          echo "es dauert noch ".(($Startzeit + ($var["Relais1K2ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung\n";
        }
      }

    }

    if ($Relais1Kontakt2Auswertung == 1) {
      /********************************************************************
      //  Das Relais 1 Kontakt 1 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 1 Kontakt 2 wird ausgeschaltet: ".$Relais1Kontakt2Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais1Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 1 Kontakt 2 wird aus geschaltet.","",2);

      $rc = relais_schalten($db,$client,"Relais1Kontakt2",$Relais1[2],$Relais1WertOFF,1);

    }

  }







}


weiter:

/**********************************************************************
//  Relais Steuerkreis 2   Relais Steuerkreis 2   Relais Steuerkreis 2
//  Relais Steuerkreis 2   Relais Steuerkreis 2   Relais Steuerkreis 2
//
//  Hier beginnt die Auswertung und Steuerung
//
//  *****************************************
**********************************************************************/
if ( $var["Relais2aktiv"] == 1) {
  log_schreiben("Relais 2 ist aktiviert.","",3);



  /************************************************************************
  //  Abfrage der Kontakte aus den aktiven Relais
  //  $db,$client,$relais,$topic,$wert
  ************************************************************************/
  if ($var["Relais2TopicFormat"] == 0) {
    $Relais2Kontakte = relais_abfragen($db,$client,2,$var["Relais2Topic"]."/cmnd/status", null);
  }
  if ($var["Relais2TopicFormat"] == 1) {
    $Relais2Kontakte = relais_abfragen($db,$client,2,"cmnd/".$var["Relais2Topic"]."/status", null);
  }
  if(count($Relais2Kontakte) == 0) {
    log_schreiben("Keine Antwort vom Relais 2","",2);
    goto ende;
  }
  $MQTTDaten = array();


  if (!isset($Relais2Kontakte[2]))   {
    $Relais2Kontakte[2] = 0;
  }
  if (!isset($Relais2Kontakte[3]))   {
    $Relais2Kontakte[3] = 0;
  }
  if (!isset($Relais2Kontakte[4]))   {
    $Relais2Kontakte[4] = 0;
  }




  /***************************************************************************
  //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN 
  //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN 
  ***************************************************************************/

  if ($Relais2Kontakte[1] == 0) {
    echo "Relais 2 Kontakt 1 ist ausgeschaltet.\n";
    log_schreiben("Relais 2 Kontakt 1 ist ausgeschaltet","",3);
    /********************************************************************
    //  Start Logik
    //  Relais 2 Kontakt 1 ist zur Zeit ausgeschaltet
    //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     
    ********************************************************************/
    $Relais2Kontakt1Auswertung = 0;
    $i = 1;
    $Geraete = 0;
    if ($LRaktiv and $var["Relais2K1PVein"] != null ) {
      if ($PVLeistung >= $var["Relais2K1PVein"] ) {
        log_schreiben("PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K1PVein"],"",3);
        echo "PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K1PVein"]."\n";
        $Relais2Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K1PVein"],"",2);
        $Ergebnis[$i] = false;
      }
      $Bedingung[$i] = $var["Relais2K1PVBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($WRaktiv and $var["Relais2K1ACein"] != null) {
      if ($ACLeistung >= $var["Relais2K1ACein"] ) {
        log_schreiben("AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K1ACein"],"",3);
        echo "AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K1ACein"]."\n";
        $Relais2Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K1ACein"],"",2);
      }
      $Bedingung[$i] = $var["Relais2K1ACBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($SMaktiv and $var["Relais2K1SMein"] != null) {
      if ($Einspeisung >= $var["Relais2K1SMein"] ) {
        log_schreiben("Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais2K1SMein"],"",3);
        echo "Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais2K1SMein"]."\n";
        $Relais2Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("Einspeisung ".$Einspeisung." ist niedriger als die Vorgabe: ".$var["Relais2K1SMein"],"",2);
      }
      $Bedingung[$i] = $var["Relais2K1SMBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($BMSaktiv and $var["Relais2K1BMSein"] != null) {
      if ($SOC >= $var["Relais2K1BMSein"] ) {
        log_schreiben("SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais2K1BMSein"]."%","",3);
        echo "SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais2K1BMSein"]."%\n";
        $Relais2Kontakt1Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais2K1BMSein"]."%","",2);
      }
      $i++;
      $Geraete++;
    }


    if ($Geraete > 1) {
      $Relais2Kontakt1Auswertung = auswertung($Ergebnis,$Bedingung,$Relais2Kontakt1Auswertung,$Geraete);
    }


    if ($Relais2Kontakt1Auswertung == 1) {
      /********************************************************************
      //  Das Relais 2 Kontakt 1 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 2 Kontakt 1 wird eingeschaltet: ".$Relais2Kontakt1Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais2Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 2 Kontakt 1 wird jetzt ein geschaltet.","",2);
      $rc = relais_schalten($db,$client,"Relais2Kontakt1",$Relais2[1],$Relais2WertON,1);

    }

  }

  /****************************************************************************/

  if ($Relais2Kontakte[2] == 0 and $var["Relais2AnzKontakte"] > 1) {
    echo "Relais 2 Kontakt 2 ist ausgeschaltet.\n";
    log_schreiben("Relais 2 Kontakt 2 ist ausgeschaltet","",3);
    /********************************************************************
    //  Start Logik
    //  Relais 2 Kontakt 2 ist zur Zeit ausgeschaltet
    //  EIN     EIN     EIN     EIN     EIN     EIN     EIN     EIN     
    ********************************************************************/
    $Relais2Kontakt2Auswertung = 0;
    $i = 1;
    $Geraete = 0;
    if ($LRaktiv and $var["Relais2K2PVein"] != null ) {
      if ($PVLeistung >= $var["Relais2K1PVein"] ) {
        log_schreiben("PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K2PVein"],"",3);
        echo "PV Leistung ".$PVLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K2PVein"]."\n";
        $Relais2Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K2PVein"],"",2);
        $Ergebnis[$i] = false;
      }
      $Bedingung[$i] = $var["Relais2K2PVBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($WRaktiv and $var["Relais2K2ACein"] != null) {
      if ($ACLeistung >= $var["Relais2K2ACein"] ) {
        log_schreiben("AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K2ACein"],"",3);
        echo "AC Leistung ".$ACLeistung." ist größer/gleich Vorgabe: ".$var["Relais2K2ACein"]."\n";
        $Relais2Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K2ACein"],"",2);
      }
      $Bedingung[$i] = $var["Relais2K2ACBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($SMaktiv and $var["Relais2K2SMein"] != null) {
      if ($Einspeisung >= $var["Relais2K2SMein"] ) {
        log_schreiben("Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais2K2SMein"],"",3);
        echo "Einspeisung ".$Einspeisung." ist größer/gleich Vorgabe: ".$var["Relais2K2SMein"]."\n";
        $Relais2Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("Einspeisung ".$Einspeisung." ist niedriger als die Vorgabe: ".$var["Relais2K2SMein"],"",2);
      }
      $Bedingung[$i] = $var["Relais2K2SMBedingungein"];
      $i++;
      $Geraete++;
    }
    if ($BMSaktiv and $var["Relais2K2BMSein"] != null) {
      if ($SOC >= $var["Relais2K2BMSein"] ) {
        log_schreiben("SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais2K2BMSein"]."%","",3);
        echo "SOC ".$SOC."% ist größer/gleich Vorgabe: ".$var["Relais2K2BMSein"]."%\n";
        $Relais2Kontakt2Auswertung = 1;
        $Ergebnis[$i] = true;
      }
      else {
        $Ergebnis[$i] = false;
        log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais2K2BMSein"]."%","",2);
      }
      $i++;
      $Geraete++;
    }


    if ($Geraete > 1) {
      $Relais2Kontakt2Auswertung = auswertung($Ergebnis,$Bedingung,$Relais2Kontakt2Auswertung,$Geraete);
    }


    if ($Relais2Kontakt2Auswertung == 1) {
      /********************************************************************
      //  Das Relais 2 Kontakt 2 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 2 Kontakt 2 wird eingeschaltet: ".$Relais2Kontakt2Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais2Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 2 Kontakt 2 wird jetzt ein geschaltet.","",2);
      $rc = relais_schalten($db,$client,"Relais2Kontakt2",$Relais2[2],$Relais2WertON,1);

    }

  }






  /***************************************************************************
  //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
  //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
  ***************************************************************************/

  if ($Relais2Kontakte[1] == 1) {
    //  Soll der Kontakt per Zeiteinstellung ausgeschaltet werden? ( 0 = nein )
    if ($var["Relais2K1ausMinuten"] == 0) {

      log_schreiben("Relais 2 Kontakt 1 ist eingeschaltet","",3);
      /********************************************************************
      //  Start Logik
      //  Relais 2 Kontakt 1 ist zur Zeit eingeschaltet
      //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
      ********************************************************************/
      $Relais2Kontakt1Auswertung = 0;
      $i = 1;
      $Geraete = 0;
      if ($LRaktiv and $var["Relais2K1PVaus"] != null ) {
        if ($PVLeistung < $var["Relais2K1PVaus"] ) {
          log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K1PVaus"],"",3);
          echo "PV Leistung ".$PVLeistung." ist niedriger der Vorgabe: ".$var["Relais2K1PVaus"]."\n";
          $Relais2Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("PV Leistung ".$PVLeistung." ist größer als die Vorgabe: ".$var["Relais2K1PVaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais2K1PVBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($WRaktiv and $var["Relais2K1ACaus"] != null) {
        if ($ACLeistung < $var["Relais2K1ACaus"] ) {
          log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K1ACaus"],"",3);
          echo "AC Leistung ".$ACLeistung." ist kleiner Vorgabe: ".$var["Relais2K1ACaus"]."\n";
          $Relais2Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("AC Leistung ".$ACLeistung." ist größer als die Vorgabe: ".$var["Relais2K1ACaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais2K1ACBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($SMaktiv and $var["Relais2K1SMaus"] != null) {
        if ($Bezug < $var["Relais2K1SMaus"] ) {
          log_schreiben("Einspeisung ".$Bezug." ist niedriger als die Vorgabe: ".$var["Relais2K1SMaus"],"",3);
          echo "Bezug ".$Bezug." ist niedriger der Vorgabe: ".$var["Relais2K1SMaus"]."\n";
          $Relais2Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("Bezug ".$Bezug." ist größer als die Vorgabe: ".$var["Relais2K1SMaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais2K1SMBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($BMSaktiv and $var["Relais2K1BMSaus"] != null) {
        if ($SOC < $var["Relais2K1BMSaus"] ) {
          log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais2K1BMSaus"]."%","",3);
          echo "SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais2K1BMSaus"]."%\n";
          $Relais2Kontakt1Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("SOC ".$SOC."% ist größer als die Vorgabe: ".$var["Relais2K1BMSaus"]."%","",2);
        }
        $i++;
        $Geraete++;
      }

      if ($Geraete > 1) {
        $Relais2Kontakt1Auswertung = auswertung($Ergebnis,$Bedingung,$Relais2Kontakt1Auswertung,$Geraete);
      }

    }
    else {
      // Der Kontakt 1 soll per Zeitsteuerung ausgeschaltet werden.
      // Ist der Zeitstempel vorhanden?
      if ($var["Relais2Kontakt1Timestamp"] == 0)  {
        // Falls nicht eintragen.
        $sql = "Update waermepumpen set Relais2Kontakt1Timestamp = ".time()." where Id = 1";
        $statement = $db->query($sql);
        $Startzeit = time();
      }
      else {
        $Startzeit = $var["Relais2Kontakt1Timestamp"];

        if (($Startzeit + ($var["Relais2K1ausMinuten"] * 60) - 20) <= time())  {
          $Relais2Kontakt1Auswertung = 1;
        }
        else {
          $Relais2Kontakt1Auswertung = 0;
          log_schreiben("es dauert noch ".(($Startzeit + ($var["Relais2K1ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung","",3);
          echo "es dauert noch ".(($Startzeit + ($var["Relais2K1ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung\n";
        }
      }

    }


    if ($Relais2Kontakt1Auswertung == 1) {
      /********************************************************************
      //  Das Relais 2 Kontakt 1 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 2 Kontakt 1 wird ausgeschaltet: ".$Relais2Kontakt1Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais2Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 2 Kontakt 1 wird aus geschaltet.","",2);

      $rc = relais_schalten($db,$client,"Relais2Kontakt1",$Relais2[1],$Relais2WertOFF,1);

    }


  }


  if ($Relais2Kontakte[2] == 1) {
    //  Soll der Kontakt per Zeiteinstellung ausgeschaltet werden? ( 0 = nein )
    if ($var["Relais2K2ausMinuten"] == 0 and $var["Relais2AnzKontakte"] > 1) {

      log_schreiben("Relais 2 Kontakt 2 ist eingeschaltet","",3);
      /********************************************************************
      //  Start Logik
      //  Relais 2 Kontakt 1 ist zur Zeit eingeschaltet
      //  AUS     AUS     AUS     AUS     AUS     AUS     AUS     AUS
      ********************************************************************/
      $Relais2Kontakt2Auswertung = 0;
      $i = 1;
      $Geraete = 0;
      if ($LRaktiv and $var["Relais2K2PVaus"] != null ) {
        if ($PVLeistung < $var["Relais2K2PVaus"] ) {
          log_schreiben("PV Leistung ".$PVLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K2PVaus"],"",3);
          echo "PV Leistung ".$PVLeistung." ist niedriger der Vorgabe: ".$var["Relais2K2PVaus"]."\n";
          $Relais2Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("PV Leistung ".$PVLeistung." ist größer als die Vorgabe: ".$var["Relais2K2PVaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais2K2PVBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($WRaktiv and $var["Relais2K2ACaus"] != null) {
        if ($ACLeistung < $var["Relais2K2ACaus"] ) {
          log_schreiben("AC Leistung ".$ACLeistung." ist niedriger als die Vorgabe: ".$var["Relais2K2ACaus"],"",3);
          echo "AC Leistung ".$ACLeistung." ist kleiner Vorgabe: ".$var["Relais2K2ACaus"]."\n";
          $Relais2Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("AC Leistung ".$ACLeistung." ist größer als die Vorgabe: ".$var["Relais2K2ACaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais2K2ACBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($SMaktiv and $var["Relais2K2SMaus"] != null) {
        if ($Bezug < $var["Relais2K2SMaus"] ) {
          log_schreiben("Einspeisung ".$Bezug." ist niedriger als die Vorgabe: ".$var["Relais2K2SMaus"],"",3);
          echo "Bezug ".$Bezug." ist niedriger der Vorgabe: ".$var["Relais2K2SMaus"]."\n";
          $Relais2Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("Bezug ".$Bezug." ist größer als die Vorgabe: ".$var["Relais2K2SMaus"],"",2);
        }
        $Bedingung[$i] = $var["Relais2K2SMBedingungaus"];
        $i++;
        $Geraete++;
      }
      if ($BMSaktiv and $var["Relais2K2BMSaus"] != null) {
        if ($SOC < $var["Relais2K2BMSaus"] ) {
          log_schreiben("SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais2K2BMSaus"]."%","",3);
          echo "SOC ".$SOC."% ist niedriger als die Vorgabe: ".$var["Relais2K2BMSaus"]."%\n";
          $Relais2Kontakt2Auswertung = 1;
          $Ergebnis[$i] = true;
        }
        else {
          log_schreiben("SOC ".$SOC."% ist größer als die Vorgabe: ".$var["Relais2K2BMSaus"]."%","",2);
        }
        $i++;
        $Geraete++;
      }

      if ($Geraete > 1) {
        $Relais2Kontakt1Auswertung = auswertung($Ergebnis,$Bedingung,$Relais2Kontakt2Auswertung,$Geraete);
      }

    }
    else {
      // Der Kontakt 1 soll per Zeitsteuerung ausgeschaltet werden.
      // Ist der Zeitstempel vorhanden?
      if ($var["Relais2Kontakt2Timestamp"] == 0)  {
        // Falls nicht eintragen.
        $sql = "Update waermepumpen set Relais2Kontakt2Timestamp = ".time()." where Id = 1";
        $statement = $db->query($sql);
        $Startzeit = time();
      }
      else {
        $Startzeit = $var["Relais2Kontakt2Timestamp"];

        if (($Startzeit + ($var["Relais2K2ausMinuten"] * 60) - 20) <= time())  {
          $Relais2Kontakt2Auswertung = 1;
        }
        else {
          $Relais2Kontakt2Auswertung = 0;
          log_schreiben("es dauert noch ".(($Startzeit + ($var["Relais2K2ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung","",3);
          echo "es dauert noch ".(($Startzeit + ($var["Relais2K2ausMinuten"] * 60)) - time())." Sekunden bis zur Abschaltung\n";
        }
      }

    }

    if ($Relais2Kontakt2Auswertung == 1) {
      /********************************************************************
      //  Das Relais 2 Kontakt 1 muss eingeschaltet werden!
      //
      ********************************************************************/
      echo "Relais 2 Kontakt 2 wird ausgeschaltet: ".$Relais2Kontakt2Auswertung."\n";
      /********************************************************************
      // Schalten von Relais Kontakten:
      // relais_schalten(Datenbank,Mosquitto,Relaiskontakt,Topic,Wert,QoS)
      // $var["Relais2Kontakt1"] wird aktualisiert
      ********************************************************************/
      log_schreiben("Relais 2 Kontakt 2 wird aus geschaltet.","",2);

      $rc = relais_schalten($db,$client,"Relais2Kontakt2",$Relais2[2],$Relais2WertOFF,1);

    }

  }

}






ende:



$client->disconnect();


Ausgang:

$db1 = null;
$db2 = null;


unset($client);

log_schreiben("---------------------------------------------------------","ENDE",1);

exit;




function db_connect($Database) {

  return  new PDO('sqlite:'.$Database);

}


function influxDB_lesen($Datenbankname,$Measurement)   {

  // Alle aktuellen Daten eines Measurement lesen.
  //
  $ch = curl_init('http://localhost/query?db='.$Datenbankname.'&precision=s&q='.urlencode('select * from '.$Measurement.' order by time desc limit 1'));
  $rc = datenbank($ch);
  if (!isset($rc["JSON_Ausgabe"]["results"][0]["series"])) {
    if (isset($rc["JSON_Ausgabe"]["results"][0]["error"])) {
      log_schreiben($rc["JSON_Ausgabe"]["results"][0]["error"],"",1);
      return false;
    }
    log_schreiben("Keine Daten vorhanden.","",1);
    return false;
  }
  else {
    for ($i = 1; $i < count($rc["JSON_Ausgabe"]["results"][0]["series"][0]["columns"]); $i++) {
      $influxDB[$rc["JSON_Ausgabe"]["results"][0]["series"][0]["columns"][$i]] = $rc["JSON_Ausgabe"]["results"][0]["series"][0]["values"][0][$i];
    }
    log_schreiben("Datenbank: '".$Datenbankname."' ".print_r($influxDB,1),"",4);
  }

  return $influxDB;
}

function datenbank($ch,$query="") {

  $Ergebnis = array();
  $Ergebnis["Ausgabe"] = false;

  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);                //timeout in second s
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
  curl_setopt($ch, CURLOPT_PORT, 8086);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $Ergebnis["result"] = curl_exec($ch);
  $Ergebnis["rc_info"] = curl_getinfo ($ch);
  $Ergebnis["JSON_Ausgabe"] = json_decode($Ergebnis["result"],true,10);
  $Ergebnis["errorno"] = curl_errno($ch);

  if ($Ergebnis["rc_info"]["http_code"] == 200 or $Ergebnis["rc_info"]["http_code"] == 204) {
    $Ergebnis["Ausgabe"] = true;
  }

  curl_close($ch);
  unset($ch);

  return $Ergebnis;
}

function connect($r,$message) {
  global $Brokermeldung;
  log_schreiben("Broker: ".$message,"",3);
  $Brokermeldung = $message;
}
 
function publish() {
  log_schreiben("Mesage published.","",4);
}
 
function disconnect() {
  log_schreiben("Broker disconnect erfolgreich.","",3);
}

function subscribe() {
  log_schreiben("Subscribed to a topic.","",4);
  // echo "subscribe\n";
}
 
function logger()   {
  global $MQTTDaten;
  log_schreiben(print_r(func_get_args(),1),"",4);
  $p = func_get_args();
  $MQTTDaten["MQTTStatus"] = $p;
  // print_r($p);
}

function message($message) {
  global $MQTTDaten;
  $MQTTDaten["MQTTRetain"] = 0;
  $MQTTDaten["MQTTMessageReturnText"] =  "RX-OK";
  $MQTTDaten["MQTTNachricht"] =  $message->payload;
  $MQTTDaten["MQTTTopic"] =  $message->topic;
  $MQTTDaten["MQTTQos"] =  $message->qos;
  $MQTTDaten["MQTTMid"] =  $message->mid;
  $MQTTDaten["MQTTRetain"] =  $message->retain;
}




function relais_schalten($db,$client,$relais,$topic,$wert,$qos=1) {

  switch (strtoupper($wert)) {
    case "ON":
    case "AN":
      $zustand = 1;
    break;

    case "OFF":
    case "AUS":
      $zustand = 0;
    break;

    default:
      $zustand = 0;
  }


  $mid = 0;
  while ($mid == 0) {
    $client->loop();
    //  $client->publish($topic, $wert, $qos, [$retain])
    $mid = $client->publish($topic, $wert, $qos);
    log_schreiben("Sent message ID: {$mid} ".$topic." ".$wert,"",3);
    $client->loop();
  }
  if ($zustand == 1) {
    $sql = "Update waermepumpen set ".$relais." = ".$zustand.", ".$relais."Timestamp = ".time()." where Id = 1";
  }
  else {
    $sql = "Update waermepumpen set ".$relais." = ".$zustand." where Id = 1";
  }
  $statement = $db->query($sql);
  if ($statement->rowCount() != 1) {
    log_schreiben("Update nicht erfolgt. [ ".$sql." ]","",2);
    log_schreiben(print_r($statement->errorInfo(),1),"",2);
    return false;
  }
  // echo $sql."\n";

  return $mid;
}


function relais_abfragen($db,$client,$relais,$topic,$wert) {
  global $MQTTDaten;

  $Power = array();
  $sql = "SELECT * FROM waermepumpen";
  $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  //  Alle Felder auslesen
  $var = $result[0];

  $client->loop();
  if ($relais == 1) {
    $client->subscribe("+/".$var["Relais1Topic"]."/#", 0); // Subscribe
    $client->subscribe($var["Relais1Topic"]."/#", 0);      // Subscribe
  }
  else {
    $client->subscribe("+/".$var["Relais2Topic"]."/#", 0); // Subscribe
    $client->subscribe($var["Relais2Topic"]."/#", 0);      // Subscribe
  }
  $client->loop();

  for ($i=1;$i<20;$i++) {

    if (substr($MQTTDaten["MQTTStatus"][1],-7) == "PINGREQ") {
      log_schreiben("Keine Antwort vom Broker (Relais). Abbruch!","",3);
      break;
    }

    if (isset($MQTTDaten["MQTTNachricht"]))

    // echo "Nachricht: ".$MQTTDaten["MQTTNachricht"]."\n";

    /*********************************************************************
    //  MQTT Meldungen empfangen. Subscribing    Subscribing    Subscribing
    //  Hier werden die Daten vom Mosquitto Broker gelesen.
    *********************************************************************/
    log_schreiben(print_r($MQTTDaten,1),"",4);

    //  Ist das Relais "ONLINE"?
    if (isset($MQTTDaten["MQTTNachricht"])) {
      log_schreiben("Nachricht: ".$MQTTDaten["MQTTNachricht"],"",4);

      if (strtoupper($MQTTDaten["MQTTNachricht"]) == "ONLINE") {
        // Gerät ist online
        log_schreiben("Topic: ".$MQTTDaten["MQTTTopic"],"   ",3);
        $MQTTDaten = array();
        $MQTTDaten["MQTTPublishReturnCode"] = $client->publish($topic, 0, 0, false);
        $client->loop();
      }
      else {
      $values = json_decode($MQTTDaten["MQTTNachricht"], true);
      if (is_array($values)) {
        if (isset($values["StatusSTS"])) {
          log_schreiben(print_r($values["StatusSTS"],1),"",4);
          foreach ($values["StatusSTS"] as $k => $v) {
            $inputs[] = array("name"=>$k, "value"=>$v);
            if ($k == "POWER") {
              // Powerstatus
              if (strtoupper($v) == "ON")
                $Power[1] = 1;
              else
                $Power[1] = 0;
            }
            if ($k == "POWER1") {
              // Powerstatus
              if (strtoupper($v) == "ON")
                $Power[1] = 1;
              else
                $Power[1] = 0;
            }
            if ($k == "POWER2") {
              // Powerstatus
              if (strtoupper($v) == "ON")
                $Power[2] = 1;
              else
                $Power[2] = 0;        
            }
            if ($k == "POWER3") {
              // Powerstatus
              if (strtoupper($v) == "ON")
                $Power[3] = 1;
              else
                $Power[3] = 0;
            }
            if ($k == "POWER4") {
              // Powerstatus
              if (strtoupper($v) == "ON")
                $Power[4] = 1;
              else
                $Power[4] = 0;
            }
          }
          break;
        }
      }
      }
    }
    $client->loop();
  }
  return $Power;
}





/**************************************************************************
//  Log Eintrag in die Logdatei schreiben
//  $LogMeldung = Die Meldung ISO Format
//  $Loglevel=2   Loglevel 1-4   4 = Trace
**************************************************************************/

function log_schreiben($LogMeldung,$Titel="",$Loglevel=3,$UTF8=0){
  global $Tracelevel, $Pfad;

  $LogDateiName = $Pfad."/../log/automation.log";
  if (strlen($Titel) < 4) {
    switch ($Loglevel) {
      case 1:
        $Titel = "ERRO";
        break;
      case 2:
        $Titel = "WARN";
        break;
      case 3:
        $Titel = "INFO";
        break;
      default:
        $Titel = "    ";
        break;
    }
  }

  if ($Loglevel <= $Tracelevel) {

    if($UTF8) {
      $LogMeldung = utf8_encode($LogMeldung);
    }

    if ($handle = fopen($LogDateiName, 'a')) {
      //  Schreibe in die geöffnete Datei.
      //  Bei einem Fehler bis zu 3 mal versuchen.
      for ($i=1;$i<4;$i++) {
        $rc = fwrite($handle,date("d.m. H:i:s")." ".substr($Titel,0,4)." ".$LogMeldung."\n");
        if ($rc) {
          break;
        }
        sleep(1);
      }
      fclose($handle);
    }

  }

  return true;
}

function auswertung($Ergebnis,$Bedingung,$KontaktAuswertung,$Geraete) {
    print_r($Ergebnis);
    log_schreiben("Mehr als eine Bedingung aktiv","",3);
    echo "Mehr als eine Bedingung\n";
    for ($i = 1 ; $i <= $Geraete; $i++) {
      if ($i < $Geraete) {
        log_schreiben("Verknüpfung mit: ".$Bedingung[$i],"",3);
        echo $i." ++ ".$Bedingung[$i]."\n";
      }
      echo $i." == ".($Ergebnis[$i] ? "wahr" : "falsch")."\n";
      if ($Bedingung[$i] == "and") {

        if ($i == 1 and !$Ergebnis[$i]) {
          $KontaktAuswertung = 0;
          log_schreiben($i.". Parameter im Vergleich ist falsch. (and) ","",3);   
          break;
        }
        if (!$Ergebnis[$i] and !$Ergebnis[$i+1]) {
          $KontaktAuswertung = 0;
          log_schreiben("Beide Parameter im Vergleich sind falsch. (and) ","",3);   
          break;
        }
        if ($Ergebnis[$i] and $Ergebnis[$i+1]) {
          $KontaktAuswertung = 1;
          log_schreiben("Beide Parameter sind wahr. (and) ","",3);   
        }
        else {
          $Ergebnis[$i+1] = false;
          log_schreiben("Ein Parameter ist falsch. (and) ","",3);   
          $KontaktAuswertung = 0;
        }
      }
      elseif ($Bedingung[$i] == "or") {
        if ($Ergebnis[$i] or $Ergebnis[$i+1]) {
          $Ergebnis[$i+1] = true;
          $KontaktAuswertung = 1;
          log_schreiben("Einer der beiden Parameter ist wahr. (or) ","",3);   

        }
        else {
          $KontaktAuswertung = 0;
          log_schreiben("Beide Parameter sind falsch. (or) ","",3);   
        }
      }
      else {

      }
      log_schreiben("Relais schalten? ".($KontaktAuswertung ? "ja" : "nein"),"",3);
    echo "Relais schalten? ".($KontaktAuswertung ? "ja" : "nein")."\n";
    }

  return $KontaktAuswertung;
}



?>
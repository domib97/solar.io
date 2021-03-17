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
//  Es dient dem Auslesen des Phocos Any-Grid Wechselrichters über die USB 
//  Schnittstelle.
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
$Tracelevel = 7;  //  1 bis 10  10 = Debug
$Device = "WR";   // WR = Wechselrichter

if (!isset($funktionen)) {
  $funktionen = new funktionen();
}

$Version = "";
$RemoteDaten = true;
$Start = time();  // Timestamp festhalten
$funktionen->log_schreiben("-------------   Start  phocos_any_grid.php   ----------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",8);
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;

setlocale(LC_TIME,"de_DE.utf8");

// Im Fall, dass man die Device manuell eingeben muss
if (isset($USBDevice) and !empty($USBDevice)) {
  $USBRegler = $USBDevice;
}


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
//  Achtung! Dieser Wert wird jeden Tag um Mitternacht auf 0 gesetzt.
//
*****************************************************************************/
$StatusFile = $Pfad."/database/".$GeraeteNummer.".WhProTag_phocos.txt";
if (file_exists($StatusFile)) {
  /***************************************************************************
  //  Daten einlesen ...
  ***************************************************************************/
  $aktuelleDaten["WattstundenGesamtHeute"] = file_get_contents($StatusFile);
  $aktuelleDaten["WattstundenGesamtHeute"] = round($aktuelleDaten["WattstundenGesamtHeute"],2);
  $funktionen->log_schreiben("WattstundenGesamtHeute: ".$aktuelleDaten["WattstundenGesamtHeute"],"   ",8);
  if (empty($aktuelleDaten["WattstundenGesamtHeute"])){
      $aktuelleDaten["WattstundenGesamtHeute"] = 0;
  }
  if (date("H:i") == "00:00" or date("H:i") == "00:01") {   // Jede Nacht 0 Uhr
    $aktuelleDaten["WattstundenGesamtHeute"] = 0;       //  Tageszähler löschen
    $rc = file_put_contents($StatusFile,"0");
    $funktionen->log_schreiben("WattstundenGesamtHeute gelöscht.","    ",5);
  }
}
else {
  $aktuelleDaten["WattstundenGesamtHeute"] = 0;
  /***************************************************************************
  //  Inhalt der Status Datei anlegen.
  ***************************************************************************/
  $rc = file_put_contents($StatusFile,"0");
  if ($rc === false) {
    $funktionen->log_schreiben("Konnte die Datei kwhProTag_ax.txt nicht anlegen.","   ",5);
  }
}

//  Nach em Öffnen des Port muss sofort der Regler ausgelesen werden, sonst
//  sendet er asynchrone Daten!
$USB = $funktionen->openUSB($USBRegler);
if (!is_resource($USB)) {
  $funktionen->log_schreiben("USB Port kann nicht geöffnet werden. [1]","XX ",7);
  $funktionen->log_schreiben("Exit.... ","XX ",7);
  goto Ausgang;
}

stream_set_blocking ( $USB , false );

/************************************************************************************
//  Sollen Befehle an den Wechselrichter gesendet werden?
//
************************************************************************************/


$i = 1;
do {


  // Daten
  $Wert = false;
  $Antwort = "";
  $CRC = $funktionen->hex2str(dechex($funktionen->CRC16Normal("QPGS".$WR_Adresse)));
  $Nachricht = "QPGS".$WR_Adresse.$CRC;
  fputs ($USB, $Nachricht."\r");
  usleep(100000);       //  [normal 1] Es dauert etwas, bis die ersten Daten kommen ...


  for ($k = 1 ; $k < 200; $k++) {
    $rc = fgets($USB,4096); // 4096
    usleep(10000);  // Die Phocos Geraete sind so langsam...
    $Antwort .= $rc;

    if (substr($Antwort,-1) == "\r" and substr($Antwort,0,1) == "(") {

      if (substr($Antwort,1,3) == "NAK") {
        $Wert = false;
        $Antwort = substr($Antwort,0,4); // Den CRC abschneiden
      }
      else {
        $Wert = true;
      }
      $rc = "";
      break;
    }
  }

  if ($Wert === false) {
    $funktionen->log_schreiben("Datenübertragung vom Wechselrichter war erfolglos! Continue..\n","    ",6);
    $rc = "";
    if ($i < 5) {
        $i++;
        continue;
    }
    else {
        break;
    }
  }


  $funktionen->log_schreiben(substr($Antwort,1,-3)."  i: ".$k,"    ",6);

  if ($Wert === true and strlen($Antwort) > 130) {

    $Teile = explode(" ",substr($Antwort,1,-3));

    $aktuelleDaten["Regler_Mode"] = $Teile[0]+0;
    $aktuelleDaten["Seriennummer"] = $Teile[1]+0;
    $aktuelleDaten["Modus"] = $Teile[2];
    $aktuelleDaten["Fehler"] = $Teile[3]+0;
    $aktuelleDaten["Netzspannung"] = $Teile[4]+0;
    $aktuelleDaten["Netzfrequenz"] = $Teile[5]+0;
    $aktuelleDaten["AC_Ausgangsspannung"] = $Teile[6]+0;      
    $aktuelleDaten["AC_Ausgangsfrequenz"] = $Teile[7]+0;
    $aktuelleDaten["AC_Ausgangsscheinleistung"] = $Teile[8]+0;
    $aktuelleDaten["AC_Ausgangsleistung"] = $Teile[9]+0;
    $aktuelleDaten["AC_Ausgangslast"] = $Teile[10]+0;
    $aktuelleDaten["Batteriespannung"] = $Teile[11]+0;
    $aktuelleDaten["Batterie_Strom"] = $Teile[12]+0;
    $aktuelleDaten["SOC"] = $Teile[13]+0;
    $aktuelleDaten["Solarspannung"] = $Teile[14]+0;
    $aktuelleDaten["SolarstromGesamt"] = $Teile[15];
    $aktuelleDaten["ScheinleistungGesamt"] = $Teile[16];      
    $aktuelleDaten["AC_LeistungGesamt"] = $Teile[17]+0;
    $aktuelleDaten["Geraetestatus"] = $Teile[18];
    $aktuelleDaten["Status"] = $Teile[19]+0;
    $aktuelleDaten["Mode"] = $Teile[20]+0;
    $aktuelleDaten["DeviceStatus"] = $Teile[21]+0;
    $aktuelleDaten["Max_AmpereSet"] = $Teile[22]+0;
    $aktuelleDaten["Max_Ampere"] = $Teile[23]+0;
    $aktuelleDaten["AC_LadestromSet"] = $Teile[24];           
    $aktuelleDaten["Solarstrom"] = $Teile[25]+0;
    $aktuelleDaten["Batterieentladestrom"] = $Teile[26]+0;
    $aktuelleDaten["PV_Leistung"] = ($aktuelleDaten["Solarspannung"]*$aktuelleDaten["Solarstrom"]);


    switch($aktuelleDaten["Modus"]) {
      case "P":
        $aktuelleDaten["IntModus"] = 1;
      break;
      case "S":
        $aktuelleDaten["IntModus"] = 2;
      break;
      case "B":
        $aktuelleDaten["IntModus"] = 3;
      break;
      case "L":
        $aktuelleDaten["IntModus"] = 4;
      break;
      case "F":
        $aktuelleDaten["IntModus"] = 5;
      break;
      case "H":
        $aktuelleDaten["IntModus"] = 6;
      break;
      case "Y":
        $aktuelleDaten["IntModus"] = 7;
      break;
      case "T":
        $aktuelleDaten["IntModus"] = 8;
      break;
      case "D":
        $aktuelleDaten["IntModus"] = 9;
      break;
      case "G":
        $aktuelleDaten["IntModus"] = 10;
      break;
      case "C":
        $aktuelleDaten["IntModus"] = 11;
      break;
      default:
        $aktuelleDaten["IntModus"] = 0;
      break;
    }

    /**************************************************************************
    //  Falls ein ErrorCode vorliegt, wird er hier in einen lesbaren
    //  Text umgewandelt, sodass er als Fehlermeldung gesendet werden kann.
    //  Die Funktion ist noch nicht überall implementiert.
    **************************************************************************/
    $FehlermeldungText = "";
    if ($aktuelleDaten["Modus"] == "F") {
      $FehlermeldungText = "Der Wechselrichter meldet einen Fehler. Bitte prüfen.";
      $funktionen->log_schreiben($FehlermeldungText,"** ",1);
      $funktionen->log_schreiben($aktuelleDaten["Fehler"],"** ",1);
      if (isset($aktuelleDaten["Fehlermeldung"])) {
        $FehlermeldungText = $aktuelleDaten["Fehlermeldung"];
      }
    }
    if (isset($aktuelleDaten["Fehlermeldung"])) {
      $funktionen->log_schreiben("Fehlermeldung: ".$aktuelleDaten["Fehlermeldung"],"   ",1);
    }


    /****************************************************************************
    //  Die Daten werden für die Speicherung vorbereitet.
    ****************************************************************************/
    $aktuelleDaten["Regler"] = $Regler;
    $aktuelleDaten["Objekt"] = $Objekt;
    $aktuelleDaten["Firmware"] = 0;

    $funktionen->log_schreiben(print_r($aktuelleDaten,1),"   ",8);


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


    /****************************************************************************
    //  InfluxDB  Zugangsdaten ...stehen in der user.config.php
    //  falls nicht, sind das hier die default Werte.
    ****************************************************************************/
    $aktuelleDaten["InfluxAdresse"] = $InfluxAdresse;
    $aktuelleDaten["InfluxPort"] =  $InfluxPort;
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
    $funktionen->log_schreiben("Schleife: ".($i)." Zeitspanne: ".(floor((56 - (time() - $Start))/($Wiederholungen-$i+1))),"   ",9);
    sleep(floor((56 - (time() - $Start))/($Wiederholungen-$i+1)));
  }
  if ($Wiederholungen <= $i or $i >= 6) {
      $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
      break;
  }


  $i++;
} while (($Start + 56) > time());

stream_set_blocking ( $USB , true );


if (isset($aktuelleDaten["Modus"]) and isset($aktuelleDaten["Regler"])) {

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
//  Achtung! Dieser Wert wird jeden Tag um Mitternacht auf 0 gesetzt.
//  Leistung in Watt / 60 Minuten, da 60 mal in der Stunde addiert wird.
*****************************************************************************/
if (file_exists($StatusFile) and isset($aktuelleDaten["AC_Ausgangsleistung"])) {
  /***************************************************************************
  //  Daten einlesen ...   ( Watt * Stunden ) pro Tag = Wh
  ***************************************************************************/
  $whProTag = file_get_contents($StatusFile);
  // aktuellen Wert in die Datei schreiben:
  $whProTag = ($whProTag + ($aktuelleDaten["PV_Leistung"]/60));
  $rc = file_put_contents($StatusFile,$whProTag);
  $funktionen->log_schreiben("PV Leistung: ".$aktuelleDaten["PV_Leistung"]." Watt -  WattstundenGesamtHeute: ".round($whProTag,2),"   ",5);
}

Ausgang:

$funktionen->log_schreiben("-----------   Stop   phocos_any_grid.php    ------------------ ","|--",6);

return;



?>
#!/usr/bin/php
<?php

/*****************************************************************************
//  Solaranzeige Projekt             Copyright (C) [2015-2016]  [Ulrich Kunz]
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
//  Es dient als grafische Darstellung von MQTT Daten der Tasmota Module
//
//  Sonoff Basic
//  Sonoff TH10 oder TH16
//  Sonoff POW R2
//  Gosund SP1 Modul
//  Shelly 2.5           (ohne Sandard Dashboard)
//
//
//
*****************************************************************************/
//  Das sind die default Werte des Mosquitto Brokers, der mit auf dem 
//  Raspberry läuft. Hier nur etwas ändern, falls die Werte des Brokers 
//  geändert wurden.
$MQTTBroker = "localhost";
$MQTTPort = 1883;
$MQTTKeepAlive = 60;
//
//***************************************************************************/
$path_parts = pathinfo($argv[0]);
$Pfad = $path_parts['dirname'];

if (is_file($Pfad."/1.user.config.php") == false) {
  // Handelt es sich um ein Multi Regler System
  require($Pfad."/user.config.php");
}
require_once($Pfad."/phpinc/funktionen.inc.php");
if (!isset($funktionen))  {
  $funktionen = new funktionen();
}

$Go = true;
$Tracelevel = 7;  //  1 bis 10  10 = Debug
$aktuelleDaten = array();
$aktuelleDaten["Status"] = "Offline";
$aktuelleDaten["Period"] = "0";
$aktuelleDaten["Powerstatus"] = "0";   
$aktuelleDaten["Temperatur"] = "0";
$aktuelleDaten["Powerstatus0"] = "0";
$aktuelleDaten["Powerstatus1"] = "0";


$RemoteDaten = true;

$Version = "";
$Startzeit = time();  // Timestamp festhalten
$funktionen->log_schreiben("-------------   Start  sonoff_mqtt.php    --------------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",6);
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


$MQTTDaten = array();


$client = new Mosquitto\Client();
$client->onConnect([$funktionen,'mqtt_connect']);
$client->onDisconnect([$funktionen, 'mqtt_disconnect']);
$client->onPublish([$funktionen,'mqtt_publish']);
$client->onSubscribe([$funktionen,'mqtt_subscribe']);
$client->onMessage([$funktionen,'mqtt_message']);

if (!empty($MQTTBenutzer) and !empty($MQTTKennwort)) {
  $client->setCredentials($MQTTBenutzer, $MQTTKennwort);
}
if ($MQTTSSL) {
  $client->setTlsCertificates($Pfad."/ca.cert");
  $client->setTlsInsecure(SSL_VERIFY_NONE);
}

$rc = $client->connect($MQTTBroker, $MQTTPort, $MQTTKeepAlive);
for ($i=1;$i<200;$i++) {
  // Warten bis der connect erfolgt ist.
  if (empty($MQTTDaten)) {
    $client->loop(100);
  }
  else {
    break;
  }
}


$client->subscribe("+/".$Topic."/#", 0); // Subscribe
$client->subscribe($Topic."/#", 0); // Subscribe


$i = 1;

do {

  $funktionen->log_schreiben("Die Daten werden ausgelesen...","+  ",3);

  /****************************************************************************
  //  Ab hier wird das Sonoff Gerät ausgelesen.
  //
  //  Ergebniswerte:
  //  $aktuelleDaten["Firmware"]                Nummer
  //  $aktuelleDaten["Produkt"]                 Text
  //  $aktuelleDaten["Objekt"]                  Text
  //  $aktuelleDaten["Datum"]                   Text
  //  $aktuelleDaten["AC_Spannung"]
  //  $aktuelleDaten["AC_Strom"]
  //  $aktuelleDaten["AC_Leistung"]
  //  $aktuelleDaten["AC_Scheinleistung"]
  //  $aktuelleDaten["AC_Wirkleistung"]
  //  $aktuelleDaten["Status"]                  Text
  //  $aktuelleDaten["Powerstatus"]             0 / 1
  //  $aktuelleDaten["WattstundenGesamtHeute"]
  //  $aktuelleDaten["WattstundenGesamtGestern"]
  //  $aktuelleDaten["WattstundenGesamt"]
  //
  ****************************************************************************/
 
  do {

    $client->loop(100);

    if (isset($MQTTDaten["MQTTMessageReturnText"]) and $MQTTDaten["MQTTMessageReturnText"] == "RX-OK") {



      $funktionen->log_schreiben($MQTTDaten["MQTTConnectReturnText"],"MQT",10);

      /*************************************************************************
      //  MQTT Meldungen empfangen. Subscribing    Subscribing    Subscribing
      //  Hier werden die Daten vom Mosquitto Broker gelesen.
      *************************************************************************/
      $funktionen->log_schreiben(print_r($MQTTDaten,1),"MQT",10);
      $funktionen->log_schreiben("Nachricht: ".$MQTTDaten["MQTTNachricht"],"MQT",10);
      if (strtoupper($MQTTDaten["MQTTNachricht"]) == "ONLINE") {
        // Gerät ist online
        $aktuelleDaten["Status"] = $MQTTDaten["MQTTNachricht"];

        $funktionen->log_schreiben("Topic: ".$MQTTDaten["MQTTTopic"],"   ",7);

        $TopicTeile = explode("/",$MQTTDaten["MQTTTopic"]);
        if ($TopicTeile[0] == "tele") {
          $Prefix = 0;
          $TopicPosition = 1;
          $Payload = 2;
        }
        elseif ($TopicTeile[1] == "tele") {
          $Prefix = 1;
          $TopicPosition = 0;
          $Payload = 2;
        }
      }

      $funktionen->log_schreiben("Prefix: ".$Prefix." TopicPosition: ".$TopicPosition." Payload: ".$Payload,"MQT",9);

      $values = json_decode($MQTTDaten["MQTTNachricht"], true);

      $funktionen->log_schreiben(print_r($values,1),"+  ",9);


      if (is_array($values)) {
        if (isset($values["StatusSNS"])) {
          foreach ($values["StatusSNS"] as $k => $v) {
            $inputs[] = array("name"=>$k, "value"=>$v);

            if ($k == "Epoch") {
              // Die richtige Time Zone muss am Sonoff eingestellt sein.
              $aktuelleDaten["Timestamp"] = $v;
            }
            if ($k == "ENERGY") {

              // Faktor =  Leistung / Scheinleistung!
              $aktuelleDaten["AC_Spannung"] = $v["Voltage"];
              $aktuelleDaten["TotalStartTime"] = $v["TotalStartTime"];
              $aktuelleDaten["WattstundenGesamt"] = ($v["Total"] * 1000);
              $aktuelleDaten["WattstundenGesamtHeute"] = ($v["Today"] * 1000);
              $aktuelleDaten["WattstundenGesamtGestern"] = ($v["Yesterday"] * 1000);
              if (is_array($v["Current"])){
              //$funktionen->log_schreiben(print_r($Current,1),"   ",1);
                $funktionen->log_schreiben("Anzahl: ".count($v["Current"]),"   ",9);
                $aktuelleDaten["AC_Frequenz"] = $v["Frequency"];
                for ($l = 0; $l < count($v["Current"]); $l++) {
                  $aktuelleDaten["AC_Strom".$l] = $v["Current"][$l];
                  $Faktor = $v["Factor".$l][$l];
                  $aktuelleDaten["AC_Leistung".$l] = $v["Power"][$l];
                  $aktuelleDaten["AC_Scheinleistung".$l] = $v["ApparentPower"][$l];
                  $aktuelleDaten["AC_Blindleistung".$l] = $v["ReactivePower"][$l];
                }
              }
              else {
                $aktuelleDaten["AC_Strom"] = $v["Current"];
                $Faktor = $v["Factor"];
                $aktuelleDaten["AC_Leistung"] = $v["Power"];
                $aktuelleDaten["AC_Scheinleistung"] = $v["ApparentPower"];
                $aktuelleDaten["AC_Blindleistung"] = $v["ReactivePower"];
              }
            }
            if ($k == "SI7021") {   //TH16
              $aktuelleDaten["Temperatur"] = $v["Temperature"];
              $aktuelleDaten["Luftfeuchte"] = $v["Humidity"];
            }
            if ($k == "DS18B20") {   //TH16
              $aktuelleDaten["Temperatur"] = $v["Temperature"];
              $aktuelleDaten["Luftfeuchte"] = 0;
            }
            if ($k == "AM2301") {   //TH10
              $aktuelleDaten["Temperatur"] = $v["Temperature"];
              $aktuelleDaten["Luftfeuchte"] = $v["Humidity"];
            }
            if ($k == "TempUnit") {
              $aktuelleDaten["Masseinheit"] = $v;
            }
            if ($k == "ANALOG") {   //Shelly 2.5
              $aktuelleDaten["Temperatur"] = $v["Temperature"];
            }
          }
        }

        if (isset($values["StatusSTS"])) {
          foreach ($values["StatusSTS"] as $k => $v) {
            $inputs[] = array("name"=>$k, "value"=>$v);
            if ($k == "POWER") {
              if ($v == "ON") {
                $aktuelleDaten["Powerstatus"] = "1";
              }
              else {
                $aktuelleDaten["Powerstatus"] = "0";
              }
            }
            elseif ($k == "POWER1") {
              if ($v == "ON") {
                $aktuelleDaten["Powerstatus0"] = "1";
                $aktuelleDaten["Powerstatus"] = "1";
              }
              else {
                $aktuelleDaten["Powerstatus0"] = "0";
                $aktuelleDaten["Powerstatus"] = "0";
              }
            }
            elseif ($k == "POWER2") {
              if ($v == "ON") {
                $aktuelleDaten["Powerstatus1"] = "1";
              }
              else {
                $aktuelleDaten["Powerstatus1"] = "0";
              }
            }
          }
          $funktionen->log_schreiben("Powerstatus: ".$aktuelleDaten["Powerstatus"],"*- ",10);
          // break;
        }
        if (isset($values["StatusFWR"])) {
          foreach ($values["StatusFWR"] as $k => $v) {
            $inputs[] = array("name"=>$k, "value"=>$v);
            if ($k == "Version") {
              $aktuelleDaten["Produkt"] = $v;
            }
          }
        }
        if (isset($values["Status"])) {
          foreach ($values["Status"] as $k => $v) {
            $inputs[] = array("name"=>$k, "value"=>$v);
            if ($k == "Module") {
              $aktuelleDaten["SonoffModul"] = $v;
            }
            if ($k == "DeviceName") {
              $aktuelleDaten["DeviceName"] = $v;
            }
          }
        }
      }

      $MQTTDaten["MQTTMessageReturnText"] = "RX-NO";

      if ($Go) {
        if ($TopicPosition == 0) {
          $topic = $Topic."/cmnd/status";
        }
        else {
          $topic = "cmnd/".$Topic."/status";
        }
        $wert = "0";

        try {
          $MQTTDaten["MQTTPublishReturnCode"] = $client->publish($topic, $wert, 0, false);
          $funktionen->log_schreiben("Befehl gesendet: ".$topic." Wert: ".$wert,"MQT",8);
        }
        catch(Mosquitto\Exception $e){
          $funktionen->log_schreiben($topic." rc: ".$e->getMessage(),"MQT",1);
        }
        $Go = false;
      }
    }

  } while (($Startzeit + 8) > time());


  /****************************************************************************
  //  ENDE SONOFF MODUL AUSLESEN         ENDE SONOFF MODUL AUSLESEN
  ****************************************************************************/
  if (!isset($aktuelleDaten["SonoffModul"])) {
    $funktionen->log_schreiben("Keine Daten vom Sonoff Modul empfangen.","   ",6);
    goto Ausgang;
  }

  $funktionen->log_schreiben("SonoffModul: ".$aktuelleDaten["SonoffModul"],"   ",8);
  $funktionen->log_schreiben("Firmware: ".$aktuelleDaten["Produkt"],"   ",8);
  switch($aktuelleDaten["SonoffModul"]) {
    case 0:
      if ($aktuelleDaten["DeviceName"] == "shelly") {
        // Es handelt sich nicht um einen Shelly 2.5
        $funktionen->log_schreiben("Es handelt sich um ein Shelly 2.5 Modul Nr.: ".$aktuelleDaten["SonoffModul"],"   ",5);
        $aktuelleDaten["SonoffModul"] = 200; // Dummy Nummer
      }
      else {
        $funktionen->log_schreiben("Das Relais ist ein Shelly 2.5, SP111 oder ein SP211. Tasmota Modul Nr: ".$aktuelleDaten["SonoffModul"],"   ",5);
      }
    break;
    case 1:
      // Es handelt sich nicht um einen Sonoff Basic
      $funktionen->log_schreiben("Es handelt sich um ein Sonoff Basic Modul Nr.: ".$aktuelleDaten["SonoffModul"],"   ",5);
    break;
    case 4:
      // Es handelt sich nicht um einen Sonoff TH10 oder TH16
      $funktionen->log_schreiben("Es handelt sich um ein Sonoff TH10 / TH16 Modul Nr.: ".$aktuelleDaten["SonoffModul"],"   ",5);
    break;
    case 43:
      // Es handelt sich nicht um einen Sonoff POW R2
      $funktionen->log_schreiben("Es handelt sich um ein Sonoff POW R2 Modul Nr.: ".$aktuelleDaten["SonoffModul"],"   ",5);
    break;
    case 55:
      // Es handelt sich nicht um Gosund SP1 Modul
      $funktionen->log_schreiben("Es handelt sich um ein GOSUND SP1 Modul Nr.: ".$aktuelleDaten["SonoffModul"],"   ",5);
    break;
    default:
      $funktionen->log_schreiben("Das Sonoff Relais ist nicht aktiv oder es ist kein unterstütztes Sonoff Gerät. Tasmota Modul: ".$aktuelleDaten["SonoffModul"],"   ",5);
      goto Ausgang;
    break;
  }


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
  $aktuelleDaten["Firmware"] = 0;
  //  Dummy für HomeMatic
  if (isset($aktuelleDaten["AC_Spannung"]))      {
    $aktuelleDaten["AC_Ausgangsspannung"] = $aktuelleDaten["AC_Spannung"];
  }
  else {
    // Dummy für Statistik
    $aktuelleDaten["WattstundenGesamtHeute"] = 0;
  }

  /**************************************************************************
  //  Alle ausgelesenen Daten werden hier bei Bedarf als mqtt Messages
  //  an den mqtt-Broker Mosquitto gesendet.
  //  Achtung! Die Übertragung dauert ca. 30 Sekunden!
  **************************************************************************/
  if ($MQTT) {
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
  $aktuelleDaten["InfluxSSL"] = $InfluxSSL;
  $aktuelleDaten["Demodaten"] = false;



  $funktionen->log_schreiben(print_r($aktuelleDaten,1),"*- ",10);


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
    $Zeitspanne = (9 - (time() - $Startzeit));
    $funktionen->log_schreiben("Multi-Regler-Ausgang. ".$Zeitspanne,"   ",2);
    if ($Zeitspanne > 0) {
      sleep($Zeitspanne);
    }
    break;
  }
  else {
    $funktionen->log_schreiben("Schleife: ".($i)." Zeitspanne: ".(floor((56 - (time() - $Startzeit))/($Wiederholungen-$i+1))),"   ",9);
    sleep(floor((56 - (time() - $Startzeit))/($Wiederholungen-$i+1)));
  }
  if ($Wiederholungen <= $i or $i >= 6) {
    $funktionen->log_schreiben("OK. Daten gelesen.","   ",9);
    $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
    break;
  }


  $i++;
} while (($Startzeit + 54) > time());


Ausgang:


if (isset($aktuelleDaten["Firmware"]) and isset($aktuelleDaten["Regler"])) {


  /*********************************************************************
  //  Jede Minute werden bei Bedarf einige Werte zur Homematic Zentrale
  //  übertragen.
  *********************************************************************/
  if (isset($Homematic) and $Homematic == true) {
    // $aktuelleDaten["Solarspannung"] = $aktuelleDaten["Solarspannung1"];
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



$funktionen->log_schreiben("-------------   Stop   sonoff_mqtt.php     -------------------- ","|--",6);

return;




?>

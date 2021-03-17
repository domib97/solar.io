<?php
/******************************************************************************
//  Hier werden die Kunden spezifischen Einstellungen         (c) U.Kunz  2016
//  vorgenommen, damit keine der Daten bei einem Softwareupdate
//  überspielt werden. Diese Datei bitte mit Vorsicht
//  ändern. Die Form und die Schreibweise darf in keinen Fällen
//  geändert werden. Weitere Hilfe finden Sie im FORUM   www.solaranzeige.de
//
//  Diese Datei ist hier zu finden:  /var/www/html/user.config.php
//
//
******************************************************************************/
//
/******************************************************************************
//  REGLER  und  WECHSELRICHTER       REGLER  und  WECHSELRICHTER      REGLER
******************************************************************************/
//  Diese Regler knnen derzeit mit der Software benutzt werden:
//
//  Welcher Regler wird benutzt?
//
//   1 = ivt-Hirschau Regler SCPlus oder SCDPlus
//
//   2 = Steca Regler Tarom 6000 und Tarom 4545
//
//   3 = Regler der Tracer Serie  z.B. Tracer2210A
//       mit RS-485 zu USB Anschlusskabel (MODBUS) (Unbedingt FTDI Chipsatz)
//
//   4 = BlueSolar oder SmartSolar Regler von Victron-energy mit
//       VE.Direkt zu USB Anschlusskabel
//       --------------------------------------------------------------------
//       [Im Moment noch nicht in Betrieb]
//       Zusätzlich kann ein MultiPlus Wechselrichter mit einem MK3 USB Kabel
//       angeschlossen werden.
//
//   5 = Micro Wechselrichter von AEconversion z.B. INV250-45
//       mit RS-485 zu USB Anschlusskabel   (Unbedingt FTDI Chipsatz)
//
//   6 = Victron BMV 7xx Batteriewächter
//       mit VE.direct zu USB Kabel
//
//   7 = Voltronic Geräte der Axpert Serie
//       Effekta Geräte der AX und HX Serie,
//       Steca Solarix PLI 5000-48,
//       InfiniSolar PIP Serie 3k,
//       MPPSolar PIP HSP/MSP , MS/MSX und MSD Serien
//       EAsun ISolar SPV SMV 1KVA-5KVA Inverter
//       mit einfachem  USB Kabel (A und B Stecker)
//       (Kein Seriell zu USB Wandler!)
//
//   8 = InfiniSolar V Serie , MPP Solar HSE/MSE Serie und viele Baugleiche.
//       mit einfachem USB Kabel (A und B Stecker) (Kein Seriell zu USB Wandler!)
//
//   9 = MPPSolar MPI Hybrid Serie 3 Phasen Inverter
//       Baugleich: FSP 5,5 Hybrid und Panta 10 Hybrid
//       mit einfachem USB Kabel (A und B Stecker) (Kein Seriell zu USB Wandler!)
//
//  10 = SolarMax S-Serie
//       mit Ethernet (LAN) Kabel Anschluss.
//
//  11 = Phoenix Wechselrichter von Victron mit VE.direct Kabel Anschluss
//
//  12 = Fronius Symo Wechselrichter  inkl. Hybrid Geräte und Fronius Prymo
//       MIT Ethernet (LAN) Kabel Anschluss
//
//  13 = Joulie-16  Batterie-Management-System von AutarcTech  (BMS)
//       Mit LAN Anschluss
//
//  14 = Rover Laderegler von Renogy, Toyo von SRNE und baugleiche
//       mit USB Kabel Anschluss
//
//  15 = PYLONTECH US2000B Plus Batteriespeicher  Batterie-Management-System (BMS)
//       Mit Seriell zu USB Adapter. Den Console Port im Gerät benutzen.
//
//  16 = SolarEdge 3 Phasen Wechselrichter mit LAN Schnittstelle
//       (Für 1 Phasen Geräte muss das Dashboard geändert werden)
//
//  17 = KOSTAL Plenticore Wechselrichter und Pico der 3. Generation
//       mit LAN Schnittstelle
//
//  18 = S10E und S10 mini von E3/DC mit LAN Schnittstelle   (Port 502)
//       Nur im Simple-Mode möglich.
//
//  19 = eSmart3  Laderegler  40A, 50A, 60A
//
//  20 = SolarEdge 3 Phasen Wechselrichter ohne MODBUS Zähler
//       mit Ethernet (LAN) Kabel Anschluss.
//       (Für 1 Phasen Geräte muss das Dashboard geändert werden)
//
//  21 = KOSTAL Piko mit RS485 Anschluss
//
//  22 = Smart Energy Meter von KOSTAL oder Anderen
//
//  23 = Sonoff / Shelly:  POW R2 , TH10 oder TH16 R2 oder GOSUND SP1 oder Shelly 2.5,
//       alle mit Tasmota Firmware.
//
//  24 = Infini xx KW Hybrid Wechselrichter. Protokoll 16  1 Phase
//
//  25 = SonnenBatterie mit LAN Anschluss
//
//  26 = MPPSolar 5048MK und 5048GK ( PIP MK und GK Serie )
//       EAsun ISolar V III Off-Grid lnverter
//       sowie Baugleiche mit USB Anschluss
//
//  27 = SMA Wechselrichter Sunny Island und Sunny Tripower 
//       Modbus TCP mit LAN Anschluss
//
//  28 = HRDi marlec Laderegler für PV und Windgenerator
//       mit Seriell - USB Adapter
//
//  29 = go-e Charger  (Wallbox)
//
//  30 = Keba Wallbox  P20 + P30
//
//  31 = Shelly 3EM
//
//  32 = KACO Wechselrichter der TL3 Serie
//
//  33 = Labornetzteil JOY-IT  JT-DPM8624
//
//  34 = SDM630  Energy Meter (RS485 Anschluss)
//
//  35 = Wallbe Wallbox  Eco 2.0 und andere.
//
//  36 = Delta Wechselrichter SI 2500  mit RS485 Anschluss
//
//  37 = Simple EVSE WiFi Wallbox
//
//  38 = ALPHA ESS T10  Wechselrichter + Batteriesystem
//
//  39 = openWB  Wallbox
//
//  40 = Phocos PH1800 Wechselrichter
//
//  41 = PylonTech US 3000 A mit RS485 Schnittstelle  Anz. Batterie-Packs auch angeben!
//
//  42 = PV18-3KW VHM oder PV1800 VHM oder Baugleiche mit RS485 Schnittstelle
//
//  43 = Senec Stromspeicher
//
//  44 = Webasto Wallbox
//
//  45 = Phocos Any-Grid mit RS485 Schnittstelle
//
//  46 = Huawei SUN2000 Wechselrichter
//
//  47 = Phoenix Contact Wallbox
//
//  48 = Growatt Wechselrichter
//
//  49 = Huawei SmartLogger
//  ---------------------------------------------------------------------------
//
$Regler = "0";
//
/******************************************************************************
//  Raspberry Gerätenummer   Raspberry Gerätenummer   Raspberry Gerätenummer
//  Falls mehr als ein Gerät pro Raspberry betrieben wird.
//  Es ist die Reihenfolge der Geräte und taucht auch in der Nummerierung
//  der  x.user.config.php Dateien auf
******************************************************************************/
//  Bitte nur bei einer Multi-Regler-Version ändern.  [ 1 bis 6 ]
$GeraeteNummer = "1";
//
//  Nur bei einem Micro Wechselrichter von AEconversion ($Regler = "5")
//  -------------------------------------------------------------------
//  Z.B. Typ INV250-45 oder INV500-60
//  Steht auf dem Gerät! Ist 10 stellig. Serial-No. 0607600...
//  Bitte alle 10 Stellen hier eintragen:
$Seriennummer = "0000000000";
//  Falls ein WLAN HF2211 serial   Gateway benutzt wird true eingeben
$HF2211 = false;
//
//  Nur bei PylonTech BMS US3000A       ($Regler = "41" )
//  und den neuen US2000 aus dem Jahr 2019 und später
//  Anzahl der vorhandenen Batteriepacks
//  -------------------------------------------------------------------
$Batteriepacks = "1";//                                Regler = "41"
//
//
//  Ethernet Kabelverbindung:          Local Area Network  (LAN)
//  Alle Geräte, die über das LAN angesprochen und ausgelesen werden,
//  oder ein Serial Device Server, wie z.B. der HF2211 oder der Elfin-EW11,
//  dazwischen geschaltet haben, bitte hier IP und Port eintragen und
//  falls erforderlich die Device ID. (Geräteadresse = WR_Adresse)
//  Die Geräte Adresse wird auch manchmal bei RS485 Verbindungen benutzt.
//  -------------------------------------------------------------------
//  Bitte die Daten aus dem Gerät übernehmen
//
$WR_IP = "0.0.0.0";    //  Keine führenden Nullen!  67.xx Ja!, 067.xx Nein!
$WR_Port = "12345";
$WR_Adresse = "1";
//
/*****************************************************************************/
//
//
//  Bezeichnung des Objektes. Freie Wahl, maximal 15 Buchstaben.
$Objekt = "";
//
//
/******************************************************************************
//  InfluxDB     InfluxDB     InfluxDB     InfluxDB     InfluxDB     InfluxDB
//  ***************************************************************************
//  Die Daten können jede Minute oder öfter an eine InfluxDB Datenbank
//  übertragen werden. Die Datenbank muss nur über das Netzwerk erreichbar
//  sein. Sie kann sich im lokalen Netz, im Intenet oder aber auch auf diesem
//  Raspberry befinden. Bitte lesen Sie auch das Dokument
//  "Solaranzeige + InfluxDB" welches Sie auf unserem Support Server finden.
******************************************************************************/
//  Sollen die Daten in die lokale Influx Datenbank geschrieben werden?
//  Für die lokale Datenbank sind keine weiteren Angaben nötig.
//  true oder false
$InfluxDB_local = true;
//
//  Name der lokalen Datenbank. Bitte nicht ändern, sonst funktionieren die
//  Standard Dashboards nicht!
//  ---  Nur bei Multi-Regler-Version  Nur bei Multi-Regler-Version  ----
//  Bei einer Muti-Regler-Version müssen hier unterschiedliche lokale
//  Datenbanknamen eingetragen werden. Mit gleichem Namen müssen die Datenbanken
//  in der InfluxDB angelegt werden. Siehe Dokument:
//  "Multi-Regler-Version Installation"
$InfluxDBLokal  = "solaranzeige";
//
//  Wie oft pro Minute sollen die Daten ausgelesen und zur InfluxDB
//  übertragen werden?
//  Gültige Werte sind 1 bis 6 (6 = alle 10 Sekunden)
//  Bei einer zusätzlichen entfernten Datenbank kann das zu erheblichen
//  Traffic führen! Dieses gilt nur für die Single-Geräte-Version!
//  Wie es bei der Multi-Regler-Version funktioniert bitte in dem
//  entsprechenden Dokument nachlesen.
//  Default ist 1 (Ein mal pro Minute)
$Wiederholungen = 1;
//
/****************************************************************************/
//  ENTFERNTE INFLUX DATENBANK:
//  ---------------------------
//  Ist eine entfernte InfluxDB vorhanden und sollen dorthin auch die Daten
//  übertragen werden?
//  true oder false
$InfluxDB_remote = false;
//
//  Port an den die Daten geschickt werden. Normal ist Port 8086
$InfluxPort = 8086;
//
//  Name der entfernten Datenbank eintragen
//  Beispiel:  "solaranzeige" oder "MeineDatenbank"
$InfluxDBName  = "solaranzeige";
//
//  Adresse der Datenbank
//  Entweder die IP Adresse "xxx.xxx.xxx.xxx" oder den Hostnamen oder "localhost"
//  eintragen.
//  Beispiel:  "db.solaranzeige.de" oder "34.101.3.20"
$InfluxAdresse = "";
//
//  Wenn man mit UserID und Kennwort die Daten übertragen möchte, sollte man
//  auf jeden Fall auch die SSL Verschlüsselung einschalten. Dazu muss die
//  Influx Datenbank aber erst auf https eingerichtet werden.
$InfluxSSL = false;
//
//  Wenn die entfernte Datenbank mit UserID und Kennwort geschützt ist.
//  Wenn nicht, bitte leer lassen.
$InfluxUser = "";
$InfluxPassword ="";
//
//  Sollen die Daten nur bei Tageslicht an eine remote Datenbank gesendet werden?
//  Das reduziert den Traffic bei teuren Leitungen. Das betrifft nur die Remote
//  Datenbank falls konfiguriert.
//  true / false     ( false = die Daten werden rund um die Uhr gesendet. )
$InfluxDaylight = false;
//
//
//
/******************************************************************************
//  HOMEMATIC  ANBINDUNG      HOMEMATIC  ANBINDUNG      HOMEMATIC  ANBINDUNG
//  ***************************************************************************
//  Anbindung an eine vorhandene HomeMatic Zentrale
//  Funktioniert nur mit den Reglern von Victron (BlueSolar und SmartSolar)
//  Falls Bedarf für andere Regler besteht, bitte melden.
//  Für die genaue Einrichtung bitte das PDF Dokument "Homematic_Anschluss.pdf" lesen.
//  Es befindet sich auf unserem Support Server im Bereich "Verschiedene PDF Dokumente"
********************************************************************************/
//  Sollen die Daten an eine vorhandene Homematic Zentrale gesendet werden?
//  Diese Werte kann dann die Zentrale dann verarbeiten.
//  Folgende Werte werden übertragen:
//  * Ladestatus 0 = Keine Ladung, 2 = Fehler, 3 = Ladung (bulk); 4 = Nachladung (absorbtion),
//               5 = Erhaltungsladung (float)
//  * Ladestatus als Textzeile (Keine_Ladung, Normale_Ladung, Nachladung, Erhaltungsladung, Fehler)
//  * Batteriespannung in Volt
//  * Erzeugte Leistung am Tage in kWh
//  * Aktuell erzeugte Solar-Leistung
//  * Batteriestatus in % (Wie voll ist die Batterie?) Nicht bei allen Geräten!
//
//  true / false
$Homematic = false;
//
//  Welche IP Adresse hat Ihre Homematic Zentrale? Sie muss sich im selben
//  Netzwerk wie der Raspberry Pi befinden. Beispiel: 192.168.33.200
$Homematic_IP = "xxx.xxx.xxx.xxx";
//
//  Hier die Variablen eintragen, die zur HomeMatic Zentrale übermittelt werden
//  sollen. Siehe Dokument "HomeMatic_Anbindung.pdf"
//  Beispiel: "BatterieLadestatus,BatteriestatusText,Batteriespannung,Solarleistung,SolarleistungTag,Solarspannung";
$HomeMaticVar = "";
//
//  Den Status einzelner Geräte aus der HomeMatic Zentrale auslesen und in die
//  Influx Datenbank schreiben, damit man den Status im Dashboard anzeigen kann.
//  Nähere Einzelheiten stehen im Dokument "HomeMatic Anbindung"
$HM_auslesen = false;
//
//  Für jedes Gerät, dessen Status ausgelesen werden soll, müssen 4 Variablen
//  angegeben werden.
//  $HM[0]["Variable"] =       Kann man nennen wie man will, steht dann so in der Influx Datenbank.
//  $HM[0]["Interface"] =      Steht in der HomeMatic, bitte übernehmen
//  $HM[0]["Seriennummer"] =   Steht auch in der HomeMatic
//  $HM[0]["Datenpunkt"] =     STATE, POWER, ACTUAL_TEMPERATURE usw. Siehe HomeMatic
//
//  Für jede Systemvariable müssen 2 Variablen angegeben werden:
//  $HM[0]["Variable"] =        Kann man nennen wie man will. Steht dann so in der Influx Datenbank
//  $HM[0]["Systemvariable"] =  Name der Systemvariable in der HomeMatic
//  -----------------------------------------------------------------------
//
//  Beispiele:  ( Die zwei Schrägstich bei Aktivierung bitte entfernen. )
//  $HM[0]["Variable"] = "Wasserboiler";
//  $HM[0]["Interface"] = "BidCos-RF";
//  $HM[0]["Seriennummer"] = "OEQ1150699:1";
//  $HM[0]["Datenpunkt"] = "STATE";
//  $HM[1]["Variable"] = "Heizluefter";
//  $HM[1]["Interface"] = "BidCos-RF";
//  $HM[1]["Seriennummer"] = "OEQ1399311:1";
//  $HM[1]["Datenpunkt"] = "STATE";
//  $HM[2]["Variable"] = "...";
//  $HM[2]["Interface"] = "...";
//  $HM[2]["Seriennummer"] = "...";
//  $HM[2]["Datenpunkt"] = "POWER";
//  $HM[3]["Variable"] = "Anwesenheit";
//  $HM[3]["Systemvariable"] = "Anwesenheit";
//  usw.
//
//
//
/******************************************************************************
//  MQTT Protokoll     MQTT Protokoll      MQTT Protokoll      MQTT Protokoll
//  Wenn Daten mit dem MQTT Protokoll versendet werden sollen. Hat nichts
//  direkt mit den Sonoff Geräten zu tun.
//
******************************************************************************/
//  Sollen alle ausgelesenen Daten mit dem MQTT Protokoll an einen
//  MQTT-Broker gesendet werden? Bitte das Solaranzeige-MQTT PDF Dokument lesen
$MQTT = false;
//
//  Wo ist der MQTT-Broker zu finden?
//  Entweder "localhost", eine Domain oder IP Adresse "xxx.xxx.xxx.xxx" eintragen.
//  broker.hivemq.com ist ein Test Broker   Siehe http://www.mqtt-dashboard.com/
$MQTTBroker = "localhost";
//
//  Benutzter Port des Brokers. Normal ist 1883  mit SSL 8883
$MQTTPort = 1883;
//
//  Falls der Broker gesichert ist. Sonst bitte leer lassen.
$MQTTBenutzer = "";
$MQTTKennwort = "";
//
//  Wenn man die Daten mit SSL Verschlüsselung versenden möchte.
//  Wenn hier true steht, muss im Verzeichnis "/var/www/html/" die "cerfile"
//  'ca.crt' vorhanden sein. Nähere Einzelheiten über diese Datei findet
//  man im Internet in der Mosquitto Dokumentation.
$MQTTSSL = false;
//
//  Timeout der Übertragung zum Broker. Normal = 10 bis 60 Sekunden
$MQTTKeepAlive = 60;
//
//  Topic Name oder Nummer des Gerätes solaranzeige/1
//  oder solaranzeige/box1                     (solaranzeige ist fest vorgegeben.)
//  Man kann das Gerät nennen wie man will, nur jedes Gerät, welches Daten
//  senden soll unterschiedlich. Entwerder 1 bis 6 oder Namen Ihrer Wahl vergeben.
$MQTTGeraet = "box1";
//
//  Welche Daten sollen als MQTT Message übertragen werden? Wenn hier nichts
//  aufgeführt ist, werden alle ausgelesenen Daten übertragen.
//  Bitte darauf achten, dass keine Leerstellen zwischen den Variablen sind.
//  Die einzelnen Variablen müssen mit einem Komma getrennt und klein geschrieben
//  werden. Zusätzlich müssen sie den Eintrag vom $MQTTGeraet und ein Schrägstrich
//  enthalten. Das ist nötig, da mehrere Geräte an dem raspberry hängen können.
//  Beispiel mit obigen MQTTGeraet:
//  $MQTTAuswahl = "1/ladestatus,1/solarspannung,1/solarstrom"
//  Werden hier Variablen eingetragen, dann werden auch nur diese Topics
//  übertragen.
$MQTTAuswahl = "";
//
//
/******************************************************************************
//  MQTT Empfang       MQTT Empfang       MQTT Empfang       MQTT Empfang
//  Subscribing    Subscribing    Subscribing    Subscribing    Subscribing
******************************************************************************/
//  Welche Daten sollen empfangen werden. Hier können die Topics, die
//  empfangen werden sollen aufgeführt werden. Dabei gibt es 2 Möglichkeiten
//  Entweder ein einzelner Wert oder eine Reihe von Werten.
//  Wichtig! Das basis Topics ist immer solaranzeige. Dann muss entweder befehl
//  oder anzeige kommen, dann die Gerätenummer und dann die Bezeichnung des
//  Wertes. Die Gerätenummer ist immer 1, außer bei Multi-Regler-Versionen.
//  Beispiel:  solaranzeige/anzeige/1/PV-Spannung
//  In diesem Beispiel wird der Wert der PV-Spannung in die Influx Datenbank
//  geschrieben unter dem Measurement MQTT
//  oder
//  Beispiel:  solaranzeige/befehl/1/POP  mit Wert 00
//  Der Befehl POP00 wird zum Wechselrichter geschickt. Er wird jedoch nur
//  ausgeführt wenn es sich um einen erlaubten Befehl handelt, der in der
//  Datei "befehle.ini.php" enthalten ist.
//
//  Beispiele:
//  $MQTTTopic[1] = "solaranzeige/befehl/1/POP";
//  $MQTTTopic[2] = "solaranzeige/befehl/1/PCP";
//  $MQTTTopic[3] = "solaranzeige/anzeige/1/Wasserboiler";
//
//  Oder auch
//  $MQTTTopic[1] = "solaranzeige/befehl/1/#";
//  Es können so viele Topics wie benötigt aufgeführt werden. Sie müssen nur
//  durch nummeriert werden [1] bis [n]
//  Bei Multi-Regler-Versionen muss zusätzlich noch die Gerätenummer angegeben
//  werden. Weitere Informationen finden Sie auf dem Support Forum.
$MQTTTopic[1] = "solaranzeige/befehl/1/#";
//
//
/******************************************************************************
//  SONOFF Geräte mit Tasmota Firmware       SONOFF Geräte mit Tasmota Firmware
//  POW R2 / TH10 R2 oder TH16 R2  oder GOSUND SP1xx
******************************************************************************/
//  Bitte den Topic-Namen, der in der TASMOTA Firmware angegeben ist, hier
//  eintragen. Unbedingt auf Groß- und Keinschreibung achten! Der Name kann
//  frei gewählt werden, er muss nur im Gerät und hier gleich sein. Werden
//  mehrere Sonoff Geräte mit der Solaranzeige betrieben, muss jedes einzelne
//  Gerät einen anderen Topic-Namen benutzen!
$Topic = "sonoff";
//
//
/******************************************************************************
//  WETTERDATEN     WETTERDATEN    WETTERDATEN    WETTERDATEN    WETTERDATEN
******************************************************************************/
//  Die Wetterdaten werden vom Server openweathermap.org geholt, da von dort
//  die Informationen kostenlos sind.
//  Man muss sich jedoch auf dem Server anmelden, um eine APP ID zu bekommen.
//
//  Bei einer Multi-Regler-Version nur in der 1.user.config.php aktivieren!
//  Sollen die aktuellen Wetterdaten geholt und abgespeichert werden?
//  Dadurch wird mehr Traffic generiert. Die Daten stehen dann in der Influx
//  Datenbank "aktuellesWetter" unter dem Measurement "Wetter" zur Verfügung.
//  Sie werden alle 30 Minuten aktualisiert
//  true oder false
$Wetterdaten = false;
//
//  Die Application ID bekommt man, wenn man sich auf dem Server
//  www.openweathermap.org registriert. Sie hat 32 Stellen und muss hier
//  eingetragen werden. Beispiel: "57b78415a343540e3a4e4f72751c90f9"
$APPID = "";
//
//  Der Standort wird mit einer StandortID angegeben. Wie die StandortID
//  ermittelt wird, bitte im Support Forum nachlesen. Man kann eine Liste
//  aller Standort ID's Weltweit hier herunterladen:
//  http://bulk.openweathermap.org/sample/city.list.json.gz
//  Default = "2925533" Frankfurt am Main oder die ID Ihres Standortes.
$StandortID = "2925533";
//
//
/******************************************************************************
//  PROGNOSEDATEN     PROGNOSEDATEN    PROGNOSEDATEN    PROGNOSEDATEN
******************************************************************************/
//  Die Wetterprognosedaten werden vom Server www.solarprognose.de geholt.
//  Teilweise sind die Daten dort kostenlos. [ www.solarprognose.de ]
//  Man muss sich jedoch auf dem Server anmelden, um eine Prognose ID zu bekommen.
//
//  Sollen die aktuellen Prognosedaten geholt und abgespeichert werden?
//  Die Daten stehen dann in der Influx Datenbank "solaranzeige" unter dem
//  Measurement "Wetterprognose" zur Verfügung. Sie werden pro Stunde einmal
//  aktualisiert.
//  Möchte man seinen eigenen Prognose Script nutzen, dann bitte hier User eingeben.
//  In diesem Fall wird alle 30 Minuten der Script "prognose.php" aufgerufen.
//  Dort müssen die Funktionen hinterlegt sein.
//  keine, API, User, beide
$Prognosedaten = "keine";              //  "keine" , "API" , "User" , "beide"
//
//  Wenn API eingetragen wird, dann folgende 3 Variablen füllen:
$AccessToken = "";                     // Bekommt man bei www.solarprognose.de
$PrognoseItem = "inverter";            // plant, inverter
$PrognoseID = "0";                     // Anlagen ID oder Wechselrichter ID
$Algorithmus = "";                     // kann leer bleiben oder
//                                     // mosmix | own-v1 | clearsky
//
/******************************************************************************
//  MESSENGER   MELDUNGEN        MESSENGER   MELDUNGEN        MESSENGER
******************************************************************************/
//  Es können Fehlermeldungen, Ereignisse oder Statistiken mit einem
//  Messenger übertragen werden. Dazu bitte Messenger = true eintragen
//  Genaue Informationen stehen im Dokument "Nachrichten_senden.pdf" sobald
//  diese Funktion freigeschaltet ist. (Voraussichtlich Anfang Dezember 2018)
//  (Diese Funktion ist noch nicht in Betrieb! Bitte nicht verändern)
//  true / false
$Messenger = false;
//
//  Die Solaranzeige müssen Sie bei Pushover registrieren und einen API Token
//  holen. Wie das geht, steht in dem Dokument "Messenger Nachrichten" auf dem
//  Support Server
//  Beispiel $API_Token = "amk4be851bcegnirhu1b71u6ou7uoh";
$API_Token = "";
//
//  Der User_Key ist die Pushover Empfänger Adresse. Es können bis zu
//  9 Empfänger angegeben werden. $User_Key[1]  bis  $User_Key[9]
//  Am Ende jeder Zeile das Semikolon nicht vergessen!
//  Beispiel: $User_Key[1] = "ub6c3wmw4a3idwk9b5ajgfs5a7aypt";
$User_Key[1] = "";
//  $User_Key[2] = "";
//  $User_Key[3] = "";
//
//*****************************************************************************
//  Sonnen Auf und Untergang:
//  Standort für Frankfurt. Wer es etwas genauer haben möchte, hier den eigenen
//  Standort eintragen. Bitte als Dezimalzahl wie hier vorgegeben!
$Breitengrad = 50.1143999;
$Laengengrad = 8.6585178;
//
//
/******************************************************************************
//  aWATTar Börsenpreise      aWATTar Börsenpreise      aWATTar Börsenpreise.
//
//  Sollen die aktuellen Strom Börsenpreise in die oben angegebene locale.
//  Datenbank in das Measurement "awattarPreise" geschrieben werden?
******************************************************************************/
//
$aWATTar = false;
//
$Aufschlag = "0";       // Z.B.  "20,6"        Preis des Aufschlages in Cent
//
//
/******************************************************************************
//  ACHTUNG!   ACHTUNG!   ACHTUNG!   ACHTUNG!   ACHTUNG!   ACHTUNG!   ACHTUNG!
//
//  Alles ab hier nicht ändern! Nur auf Anweisung. Änderungen hier können
//  das System zum Absturz bringen.
/******************************************************************************
//  USB Device      USB Device      USB Device      USB Device      USB Device
******************************************************************************/
//
//  USB Device, die automatisch erkannt wurde...  bitte nicht ändern
//  Wird nicht bei der Multi-Regler-Version benötigt.
//
$USBRegler         = "/dev/ttyUSB0";
$USBWechselrichter = "/dev/ttyUSB1";
//
//  Nur wenn die automatischer Erkennung nicht funktioniert hat, bitte manuell
//  eintragen. Im Normalfall wird das nicht benötigt. So lassen wie es ist.
//  ---  Nur bei Multi-Regler-Version  Nur bei Multi-Regler-Version  ----
//  Bei einer Multi-Regler-Version muss hier der Devicename manuell
//  eingetragen werden.
//
$USBDevice = "";
/*****************************************************************************/
//
/******************************************************************************
//  Raspberry Pi   Hardware   Raspberry Pi   Hardware   Raspberry Pi   Hardware
******************************************************************************/
// Bitte nicht ändern, wird automatisch ermittelt.
//
$Platine = "Raspberry unbekannt";
//
/******************************************************************************
//  PHP Error Reporting        PHP Error Reporting        PHP Error Reporting
//  Bei ungeklärten Problemen hier einschalten. Normal = ausgeschaltet
******************************************************************************/
// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE | E_STRICT);
//
//  Ist für die neue Datenbankstruktur des Alpha ESS Wechselrichters
//  Mit 0 kann die alte Struktur eingeschaltet werden.
$Alpha_ESS = 1;
// ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE ENDE
?>

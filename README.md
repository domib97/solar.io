# solar.io

[![Join the chat at https://gitter.im/solar-io/community](https://badges.gitter.im/solar-io/community.svg)](https://gitter.im/solar-io/community?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge) [![GPLv3 license](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://opensource.org/licenses/GPL-3.0) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](https://github.com/domib97/solar.io/pulls) ![GitHub pull requests](https://img.shields.io/github/issues-pr/domib97/solar.io?style=flat-square)
![GitHub issues](https://img.shields.io/github/issues/domib97/solar.io?style=flat-square)

--------------------------------------------------------------------------------------------------------------------

### -> Smart Home Photovoltaik / ( Multi- ) Inverter RJ45 MPPT / Logger with influxDB and grafana

### -> to run on: docker (*thanks to* **@DeBaschdi**), arm32/64 like Pi4 (Pi OS Lite, Debian Buster), Synology (NAS), UnRaid, OpenMediaVault

### -> orginally created by *@Ulrich* and only distributed on his website: https://solaranzeige.de/phpBB3/solaranzeige.php

### -> goal of this repo: a leight-weight, fully documented, GPLv3 fork of "solaranzeige"

--------------------------------------------------------------------------------------------------------------------

### To Do's :

- [x] *port solaranzeige.de to docker: thanks to* **@DeBaschdi** : https://github.com/DeBaschdi/docker.solaranzeige
- [x] *make it GPLv3 compliant*
- [ ] port solaranzeige.de to Pi OS Lite -> WIP
- [ ] finish debloating solaranzeige.de
- [ ] complete english doc's
- [ ] pull request docker.solaranzeige
- [ ] openHAB integration : https://www.openhab.org
- [ ] add new features like alternitive dashboards : https://github.com/Tafkas/solarpi
- [ ] add fancy animations : https://github.com/reptilex/tesla-style-solar-power-card
- [ ] native weather forecast and pv-energy-prediction
- [ ] make the predictions more precise : https://github.com/ColasGael/Machine-Learning-for-Solar-Energy-Prediction
- [ ] ?(python port)? : https://github.com/basking-in-the-sun2000/solar-logger

**If anyone can help with anything, feel free to contribute and make a pull request!**

--------------------------------------------------------------------------------------------------------------------

### List of currently supported devices:
- IVT controller SCplus and SCDplus
- Steca TAROM 6000 and TAROM 4545 / Solarix PLI 5000-48
- Tracer series controllers
- Victron BlueSolar and SmartSolar controllers , Phoenix inverters
- AEconversion INV inverter
- Effekta AX series
- Voltronic Axpert series
- InfiniSolar PIP series, V series
- MPPSolar PIP HSE / MSE series, PIP-MS/MSX series, MIP Hybrid 3 phases 10kW,
- MPPSolar 2424-msd with 2 x MPPT, 2424-msd with 2 x MPPT, 5048MK and 5048GK
- SolarMax S series and MT series
- Fronius Symo inverter 3 phases and others
- AutarcTech Joulie-16 BMS battery management system
- Rover series from Renogy (MPPT charge controller)
- US2000B battery management system
- SolarEdge inverters
- KOSTAL Plenticore and Pico series, Pico series with RS485 connection
- FSP Solar PowerManager Hybrid series
- S10E and S10 mini from E3 / DC
- eSmart3 charge controller
- Toyo charge controller (identical to Rover)
- Smart Energy Meter from KOSTAL and others
- Sonoff / MQTT / Tasmota
- Infini x Kw hybrid inverter 1 phase (protocol 16)
- Solar batteries
- SMA Sunny Tripower and Sunny Island
- HRDi Marlec charge controller (PV and wind power)
- go-eCharger Wallbox
- Keba wallbox
- Shelly 2.5, 3EM
- KACO inverter TL3 series
- SDM630 Energy Meter
- Wallbe wallbox
- EAsun ISolar V III Off-Grid inverter ( like MPPSolar 5048 MK and GK series),
- EAsun ISolar SPV SMV 1KVA-5KVA inverter ( like Effakta AX series )
- Delta inverter SI 2500
- ALPHA ESS T10 inverter
- Simple EVSE wallbox
- openWB wallbox
- Senec power storage
- Webasto wallbox
- Phocos Any-Grid
- Huawei SUN2000 inverter
- Phoenix Contact Wallbox
- Growatt inverter

--------------------------------------------------------------------------------------------------------------------



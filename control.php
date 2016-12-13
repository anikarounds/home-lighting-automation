<?php

class DeviceController
{
	private $devices = array();
	private $modes = array();
	
	public function DeviceController() {
		$conf_s = file_get_contents("./conf.json");
		$conf = json_decode($conf_s,true);
		$pdu = new Pdu($conf["PDU"]["address"]);
		$this->modes = $conf["Modes"];
		//$this->devices = $conf["devices"];
		foreach ($conf["devices"] as $k => $v) {
			if ($v["type"] == "WeMo") {
				$wemo = new WemoDevice($v["address"]);
				$n = $wemo->getFriendlyName();
				$this->devices[$n] = $wemo;
			} elseif ($v["type"] == "PDU") {
				$this->devices[$k] = new PduDevice($pdu,$v["unitNumber"],$k);
			} else {
				die("invalid device type: " . $v["type"] . " on device " . $k);
			}
		}
	}

	public function getState($device) {
		if (isset($device))
			return $this->devices[$device]->getState();

		$state = array();
		foreach ($this->devices as $k => $v) {
			$state[$k] = $v->getState();
		}
		return $state;
	}

	public function setState($k,$state) {
		$this->devices[$k]->setState($state);
	}
	
	public function getModes() {
		$modes = array();
		foreach ($this->modes as $mode => $settings) {
			$stateActive = "Active";
			foreach ($settings as $device => $state) {
				$curState = $this->getState($device);
				if ($curState != $state) {
					$stateActive = "Inactive";
				}
			}
			$modes[$mode] = $stateActive;
		}
		return $modes;
	}

	public function setMode($mode) {
		foreach($this->modes[$mode] as $device => $state) {
			if (array_key_exists($device,$this->devices)) {
				$this->devices[$device]->setState($state);
			} else {
				die("bad conf: Unknown device: " . $device);
			}
		}
	}
}

class WemoDevice
{
	private $address;
	private $cachedState;
	private $timestamp;
	
	public function WemoDevice($add) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $add . ":49153");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$port = curl_errno($ch) == 0 ? ":49153" : ":49152";
		$this->address = $add . $port;
	}
	
	public function getState() {
		// curl -0 -A '' -X POST -H 'Accept: ' -H 'Content-type: text/xml; charset="utf-8"' -H "SOAPACTION: \"urn:Belkin:service:basicevent:1#GetBinaryState\""
		if (isset($this->cachedState) && time() - $this->timestamp < 10)
			return $this->cachedState;
		$ch = curl_init();
		$opt = array(
                    CURLOPT_URL => $this->address . '/upnp/control/basicevent1',
                    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_POST => true,
		    CURLOPT_HTTPHEADER => array("SOAPACTION: \"urn:Belkin:service:basicevent:1#GetBinaryState\"",
						"Accept: ",
						"Content-type: text/xml; charset=\"utf-8\""),
		    CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="utf-8"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetBinaryState xmlns:u="urn:Belkin:service:basicevent:1"></u:GetBinaryState></s:Body></s:Envelope>'

                );
		curl_setopt_array($ch, $opt);
		$result = curl_exec($ch);
		$i = strpos($result, "<BinaryState>");
		return $result[$i+13] == 1 ? 'ON' : 'OFF';
	}

	public function setState($state) {
		$ch = curl_init();
		$binary = strtolower($state) == "off" ? '0' : '1';
                $opt = array(
                    CURLOPT_URL => $this->address . '/upnp/control/basicevent1',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => array("SOAPACTION: \"urn:Belkin:service:basicevent:1#SetBinaryState\"",
						"Content-type: text/xml"),
		    CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="utf-8"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetBinaryState xmlns:u="urn:Belkin:service:basicevent:1"><BinaryState>' . $binary . '</BinaryState></u:SetBinaryState></s:Body></s:Envelope>'
                );
                curl_setopt_array($ch, $opt);
                $result = curl_exec($ch);
		return;
	}

	public  function getFriendlyName() {
		$ch = curl_init();
                $opt = array(
                    CURLOPT_URL => $this->address . '/upnp/control/basicevent1',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => array("SOAPACTION: \"urn:Belkin:service:basicevent:1#GetFriendlyName\"",
						"Content-type: text/xml"),
		    CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="utf-8"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:GetFriendlyName xmlns:u="urn:Belkin:service:basicevent:1"></u:GetFriendlyName></s:Body></s:Envelope>'

                );
                curl_setopt_array($ch, $opt);
                $result = curl_exec($ch);
		$i = strpos($result,"<FriendlyName>");
		$j = strpos($result,"</FriendlyName>");
                return substr($result,$i+14,$j-$i-14);
	}
	
}

class PduDevice
{
	private $pdu;
	private $unit;
	private $name;
	
	public function PduDevice($pdu, $unit, $name) {
		$this->unit = $unit;
		$this->pdu = $pdu;
		$this->name = $name;
	}
	public function name() {
		return $name;
	}
	public function getState() {
		return $this->pdu->getUnitState($this->unit);
	}
	public function setState($value) {
		return $this->pdu->setUnitState($this->unit,$value);
	}
}

class Pdu
{
	private $address;
	private $state = array();
	private $lastQueryTimestamp;
	
	public function Pdu($add) {
		$this->address = $add;
	}
	
	public function getUnitState($unit) {
		if (!isset($this->lastQueryTimestamp) || time() - $this->lastQueryTimestamp > 60)
			$this->queryState();
		return $this->state[$unit];
	}
	
	private function queryState() {
		$url = $this->address . "/status";
		$ch = curl_init();
		$opt = array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true
		);
		curl_setopt_array($ch,$opt);
		$result = curl_exec($ch);
		//todo error check
		curl_close($ch);
		$i = strpos($result,"<div id=\"state\">");
		//var_dump($ls);
		$hex = "0x" . $result[$i+16] . $result[$i+17];
		$state = hexdec($hex);
		for ($i = 0; $i < 8; $i++) {
			$this->state[$i+1] = ($state & (1 << $i)) != 0 ? "ON" : "OFF";
		}
		$this->lastQueryTimestamp = time();
	}
	
	public function setUnitState($unit, $state) {
		$mode = (strtolower($state) == "off") ? "OFF" : "ON";
		$url = $this->address . "/outlet?" . $unit . "=" . $mode;
		$ch = curl_init();
		$opt = array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true
		);
		curl_setopt_array($ch,$opt);
		$result = curl_exec($ch);
		$this->state[$unit] = $mode;
	}
}

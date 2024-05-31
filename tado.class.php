<?php

class Tado {

        //https://shkspr.mobi/blog/2019/02/tado-api-guide-updated-for-2019/

        public $auth_url = "https://auth.tado.com/oauth/token";
        public $api_url_v1 = "https://my.tado.com/api/v1/";
        public $api_url_v2 = "https://my.tado.com/api/v2/";
        private $username = "";
        private $password = "";
        private $client_secret = "wZaRN7rpjn3FoNyF5IFuxg9uMzYJcvOoQ8QWiIqS3hfk6gLhVlG57j5YNoZL2Rtc";
        private $access_token = false;
        private $refresh_token = false;
        public $me = [];
        public $home_id = false;
        public $zones = array();
        public $error = false;
	public $debug = false;

	function __construct($debug = false) {

		if ($debug) $this->debug = true;

		$this->authenticate();
		
		if (!$this->access_token) {
			return $this->error;
		}

		$this->getMe();

		if (!$this->home_id) {
			return $this->error;
		}

		$this->getZones();
		
		return ($this);

	}

	private function authenticate() {

		$client_id = "tado-web-app";
		$grant_type = "password";
		$scope = "home.user";

		$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
		);

		$ch = curl_init();

		$data = "client_id=tado-web-app&grant_type=password&scope=home.user&client_secret=" . $this->client_secret . "&username=" . $this->username ."&password=" . $this->password;

		curl_setopt($ch, CURLOPT_URL, $this->auth_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$results = curl_exec($ch);

		curl_close($ch);

		$json_results = json_decode($results);

		if ($this->debug) {

			print_r($json_results);

		}

		if (isset($json_results->access_token)) {

			$this->access_token = $json_results->access_token;
			$this->refresh_token = $json_results->refresh_token;
			return $this;
		} else {
			$this->error = curl_error($ch);
			return false;
		}


	}

	private function api($endpoint, $api_url) {

		$api_url = $api_url.$endpoint;

		$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . $this->access_token
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$results = curl_exec($ch);
		$json_results = json_decode($results);

		if ($this->debug) {

			print_r($api_url);
			print_r($json_results);

		}

		curl_close($ch);

		return $json_results;

	}

	private function getMe() {

		$results = $this->api("me", $this->api_url_v1);

		if (!isset($results->homeId)) {
			$this->error = "No home id";
			return false;
		}
		$this->me = $results;
		$this->home_id = $results->homeId;

		return $this;

	}

	public function getZones() {

                $results = $this->api("homes/" . $this->home_id . "/zones", $this->api_url_v2);
                foreach ($results as $result) {

                        if ($result->type=="HEATING") {
                                $this->zones[$result->name] = array("id"=>$result->id, "type"=>"Heating");
                        } else {
                                $this->zones[$result->name] = array("id"=>$result->id, "type"=>"Other");
                        }

                }

                return $this;

	}

	public function listZones() {

		return $this->zones;

	}

	public function getZoneIdFromName ($zone_name) {

		if (isset($this->zones[$zone_name])) {

			return $this->zones[$zone_name]["id"];

		} else {

			return false;

		}

	}

	public function getZoneStateFromId ($zone_id) {

		return $this->api("homes/" . $this->home_id . "/zones/" . $zone_id . "/state", $this->api_url_v2);

	}

	public function getZoneStateFromName ($zone_name) {

		$zone_id = $this->getZoneIdFromName($zone_name);
		return $this->getZoneStateFromId ($zone_id);

	}

	public function zone_temperature($zone_name) {

		$zone_state = $this->getZoneStateFromName($zone_name);
		if ($zone_state->link->state=="ONLINE") {
			return $zone_state->sensorDataPoints->insideTemperature->celsius;
		} else {
			return "U";
		}

	}

	public function zone_humidity($zone_name) {

		$zone_state = $this->getZoneStateFromName($zone_name);
		if ($zone_state->link->state=="ONLINE") {
			return $zone_state->sensorDataPoints->humidity->percentage;
		} else {
			return "U";
		}

	}

	public function zone_heating_target($zone_name) {

		$zone_state = $this->getZoneStateFromName($zone_name);
		if ($zone_state->link->state=="ONLINE") {
			if ($zone_state->setting->power=="OFF") {
				return 0;
			} else {
				return $zone_state->setting->temperature->celsius;
			}
		} else {
			return "U";
		}

	}

	public function zone_heating_power($zone_name) {

			$zone_state = $this->getZoneStateFromName($zone_name);
			return $zone_state->activityDataPoints->heatingPower->percentage;

	}

	public function zone_hot_water_state($zone_name) {

		$zone_state = $this->getZoneStateFromName($zone_name);
		if ($zone_state->link->state=="ONLINE") {
                        if ($zone_state->setting->power=="OFF") {
                                return 0;
                        } else {
                                return 100;
                        }
                } else {
                        return "U";
                }

	}


	public function getDeviceFeatures() {
		
	}

	public function getCurrentSetting($zone_name) {
		$zone_id = $this->getZoneIdFromName($zone_name);
		$zone_state = $this->getZoneStateFromId($zone_id);
		return $zone_state->setting;
	}

	public function getNextScheduleChange($zone_name) {
		$zone_id = $this->getZoneIdFromName($zone_name);
		$zone_state = $this->getZoneStateFromId($zone_id);
		if (isset($zone_state->overlay)) {
			return $zone_state->overlay;
		} elseif (isset($zone_state->nextScheduleChange)) {
			return $zone_state->nextScheduleChange;
		} else {
			return false;
		}
	}

}

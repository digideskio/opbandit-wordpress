<?php

class OpBanditService {
  function __construct($apikey, $apisecret, $site, $baseurl = "https://opbandit.com/api/experiments") {
    $this->authstring = $apikey . ":" . $apisecret;
    $this->site = $site;
    $this->baseurl = $baseurl;
  }

  private function makeRequestArgs() {
    return array(
		 'headers' => array('Authorization' => 'Basic ' . base64_encode($this->authstring)),
		 'timeout' => 5,
		 'sslverify' => true,
		 'redirection' => 0
		 );
  }

  public function getExperiment($default) {
    $params = array('default' => $default, 'site' => $this->site);
    $url = $this->baseurl . "?" . http_build_query($params);
    $response = wp_remote_get($url, $this->makeRequestArgs());
    if(!is_wp_error($response)) {
      return json_decode($response['body']);
    }
    return $response->get_error_message();
  }

  public function setExperiment($default, $additional) {
    $args = $this->makeRequestArgs();
    $args['body'] = array('default' => $default, 'site' => $this->site);
    for($i = 0; $i < count($additional); $i++)
      $args['body']['alternative_' . $i] = $additional[$i];
    
    $response = wp_remote_post($this->baseurl, $args);
    if(!is_wp_error($response)) {
      return json_decode($response['body']);
    }
    return $response->get_error_message();
  }
}

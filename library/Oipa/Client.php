<?php

/**
 * Oipa_Client class
 *
 * An client written for Aidstream to connect to the OIPA API
 *
 * For details and documentation, please see http://github.com/jeffreybarke/Ckan_client-PHP
 *
 * @author		Zimmerman
 *
 */

class Oipa_Client
{

	protected $api_key = FALSE;
	protected $api_version = 'v3';
	public $base_url = 'http://localhost:8000/';
	protected $ch = FALSE;
	protected $ch_headers;

	protected $http_status_codes = array(
		'200' => 'OK',
		'301' => 'Moved Permanently',
		'400' => 'Bad Request',
		'403' => 'Api key or publisher id is not correct.',
		'404' => 'Not Found',
		'409' => 'Conflict (e.g. name already exists)',
		'500' => 'Service Error'
	);

	protected $user_agent = 'Oipa_client-PHP/%s';
	protected $version = '0.1.0';	
	protected $error;

	public function __construct($api_key = FALSE)
	{
		// If provided, set the API key.
		if ($api_key)
		{
			$this->set_api_key($api_key);
		}

        // set base URI from application.ini
		$this->base_url = Zend_Registry::getInstance()->config->oipa->url;
                
		// Set base URI and Ckan_client user agent string.
		//$this->set_base_url();
		$this->set_user_agent();
		// Create cURL object.
		$this->ch = curl_init();
		// Follow any Location: headers that the server sends.
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		// However, don't follow more than five Location: headers.
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
		// Automatically set the Referer: field in requests 
		// following a Location: redirect.
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
		// Return the transfer as a string instead of dumping to screen. 
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		// If it takes more than 45 seconds, fail
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 45);
		// We don't want the header (use curl_getinfo())
		curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
		// Set user agent to Ckan_client
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
		// Track the handle's request string
		curl_setopt($this->ch, CURLINFO_HEADER_OUT, TRUE);
		// Attempt to retrieve the modification date of the remote document.
		curl_setopt($this->ch, CURLOPT_FILETIME, TRUE);
		// Initialize cURL headers
		$this->set_headers();

	}

	public function __destruct()
	{
		if ($this->ch)
		{
			curl_close($this->ch);
			unset($this->ch);
		}
	}


	public function set_api_key($api_key)
	{
		$this->api_key = $api_key;
	}

	protected function set_base_url()
	{
		$this->base_url = sprintf($this->base_url, $this->api_version);
	}

	protected function set_headers()
	{
		$date = new DateTime(NULL, new DateTimeZone('UTC'));
		$this->ch_headers = array(
			'Date: ' . $date->format('D, d M Y H:i:s') . ' GMT', // RFC 1123
			'Accept: application/json;q=1.0, application/xml;q=0.5, */*;q=0.0',
			'Accept-Charset: utf-8',
			'Accept-Encoding: gzip'
		);
	}

	protected function set_user_agent()
	{
		if ('80' === @$_SERVER['SERVER_PORT'])
		{
			$server_name = 'http://' . $_SERVER['SERVER_NAME'];
		}
		else
		{
			$server_name = '';
		}
		$this->user_agent = sprintf($this->user_agent, $this->version) . 
			' (' . $server_name . $_SERVER['PHP_SELF'] . ')';
	}


	public function make_request($method, $url, $data = FALSE)
	{	
		// $build_q = http_build_query(
  //           array(
  //           	'publisher' => array(
  //           		'ref' => "org_ref_1",
  //               	'name' => "org_name_1"
  //           	), 
  //           	'xml_source' => array(
  //           		'source_url' => $json_decoded->resources[0]->url,
  //           		'name' =>$json_decoded->name
  //           	)
  //           )
  //       );

		// just use simple file_get_contents for now
		$opts = array('http' =>
            array(
                'method'  => 'GET',
                'timeout' => 1200
            )
        );

		$json_decoded = json_decode($data);

        $url .= '?publisher_ref=org_ref_1&publisher_name=org_name_1&xml_source_url=' . urlencode($json_decoded->resources[0]->url) . '&xml_ref=' . urlencode($json_decoded->name);
       	$context  = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        var_dump($result);
        exit();

        /*
		
		// Set cURL method.
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		// Set cURL URI.
		curl_setopt($this->ch, CURLOPT_URL, $this->base_url . $url);

		// If POST or PUT, add Authorization: header and request body
		if ($method === 'POST' || $method === 'PUT')
		{
			
			// Add Authorization: header.
			$this->ch_headers[] = 'Authorization: ' . $this->api_key;
			// Add data to request body.
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		}
		else
		{
			// Since we can't use HTTPS,
			 // if it's in there, remove Authorization: header
			$key = array_search('Authorization: ' . $this->api_key, 
				$this->ch_headers);
			if ($key !== FALSE)
			{
				unset($this->ch_headers[$key]);
			}
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, NULL);
		}
		// Set headers.
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->ch_headers);

		$ctx = stream_context_create(array('http'=>
		    array(
		        'timeout' => 1200, // 1 200 Seconds = 20 Minutes
		    )
		));

		
		// Execute request and get response headers.
		$response = curl_exec($this->ch);
		

		$info = curl_getinfo($this->ch);
		// Check HTTP response code
		if ($info['http_code'] !== 201 && $info['http_code'] !== 200)
		{
		    $this->error = $this->http_status_codes[$info['http_code']];
			return false;
		}
		// Determine how to parse
		if (isset($info['content_type']) && $info['content_type'])
		{
			$content_type = str_replace('application/', '', 
				substr($info['content_type'], 0, 
				strpos($info['content_type'], ';')));
			return $this->parse_response($response, $content_type);
		}
		else
		{
			throw new Exception('Unknown content type.');
		}
		*/
	}

	protected function parse_response($data = FALSE, $format = FALSE)
	{
		if ($data)
		{
			if ('json' === $format)
			{
				return json_decode($data);
			}
			else
			{
				throw new Exception('Unable to parse this data format.');
			}
		}
		return FALSE;
	}
	
	public function getError()
	{
	    return $this->error;    
	}

}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter MapQuest Class
 * mapquest api account 
 */
 
class Mapquest {

	private $_ci;				// CodeIgniter instance
	private $_response = '';		  // Contains the cURL response for output and debug
	private $_apiKey = 'Fmjtd%7Cluu2n1ual9%2Cr2%3Do5-hw7n9';
	private $_geocodingURL = 'http://www.mapquestapi.com/geocoding/v1/address?';
	private $_reverseGeocodingURL = 'http://www.mapquestapi.com/geocoding/v1/reverse?';
	private $_directionsURL = NULL;
	private $_mode = NULL; //geocode, directions, reverseGeocode
	private $_requestURL = NULL;
	private $_location = NULL;
	private $_debug = TRUE;
	private $_output = NULL;
	private $_apiLimitCountFile = '/tmp/mapquest.api.count.txt';
	private $_dailyApiLimit = 1000;
	
	private $_cacheDir = '/var/cache/mapquest/';
	
	public $outputMode = 'array';
	
	//Location Variables for geocoding and directions
	public $address1 = NULL;
	public $address2 = NULL;
	public $city = NULL;
	public $zip = NULL;
	public $state = NULL;
	
	//the current working address
	public $address = array();
	
	//Reverse geocoding coordinates
	public $latitude = 0;
	public $longitude = 0;
	//For the lazy ones
	public $lat = 0;
	public $lng = 0;
	
	//storage array for directions
	public $waypoints = array();

	//Gets the EP Instance and Loads the cURL library 
	function __construct()
	{
        $this->_ci =& EP_Controller::getInstance();
        $this->_ci->load->library('curl');
		$this->_ci->load->helper('file');
		log_message('debug', 'MapQuest Class Initialized');
		
		//For the lazy ones
		if(isset($this->lat))
		{
			$this->latitude = $this->lat;
		}
		if(isset($this->lng))
		{
			$this->longitude = $this->lng;
		}
		
		//Setup the address object
		$this->address = array(
			'address1' => $this->address1,
			'address2' => $this->address2,
			'city' => $this->city,
			'state' => $this->state,
			'zip' => $this->zip
		);
	}
	
/*
	For geolocating addresses or part of addresses to lat/lng and more	
		
	//One or more location variable (address1, address2, city, state, zip) must be set
	
	//Example
	$this->mapquest->zip = 90248;
	$response = $this->mapquest->geocode();
*/
	
	public function geocode()
	{
		//set the mode to geocoding
		$this->_mode = "geocode";
		
		//build the location
		$location = '';
		if(!empty($this->address1))
		{
			$location .= $this->address1 . ' ';
		} 
		if(!empty($this->address2))
		{
			$location .= $this->address2 . ' ';
		} 
		if(!empty($this->city))
		{
			$location .= $this->city . ' ';
		} 
		if(!empty($this->state))
		{
			$location .= $this->state . ' ';
		} 
		if(!empty($this->zip))
		{
			$location .= $this->zip;
		} 			
		
		$this->_location = $location;
		//log the location
		$this->_debug(__METHOD__ . ' ' . $this->_location);		
		
		//Validate that at least one geocoding variable is set
		if(empty($this->_location))
		{
			die("At least one location variable needs to be set");
		}		
		
		//fire off the request
		$this->_response = $this->_request();
		
		//ensure that a response was received
		if(empty($this->_response))
		{
			die("Did not receive a response from mapquest");
		}
		
		//return the response
		return $this->_response();
	}

/*
	For Reverse Geocoding you simple need to set the latitude and longitude objects
	Then run the reverseGeocode method.
	
	//load the mapquest library
	$this->load->library('mapquest');
	//set the latitude and longitude elements
	$this->mapquest->latitude = 40.0765;
	$this->mapquest->longitude = -76.329999;
	//run & return the validation
	$address = $this->mapquest->reverseGeocode();
*/
	
	public function reverseGeocode()
	{
		if(empty($this->latitude) && empty($this->longitude))
		{
			die("Longitude and Latitude are required for reverse geocoding");
		}
		
		//set the request mode
		$this->_mode = 'reverse_geocode';
		
		//fire off the request
		$this->_response = $this->_request();
		
		//make sure there is a valid response
		if(empty($this->_response))
		{
			die("Did not receive a response from mapquest");		
		}

		//return the response
		return $this->_response();		
	}
	
/*
	Directions requires a small combination of method calls. 
	You first need to setup a address object, add the waypoint and do it at least one more time so that you have two waypoints. 
	
	//load the mapquest library
	$this->load->library('mapquest'); 
	
	//add the first address
	$this->mapquest->address1 = '542 N. Homerest';
	$this->mapquest->city = 'West Covina';
	$this->mapquest->state = 'CA';
	$this->mapquest->zip = '91791';
	$this->mapquest->addWaypoint();	    	    	

	//add the second address
	$this->mapquest->address1 = '535 W. 135th St.';
	$this->mapquest->city = 'Gardena';
	$this->mapquest->state = 'CA';
	$this->mapquest->zip = '90248';
	$this->mapquest->addWaypoint();
	
	$directions = $this->mapquest->directions();	
*/
	
	public function directions()
	{
		//ensure there is at least two waypoints
		if(empty($this->waypoints[0]) && !empty($this->waypoints[1]))
		{
			die("You need at least two ");
		}
		
		//perform the request
		$this->_mode = 'directions';
		$this->_response = $this->_request();
		
		//make sure there is a valid response
		if(empty($this->_response))
		{
			die("Did not receive a response from mapquest");		
		}		
		
		//return the directions
		return $this->_response();
		
	}
	
/* 	This is part of using the directions.  */
	
	public function addWaypoint()
	{
		//ensure the minimum address elements are set
		if(empty($this->address1) || empty($this->city) || empty($this->state) || empty($this->zip))
		{
			die("Adding a waypoint requires a full address");
		}
		
		//add it to the array of waypoints
		$this->waypoints[] = array(
			'address1' => $this->address1,
			'address2' => $this->address2,
			'city' => $this->city,
			'state' => $this->state,
			'zip' => $this->zip
		);
		
		//unset the address objects
		$this->clearLocation();
	}
	
/* 	This is used interaly, but is accessable in case you need to get the current waypoints.  */
	
	public function getWaypoints()
	{
		//return the current waypoints
		return $this->waypoints;
	}

/* 	You can remove a waypoint from the waypoints array object by tossing in the array key for that waypoint.  */
	
	public function removeWaypoint($id = 0)
	{
		//if the waypoint array key exists
		if(isset($this->waypoints[$id]))
		{
			//remove the waypoint element from the array
			foreach($this->waypoints as $element)
			{
				//unset each set element from the array
				unset($this->waypoints[$id][$element]);
			}
			//remove the waypoint id
			unset($this->waypoints[$id]);
			
			//reset the waypoints keys for sanity
			$tmpWaypoints = $this->waypoints;
			$this->waypoints = $tmpWaypoints;
			
			//return the waypoints because people are lazy
			return $this->getWaypoints();
		}
		else
		{
			die("The waypoint key does not exist");
		}
	}
	
/* 	
		Address Validation takes an address object and returns up to three array elements. 
		The most important one is the valid array element which is a TRUE or false. 
		The next elements are the address matching fields which shows where any potential address element conflicts could be. 
		The last element is a suggesstion and is only accurate when the submitted address could be parsed and a suggesstion could be found.  

    	//load the patient
    	$patient = new patient($patientId);
    	//load the mapquest library
    	$this->load->library('mapquest');
    	//set the address elements
		$this->mapquest->address1 = $patient->addresses[0]->line1;
		$this->mapquest->address2 = $patient->addresses[0]->line2;
		$this->mapquest->city = $patient->addresses[0]->city;
		$this->mapquest->state = $patient->addresses[0]->state;
		$this->mapquest->zip = $patient->addresses[0]->zip;				
    	//run & return the validation
    	$validation = $this->mapquest->validateAddress();
*/ 
	
	public function validateAddress()
	{
		if(empty($this->address1) || empty($this->city) || empty($this->state) || empty($this->zip))
		{
			die("Address validation requires all the address line 1, city, state and zip");
		}
		
		$validation = null;
		$location = array();
		$return = array();
		$return['valid'] = FALSE;
		$return['addressSuggestion'] = array();
		$return['nonMatchingElements'] = array();
		
		//set the output mode to array
		$this->outputMode = 'array';
		
		//run the request
		$validation = $this->geocode();
		
		//ensure there is a validate set of coordinates
		if(empty($validation['results'][0]['locations'][0]['latLng']['lat']) && empty($validation['results'][0]['locations'][0]['latLng']['lng']))
		{
			throw new Exception("Address validation could not find a valid location for this address");
		}
		
		//validate that the address1, city, state and zip match what is returned from mapquest
		$location = $validation['results'][0]['locations'][0];
		
		//validate the address elements against the elements provided
		//validate the street number
		$mapquestStreetParsed = explode(' ', $location['street']);
		$userInputStreetParsed = explode(' ', $this->address1);
		if(intval($mapquestStreetParsed[0]) != intval($userInputStreetParsed[0]))
		{
			$return['nonMatchingElements']['address1'] = $this->address1;
		}

		if(strtoupper($location['adminArea5']) != strtoupper($this->city))
		{
			$return['nonMatchingElements']['city'] = $this->city;
		}
		if(strtoupper($location['adminArea3']) != strtoupper($this->state))
		{
			$return['nonMatchingElements']['state'] = $this->state;
		}
		if(strpos($location['postalCode'], substr($this->zip, 0 ,5)) === FALSE)
		{
			$return['nonMatchingElements']['zip'] = $this->zip;
		}
		
		//create the suggested address
		if(!empty($return['nonMatchingElements']))
		{
			$return['addressSuggestion']['address1'] = $location['street'];
			$return['addressSuggestion']['city'] = $location['adminArea5'];
			$return['addressSuggestion']['state'] = $location['adminArea3'];
			$return['addressSuggestion']['zip'] = $location['postalCode'];		
		}
		
		if(empty($return['nonMatchingElements']))
		{
			$return['valid'] = TRUE;
		}
		
		$this->_debug(__METHOD__ . print_r($return, TRUE));
		return $return;
	}
	
	/*
	* For clearing location variables when in some kind of batchmode
	*/	
	public function clearLocation()
	{
		unset($this->address1);
		unset($this->address2);
		unset($this->city);
		unset($this->zip);
		unset($this->state);
		
		$this->address = array();
	}

	//Builds and sends a curl request based on the mode
	private function _request()
	{
		$request = NULL;
		if(!empty($this->_mode))
		{
			$this->_debug(__METHOD__ . " is running in mode " . $this->_mode);
			if($this->_mode == 'geocode')
			{		
				//check cache
				$cache = $this->_cache($this->_location);
				if($cache)
				{
					return $cache;
				}
			
				//check/set the limiter
				$this->_apiLimiter();			
																						
				//build the request URL
				$this->_requestURL = $this->_geocodingURL . 'key=' . $this->_apiKey . '&inFormat=kvp&outFormat=json&location=' . urlencode($this->_location);
				
				//Debug the request
				$this->_debug(__METHOD__ . ' ' . $this->_requestURL);
				
				//send the request and set the response
				$request = $this->_ci->curl->simple_get($this->_requestURL);
			
				//add the request to the cache the cache
				$this->_cache($this->_location, 'SET', $request);
				
				return $request;
			}
			elseif($this->_mode == 'reverse_geocode')
			{		
				//set the location
				$this->_location = '&lat=' . $this->latitude . '&lng=' . $this->longitude;				
			
				//check cache
				$cache = $this->_cache($this->_location);
				if($cache)
				{
					return $cache;
				}
			
				//check/set the limiter
				$this->_apiLimiter();				
				
				//build the request url
				$this->_requestURL = $this->_reverseGeocodingURL . 'key=' . $this->_apiKey . $this->_location;
				log_message('debug', 'THIS IS THE FUCKING REQUEST URL ' . $this->_requestURL);
				//debug the request url
				$this->_debug(__METHOD__ . ' ' . $this->_requestURL);
				
				//peform the get request and setup the reponse
				$request = $this->_ci->curl->simple_get($this->_requestURL);
				
				//add the request to the cache
				$this->_cache($this->_location, 'SET', $request);
				
				return $request;
			}
			elseif($this->_mode == 'directions')
			{
				//build the location string
				$waypointCount = 0;
				foreach($this->waypoints as $waypoint)
				{
					//merge address line two if its not empty
					if(!empty($waypoint['address2']))
					{
						$waypoint['address1'] .= ' ' . $waypoint['address2'];
					}
				
					//add one to the counter
					$waypointCount++;
					if($waypointCount <= 1)
					{
						$this->_location .= '&from=' . urlencode($waypoint['address1'] . ' ' . $waypoint['city'] . ' ,' . $waypoint['state'] . ' ' . $waypoint['zip']);
					}
					else
					{
						$this->_location .= '&to=' . urlencode($waypoint['address1'] . ' ' . $waypoint['city'] . ' ,' . $waypoint['state'] . ' ' . $waypoint['zip']);
					}
				}
				
				//check cache
				$cache = $this->_cache($this->_location);
				if($cache)
				{
					return $cache;
				}
				
				//finish the url
				$this->_requestURL = 'http://www.mapquestapi.com/directions/v1/route?key=' . $this->_apiKey . '&outFormat=json' . $this->_location;
				
				//debug the url
				$this->_debug(__METHOD__ . ' looking for directions with URL ' . $this->_requestURL);
				
				//perform the request
				$request = $this->_ci->curl->simple_get($this->_requestURL);
				
				//add the request to the cache
				$this->_cache($this->_location, 'SET', $request);				

				return $request;
			}
		}
	}

	//Parses and returns the curl response
	private function _response()
	{
		//Debug the response
		$this->_debug(__METHOD__ . ' returned ' . print_r(json_decode($this->_response, TRUE), TRUE));
		
		//return the output using the requested output format	
		if($this->outputMode == 'array')
		{
			return json_decode($this->_response, TRUE);
		}
		
		if($this->outputMode == 'json')
		{
			return $this->response;
		}
	}
	
	private function _setAddresObj()
	{
		
	}
	
	//a recursive function that will manage a tmp file to prevent us from using over a 1000 requests a day
	//Note… this is only used on api call that have a limit, geocoding and reverse geocoding and when there is no caching for that location
	private function _apiLimiter()
	{
		//if the api counter file exists
		if(file_exists($this->_apiLimitCountFile))
		{	
			//if the file time is not from today, delete it, recall apiLimiter
			if(filemtime('/tmp/mapquest.api.count.txt') + 86400 < time())
			{
				//remove the file and recall the limiter
				unlink($this->_apiLimitCountFile);
				$this->_apiLimiter();
			}
			else
			{
				//get the count
				$count = read_file($this->_apiLimitCountFile);
				//if the count is over 999
				if(intval($count) >= $this->_dailyApiLimit)
				{
					//throw a new exception that prevents the api call from
					throw new Exception("There has been too many geolocation requests today. Please contact support");		
				}
				else
				{
					//else add to the count
					$count++;
					//increment the count and rewrite the file
					write_file($this->_apiLimitCountFile, $count);
				}
			}		
		}
		else
		{
			//else create the file
			write_file($this->_apiLimitCountFile, 1);
		}
	}

	//Logs a message
	private function _debug($sData)
	{
		log_message("debug", print_r($sData, TRUE	));
	}

	private function _cache($sQuery = NULL, $sMode = 'FETCH', $sSetCacheData = NULL)
	{	
		//make the directory if necessary
		if(!is_dir(substr($this->_cacheDir, 0, -1)))
		{
			mkdir(substr($this->_cacheDir, 0 , -1), 0777, TRUE);
		}			
		
		//check for cache
		if($sMode == 'FETCH')
		{
			$cacheFile = read_file($this->_cacheDir . $sQuery);
			if($cacheFile !== FALSE)
			{
				log_message("debug", "mapquest cache file does exist");
				return $cacheFile;
			}
			else
			{
				log_message("debug", "mapquest cache file does not exist");
				return FALSE;
			}
		}
		
		//add the item to the cache
		if($sMode == 'SET' && !empty($sSetCacheData))
		{
			log_message("debug", "Trying to write cache");
			write_file($this->_cacheDir . $sQuery, $sSetCacheData);
		}
	}

}

/* End of file mapquest.php */
/* Location: shared/libraries/mapquest.php */
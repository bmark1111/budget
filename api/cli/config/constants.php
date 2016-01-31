<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

define("CLIENT_ID", "3MVG9y6x0357HledSoN6KclEOqJ.9p_Shzz_ZYU0rN17idjQp3f8SHFt34gEhS1alsop0MW6LAV3wwoNBx_.F");
define("CLIENT_SECRET", "8895415133918753211");
define("REDIRECT_URI", "https://54.187.105.130/oauth_callback.php");
define("LOGIN_URI", "https://login.salesforce.com");

define("USERNAME", "techadmin@proovebio.com");
define("PASSWORD", "SalesProove123");
define("SECURITY_TOKEN", "CYjHM5CFbuX2tpqCB0YEUAYyi");

/* End of file constants.php */
/* Location: ./application/config/constants.php */
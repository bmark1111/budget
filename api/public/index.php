<?php

header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, TOKENID, ACCOUNTID, authorization");

//print_r($_SERVER);die;
// This is the CORS Preflight request
// TODO: incorporate this into EP_Controller to give permission to the actual request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	switch ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) {
		default:
			error_log('XMLHttpRequest cannot load ' . $_SERVER['HTTP_HOST'] . '. Origin ' . $_SERVER['HTTP_ORIGIN'] . ' is not allowed by Access-Control-Allow-Origin.');
			header('HTTP 400 Bad Request', true, 400);
			exit;
			break;
		case 'GET':
		case 'PUT':
		case 'POST':
		case 'DELETE':
			exit;
			break;
	}
}
//$link = mysqli_connect("localhost", "budgettr_budget", "X120798x!", "budgettr_b_1_m");
//if (!$link) {
//    echo "Error: Unable to connect to MySQL." . PHP_EOL;
//    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
//    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
//    exit;
//}
//
//echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
//echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;
//
//mysqli_close($link);
//
//die('aaaaaaaaaa');



//header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE");//, OPTIONS");
//header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");
//header("Access-Control-Allow-Headers: X-Custom-Header");

header('Access-Control-Allow-Credentials: true');

header('X-Powered-By: Budget 1.0', TRUE);

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
    $sEnvironment = 'production';

    if(isset($_SERVER['ENVIRONMENT'])) {
       $sEnvironment = $_SERVER['ENVIRONMENT'];
    }
	else if(isset($_SERVER['SERVER_NAME'])) {
        $aDomainPieces = explode('.', $_SERVER['SERVER_NAME']);

 		$aDomainPieces = array_reverse($aDomainPieces);

		$sEnv = explode('-', $aDomainPieces[1]);

        switch($sEnv) {
            case 'loc':
            case 'dev':
                $sEnvironment = 'development';
                break;
			case 'beta':
				$sEnvironment = 'beta';
				break;
			case 'stag':
				$sEnvironment = 'staging';
                break;
            default:
                $sEnvironment = 'production';
                break;
        }
    }

	define('ENVIRONMENT', $sEnvironment);
/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
	switch (ENVIRONMENT) {
 		case 'local':
		case 'development':
		case 'alpha':
 		case 'beta':
            error_reporting(E_ALL);
			ini_set('display_errors', '1');
            break;
		case 'staging':
		case 'production':
			error_reporting(0);
			break;
		default:
			exit('The REST application environment is not set correctly.');
	}

/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
	$system_path = '../system';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
	$application_folder = '../application';


/*
 *---------------------------------------------------------------
 * SHARED FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "shared"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
	$shared_folder = '../shared';

 /*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here.  For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT:  If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller.  Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 *
 */
	// The directory name, relative to the "controllers" folder.  Leave blank
	// if your controller is not in a sub-folder within the "controllers" folder
	// $routing['directory'] = '';

	// The controller class file name.  Example:  Mycontroller.php
	// $routing['controller'] = '';

	// The controller function you wish to be called.
	// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 *
 */
	// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

	// Set the current directory correctly for CLI requests
	if (defined('STDIN'))
	{
		chdir(dirname(__FILE__));
	}

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path).'/';
	}

	// ensure there's a trailing slash
	$system_path = rtrim($system_path, '/').'/';

	// Is the system path correct?
	if ( ! is_dir($system_path))
	{
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
	}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

	// The PHP file extension
	define('EXT', '.php');

	// Path to the system folder
	define('BASEPATH', str_replace("\\", "/", $system_path));

	// Path to the front controller (this file)
	define('FCPATH', str_replace(SELF, '', __FILE__));

	// Name of the "system folder"
	define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));

	// The path to the "application" folder
	if (is_dir($application_folder))
	{
		define('APPPATH', $application_folder.'/');
	}
	else
	{
		if ( ! is_dir(BASEPATH.$application_folder.'/'))
		{
			exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
		}

		define('APPPATH', BASEPATH.$application_folder.'/');
	}

    // The path to the "shared" folder
	if (is_dir($shared_folder))
	{
		define('SHAREPATH', $shared_folder.'/');
	}
	else
	{
		if ( ! is_dir(BASEPATH.$shared_folder.'/'))
		{
			exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
		}

		define('SHAREPATH', BASEPATH.$shared_folder.'/');
	}

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */

require_once BASEPATH.'core/CodeIgniter'.EXT;

/* End of file index.php */
/* Location: ./index.php */

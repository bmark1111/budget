<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "api";
$route['404_override'] = '';

$route['login']									= "user/login";						// GET - login
$route['register']								= "user/register";					// POST - register

$route['data/logout']							= "user/logout";					// POST - logout

$route['data/budget/load']						= "api/budget/load";				// GET - load dashboard transactions
$route['data/budget/these']						= "api/budget/these";				// GET - load dashboard transactions for interval and category

$route['data/sheet/load']						= "api/sheet/load";					// GET - load sheet transactions

$route['data/dashboard/ytdTotals']				= "api/dashboard/ytdTotals";		// GET - load YTD Totals
$route['data/dashboard/these']					= "api/dashboard/these";			// GET - load YTD Transactions for category

$route['data/transaction/loadAll']				= "api/transaction/loadAll";		// GET - load all transactions in list
$route['data/transaction/delete']				= "api/transaction/delete";			// GET - delete transaction
$route['data/transaction/edit']					= "api/transaction/edit";			// GET - edit transaction
$route['data/transaction/save']					= "api/transaction/save";			// POST - save transaction

$route['data/forecast/loadAll']					= "api/forecast/loadAll";			// GET - load all forecasts in list
$route['data/forecast/delete']					= "api/forecast/delete";			// GET - delete forecast
$route['data/forecast/edit']					= "api/forecast/edit";				// GET - edit forecast
$route['data/forecast/save']					= "api/forecast/save";				// POST - save forecast

$route['data/upload/counts']					= "api/upload/counts";				// GET - get pending uploaded transactions count
$route['data/upload/loadAll']					= "api/upload/loadAll";				// GET - load all uploaded transactions in list
$route['data/upload/assign']					= "api/upload/assign";				// GET - assign uploaded transaction
$route['data/upload/post']						= "api/upload/post";				// POST - post uploaded transaction
$route['data/upload/delete']					= "api/upload/delete";				// GET - delete uploaded transaction

$route['data/bank/load']						= "api/bank/load";					// GET - get all banks
$route['data/bank/accounts']					= "api/bank/accounts";				// GET - get bank accounts
$route['data/bank/delete']						= "api/bank/delete";				// GET - delete bank
$route['data/bank/edit']						= "api/bank/edit";					// GET - edit bank
$route['data/bank/save']						= "api/bank/save";					// POST - save bank
$route['data/rest/reconcileTransactions']		= "api/rest/reconcileTransactions";	// POST - reconcile transactions

$route['data/category/load']					= "api/category/load";				// GET - get all categories
$route['data/category/delete']					= "api/category/delete";			// GET - delete category
$route['data/category/edit']					= "api/category/edit";				// GET - edit category
$route['data/category/save']					= "api/category/save";				// POST - save category
$route['data/category']							= "api/category";					// GET - get categories

$route['data/setting/load']						= "api/setting/load";				// GET - load settings
$route['data/setting/save']						= "api/setting/save";				// POST - save settings

$route['upload/(:num)/(:num)']					= "upload/index/$1/$2";				// Upload transactions

/* End of file routes.php */
/* Location: ./application/config/routes.php */


var app = angular.module('budgetApp', ['ngRoute', 'ngResource', 'ngContextMenu', 'ui.bootstrap']);

app.config(function($routeProvider, $httpProvider)
{
	$routeProvider
		.when('/',
		{
			controller:		'DashboardController',
			templateUrl:	'app/views/dashboard.html'
		})
		.when('/forecast',
		{
			controller:		'ForecastController',
			templateUrl:	'app/views/forecast.html'
		})
		.when('/transactions',
		{
			controller:		'TransactionsController',
			templateUrl:	'app/views/transactions.html'
		})
		.when('/uploads',
		{
			controller:		'UploadsController',
			templateUrl:	'app/views/uploads.html'
		})
		.when('/bank_settings',
		{
			controller:		'SettingsController',
			templateUrl:	'app/views/bank_settings.html'
		})
		.when('/budget_settings',
		{
			controller:		'SettingsController',
			templateUrl:	'app/views/budget_settings.html'
		})
		.otherwise({redirectTo: '/'});

// CHECK THIS FOR NEED ////////
$httpProvider.defaults.useXDomain = true;
delete $httpProvider.defaults.headers.common['X-Requested-With'];
///////////////////////////////
});

app.run(function($rootScope, RestData)
{
	// get the badge count for pending uploaded transactions
	RestData.getCounts(
		function(response)
		{
			$rootScope.transaction_count = (parseInt(response.data.count) > 0) ? parseInt(response.data.count): '';
		});
});


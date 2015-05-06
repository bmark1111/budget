
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


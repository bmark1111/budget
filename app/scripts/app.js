
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
	$rootScope.bank_accounts = [];

	// get the badge count for pending uploaded transactions
	RestData.getUploadCounts(
		function(response)
		{
			$rootScope.transaction_count = (parseInt(response.data.count) > 0) ? parseInt(response.data.count): '';
		});

	// get the categories
	RestData.getCategories(
		function(response)
		{
			$rootScope.categories = response.data.categories;
		});

	// get the categories
	RestData.getBankAccounts(
		function(response)
		{
//			$rootScope.bank_accounts = response.data.bank_accounts;
			angular.forEach(response.data.bank_accounts,
				function(bank_account)
				{
					$rootScope.bank_accounts.push({
						'id': bank_account.id,
						'name': bank_account.bank.name + ' ' + bank_account.name
					})
				});
		});
});


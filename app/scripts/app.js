
var app = angular.module('budgetApp', ['ngRoute', 'ngResource', 'ngContextMenu', 'ui.bootstrap']);

app.config(function($routeProvider, $httpProvider, USER_ROLES)
{
//	$httpProvider.interceptors.push([
//		'$injector',
//		function ($injector)
//		{
//console.log($injector.get('AuthInterceptor'));
//			return $injector.get('AuthInterceptor');
//		}
//	]);

	$routeProvider
		.when('/',
		{
			controller:		'HomeController',
			templateUrl:	'app/views/home.html'
		})
		.when('/login',
		{
			controller:		'LoginController',
			templateUrl:	'app/views/login-form.html'
		})
		.when('/dashboard',
		{
			controller:		'DashboardController',
			templateUrl:	'app/views/dashboard.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/logout',
		{
			controller:		'LogoutController',
			templateUrl:	'app/views/login-form.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/forecast',
		{
			controller:		'ForecastController',
			templateUrl:	'app/views/forecasts.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/transactions',
		{
			controller:		'TransactionsController',
			templateUrl:	'app/views/transactions.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/uploads',
		{
			controller:		'UploadsController',
			templateUrl:	'app/views/uploads.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/bank_settings',
		{
			controller:		'SettingsController',
			templateUrl:	'app/views/bank_settings.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/budget_settings',
		{
			controller:		'SettingsController',
			templateUrl:	'app/views/budget_settings.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.otherwise(
		{
			redirectTo: '/'
		});

// CHECK THIS FOR NEED ////////
$httpProvider.defaults.useXDomain = true;
delete $httpProvider.defaults.headers.common['X-Requested-With'];
//$httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';		// TRY THIS ???????
///////////////////////////////
});

app.run(function($rootScope, RestData, AuthService)//, AUTH_EVENTS)
{
	$rootScope.$on('$routeChangeStart',
		function (event, next)
		{
			var authorizedRoles = (next.data) ? next.data.authorizedRoles: false;
			if (!AuthService.isAuthorized(authorizedRoles))
			{
				event.preventDefault();
				if (AuthService.isAuthenticated())
				{
					// user is not allowed
//					$rootScope.$broadcast(AUTH_EVENTS.notAuthorized);
				} else {
					// user is not logged in
//					$rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
				}
			}
		});

	$rootScope.categories = [];
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
			angular.forEach(response.data.categories,
				function(category)
				{
					$rootScope.categories.push(category)
				});
		});

	// get the categories
	RestData.getBankAccounts(
		function(response)
		{
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


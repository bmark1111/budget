//var app = angular.module('budgetApp', ['ngRoute', 'ngResource', 'ngContextMenu', 'ui.bootstrap']);
var app = angular.module('budgetApp', ['ngCookies', 'ngRoute', 'ngResource', 'ngContextMenu', 'ui.bootstrap']);

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
			if (AuthService.isAuthorized(authorizedRoles))
			{
				if (AuthService.isAuthenticated())
				{
					// load the upload counts
					if (typeof($rootScope.transaction_count) == 'undefined')
					{
						$rootScope.transaction_count = '';
						RestData(
							{
								Authorization:		"Basic " + btoa($rootScope.username + ':' + $rootScope.password),
								'TOKENID':			$rootScope.token_id,
								'X-Requested-With': 'XMLHttpRequest'
							})
							.getUploadCounts(
								function(response)
								{
									$rootScope.transaction_count = (parseInt(response.data.count) > 0) ? parseInt(response.data.count): '';
								},
								function (error)
								{
									$rootScope.error = error.status + ' ' + error.statusText;
								});
					}

					if (typeof($rootScope.categories) == 'undefined')
					{	// load the categories
						$rootScope.categories = [];
						RestData(
							{
								Authorization:		"Basic " + btoa($rootScope.username + ':' + $rootScope.password),
								'TOKENID':			$rootScope.token_id,
								'X-Requested-With': 'XMLHttpRequest'
							})
							.getCategories(
								function(response)
								{
									angular.forEach(response.data.categories,
										function(category)
										{
											$rootScope.categories.push(category)
										});
								},
								function (error)
								{
									$rootScope.error = error.status + ' ' + error.statusText;
								});
					}

					if (typeof($rootScope.bank_accounts) == 'undefined')
					{	// load the bank accounts
						$rootScope.bank_accounts = [];
						RestData(
							{
								Authorization:		"Basic " + btoa($rootScope.username + ':' + $rootScope.password),
								'TOKENID':			$rootScope.token_id,
								'X-Requested-With': 'XMLHttpRequest'
							})
							.getBankAccounts(
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
								},
								function (error)
								{
									$rootScope.error = error.status + ' ' + error.statusText;
								});
					}
				}

			} else {
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

});


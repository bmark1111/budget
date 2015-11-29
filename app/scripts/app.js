var app = angular.module('budgetApp', ['ngCookies', 'ngRoute', 'ngResource', 'ngContextMenu', 'ui.bootstrap', 'ngStorage', 'nsPopover']);

app.config(function($routeProvider, $httpProvider, $modalProvider, USER_ROLES) {

	$httpProvider.interceptors.push(function ($q, $localStorage, $location, $rootScope) {
//		$rootScope.dataErrorMsg = [];

		return {
			'response': function (response) {
				//Will only be called for HTTP up to 300
//console.log("SUCCESS")
//console.log(response);
//				if (response.success == 1)
//				{
					return response;
//				} else {
//					if (response.errors)
//					{
//						angular.forEach(response.errors,
//							function(error)
//							{
//								$rootScope.dataErrorMsg.push(error.error);
//							})
//					} else {
//						$rootScope.dataErrorMsg[0] = response;
//					}
//				}
			},
			'responseError': function (rejection) {
				if (rejection.status == '401' && rejection.statusText == 'EXPIRED') {
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.authorization		= false;
					$location.path("/login");
				} else {
					$rootScope.error = rejection.status + ' ' + rejection.statusText;
				}
				return $q.reject(rejection);
			}
		};
	});

	$routeProvider
		.when('/',
		{
			controller:		'HomeController',
			templateUrl:	'app/views/home.html'
		})
//		.when('/popoverdemo',
//		{
//			controller:		'PopoverdemoController',
//			templateUrl:	'app/views/popover_demo.html'
//		})
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
		.when('/budget',
		{
			controller:		'BudgetController',
			templateUrl:	'app/views/budget.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/sheet',
		{
			controller:		'SheetController',
			templateUrl:	'app/views/sheet.html',
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
		.when('/reconcile',
		{
			controller:		'ReconcileController',
			templateUrl:	'app/views/reconcile.html',
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
			controller:		'BankController',
			templateUrl:	'app/views/bank_settings.html',
			data:			{
								authorizedRoles: [USER_ROLES.admin, USER_ROLES.user]
							}
		})
		.when('/categories',
		{
			controller:		'CategoryController',
			templateUrl:	'app/views/categories.html',
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
		.when('/admin',
		{
			controller:		'AdminController',
			templateUrl:	"app/views/admin/admin.html",
			data:			{
								authorizedRoles: [USER_ROLES.admin]
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

app.run(function($route, $rootScope, $localStorage, $location, RestData2, AuthService) { //, AUTH_EVENTS)

	$route.reload(); 

	$rootScope.$on('$routeChangeStart',
		function (event, next) {
			$rootScope.nav_active		= $location.path().replace("/", "");
			$rootScope.error			= false;
			$rootScope.authenticated	= $localStorage.authenticated;
			$rootScope.userFullName		= $localStorage.userFullName;
//			$rootScope.accountBalancesResetDate = $localStorage.accountBalancesResetDate;

			var authorizedRoles = (next.data) ? next.data.authorizedRoles: false;
			if (AuthService.isAuthorized(authorizedRoles)) {
				if ($localStorage.authenticated) {
					// load the upload counts
					if (typeof($rootScope.transaction_count) === 'undefined') {
						$rootScope.transaction_count = '';
						RestData2().getUploadCounts(
							function(response) {
								$rootScope.transaction_count = (parseInt(response.data.count) > 0) ? parseInt(response.data.count): '';
							});
					}
				} else {
//					// user is not authenticated
					console.log('USER NOT AUTHENTICATED');
//					$rootScope.nav_active = 'login';
//					$location.path("/login");
				}
			} else {
				// role not authorized
				event.preventDefault();

				if ($localStorage.authenticated) {
					// user is not allowed
					console.log('ROLE NOT AUTHORIZED BUT AUTHENTICATED');
					$location.path("/dashboard");
				} else {
					// user is not logged in
					console.log('ROLE NOT AUTHORIZED AND  NOT AUTHENTICATED');
					$location.path("/");
				}
			}
		});

});
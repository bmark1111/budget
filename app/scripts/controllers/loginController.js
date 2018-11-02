'use strict';

app.controller('LoginController', function($rootScope, $scope, RestData2, $location, $localStorage, Accounts, Periods, Categories) {

	$rootScope.nav_active = 'login';

	$scope.error = false;
	$scope.errorMsg = null;

	$scope.credentials = {};

	var authenticate = function(credentials) {
		$localStorage.authorization = "Basic " + btoa(credentials.username + ":" + credentials.password);
		RestData2().login({account: credentials.account},
			function(data) {
				if (!!data.success && data.data.user) {
					// success
					$localStorage.authenticated		= true;
					$localStorage.authorizedRoles	= JSON.parse(data.data.user.roles);
					$localStorage.userFullName		= data.data.user.firstname + ' ' + data.data.user.lastname;
					$localStorage.token_id			= data.data.user.last_session_id;
					$localStorage.account_id		= data.data.account_id;
					$localStorage.authorization		= "Basic " + btoa(credentials.username + ":" + credentials.password);
					var dt = data.data.budget_start_date.split('-');
					$localStorage.budget_start_date	= new Date(dt[0], --dt[1], dt[2]);
					$localStorage.sheet_views		= data.data.sheet_views;
					$localStorage.budget_mode		= data.data.budget_mode;

					Accounts.clear();
					Periods.clear();
					Categories.clear();

					$location.path("/dashboard");
					$scope.error = false;
				} else {
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.account_id		= false;
					$localStorage.authorization		= false;
					$localStorage.budget_start_date	= false;
					$localStorage.sheet_views		= false;
					$localStorage.budget_mode		= false;

					$location.path("/login");
					$scope.error = true;
					$scope.errorMsg = data.errors[0].error;
				}
		//		callback && callback();
//			})
//			.error(function() {
//				$localStorage.authenticated		= false;
//				$localStorage.authorizedRoles	= false;
//				$localStorage.userFullName		= false;
//				$localStorage.token_id			= false;
//				$localStorage.authorization		= false;
//				$localStorage.budget_views		= false;
//				$localStorage.sheet_views		= false;
//
//				callback && callback();
			});
	}

	$scope.login = function() {
		authenticate($scope.credentials);
	};
});
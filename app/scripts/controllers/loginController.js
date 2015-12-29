'use strict';

app.controller('LoginController', function($rootScope, $scope, $http, RestData2, $location, $localStorage) {

	$rootScope.nav_active = 'login';

	$scope.error = false;

	var authenticate = function(credentials, callback) {
//		var headers = credentials ? {authorization : "Basic " + btoa(credentials.username + ":" + credentials.password)} : {};
//
//		$http.get('http://rest.budget.loc/login', {headers : headers})
//			.success(function(data) {
		$localStorage.authorization = "Basic " + btoa(credentials.username + ":" + credentials.password);
		RestData2().login(
			function(data) {
console.log(data)
				if (data.data.user) {
					$localStorage.authenticated		= true;
					$localStorage.authorizedRoles	= JSON.parse(data.data.user.roles);
					$localStorage.userFullName		= data.data.user.firstname + ' ' + data.data.user.lastname;
					$localStorage.token_id			= data.data.user.last_session_id;
					$localStorage.authorization		= "Basic " + btoa(credentials.username + ":" + credentials.password);
					$localStorage.budget_views		= data.data.budget_views;
					$localStorage.sheet_views		= data.data.sheet_views;

					$location.path("/dashboard");
					$scope.error = false;
				} else {
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.authorization		= false;
					$localStorage.budget_views		= false;
					$localStorage.sheet_views		= false;

					$location.path("/login");
					$scope.error = true;
				}
				callback && callback();
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

	$scope.credentials = {};

	$scope.login = function() {
			authenticate($scope.credentials);
		};
});

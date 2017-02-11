'use strict';

app.controller('LogoutController', function($scope, $localStorage, $location, RestData2) {

	$scope.dataErrorMsg = [];

	RestData2().logout({},
		function(response) {
			if (!!response.success)
			{
				$localStorage.authenticated		= false;
				$localStorage.authorizedRoles	= false;
				$localStorage.userFullName		= false;
				$localStorage.token_id			= false;
				$localStorage.account_id		= false;
				$localStorage.authorization		= false;
//				$localStorage.budget_views		= false;
				$localStorage.budget_start_date	= false;
				$localStorage.sheet_views		= false;
				$localStorage.budget_mode		= false;
				$location.path("/login");
			} else {
				if (response.errors) {
					angular.forEach(response.errors,
						function(error) {
							$scope.dataErrorMsg.push(error.error);
						})
				} else {
					$scope.dataErrorMsg[0] = response;
				}
			}
		});
});
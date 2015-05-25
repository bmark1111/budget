'use strict';

app.controller('LogoutController', function($scope, $rootScope, $localStorage, $location, $http)
{
	$rootScope.nav_active = 'logout';

	$http.post('http://rest.budget.loc/logout',
		{
			token_id:		$localStorage.token_id,
			userId:			$localStorage.userId
		})
		.success(function()
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.userId			= false;
					$localStorage.username			= false;
					$localStorage.password			= false;
					$location.path("/");
				})
		.error(function(data)
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.userId			= false;
					$localStorage.username			= false;
					$localStorage.password			= false;
				});
});
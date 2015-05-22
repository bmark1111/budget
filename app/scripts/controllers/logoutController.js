'use strict';

app.controller('LogoutController', function($scope, $rootScope, $location, $http)
{
	$rootScope.nav_active = 'logout';

	$http.post('http://rest.budget.loc/logout',
		{
			token_id:		$rootScope.token_id,
			userId:			$rootScope.userId
		})
		.success(function()
				{
					$rootScope.authenticated = false;
					$rootScope.authorizedRoles = false;
					$location.path("/");
				})
		.error(function(data)
				{
					$rootScope.authenticated = false;
					$rootScope.authorizedRoles = false;
				});
});
'use strict';

app.controller('LogoutController', function($scope, $rootScope, $localStorage, $location, $http)
{
	$rootScope.nav_active = 'logout';

	$http.post('http://rest.budget.loc/logout',
		{
			Authorization:		$localStorage.authorization,
			'TOKENID':			$localStorage.token_id,
			'X-Requested-With':	'XMLHttpRequest'
		})
		.success(function()
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.authorization		= false;
					$location.path("/");
				})
		.error(function(data)
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.authorization		= false;
				});
});
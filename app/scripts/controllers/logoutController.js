'use strict';

app.controller('LogoutController', function($scope, $rootScope, $localStorage, $location, $http)
{
	$rootScope.nav_active = 'logout';

	$http.post('http://rest.budget.loc/logout',
		{
			token_id:		$localStorage.token_id,
			userId:			$localStorage.userId,
			'ACCOUNTID':	$localStorage.account_id
		})
		.success(function()
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.account_id		= false;
					$localStorage.userId			= false;
					$localStorage.authorization		= false;
					$location.path("/");
				})
		.error(function(data)
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.account_id		= false;
					$localStorage.userId			= false;
					$localStorage.authorization		= false;
				});
});
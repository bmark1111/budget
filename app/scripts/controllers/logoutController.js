'use strict';

app.controller('LogoutController', function($scope, $localStorage, $location, RestData2)//, $http)
{
	$scope.dataErrorMsg = [];

		RestData2().logout({},
				function(response)
				{
					if (!!response.success)
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						if (response.errors)
						{
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
				});
//		$http.post('http://rest.budget.loc/logout',
//		{
//			Authorization:		$localStorage.authorization,
//			'TOKENID':			$localStorage.token_id,
//			'X-Requested-With':	'XMLHttpRequest'
//		})
//		.success(function()
//				{
//					$localStorage.authenticated		= false;
//					$localStorage.authorizedRoles	= false;
//					$localStorage.userFullName		= false;
//					$localStorage.token_id			= false;
//					$localStorage.authorization		= false;
//					$location.path("/login");
//				})
//		.error(function(data)
//				{
//					$localStorage.authenticated		= false;
//					$localStorage.authorizedRoles	= false;
//					$localStorage.userFullName		= false;
//					$localStorage.token_id			= false;
//					$localStorage.authorization		= false;
//				});
});
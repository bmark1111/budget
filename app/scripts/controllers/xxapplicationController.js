'use strict';

app.controller('ApplicationController', function ($scope, USER_ROLES, AuthService)
{
console.log ('ApplicationController');
	$scope.currentUser = null;
	$scope.userRoles = USER_ROLES;
	$scope.isAuthorized = AuthService.isAuthorized;

	$scope.setCurrentUser = function (user)
	{
console.log ('ApplicationController - setCurrentUser');
		$scope.currentUser = user;
	};
});
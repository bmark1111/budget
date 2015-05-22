'use strict';

app.controller('LoginController', function($scope, $rootScope, $window, AUTH_EVENTS, AuthService)
{
	$scope.credentials = {
			username: '',
			password: ''
		};

console.log('LoginController');
	$scope.login = function (credentials)
	{
		$rootScope.errorMsgs = [];
console.log('LoginController - login');
		AuthService.login(credentials).then(
			function (response)
			{
console.log(response);
				if (response.success == 1)
				{
console.log('LOGIN SUCCESS');
					$rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
//					$scope.setCurrentUser(response);
//					$window.location='/dashboard';
				} else {
console.log('LOGIN FAILURE');
					angular.forEach(response.errors,
						function(error)
						{
							$rootScope.errorMsgs.push(error.error);
						});
				}
			},
			function ()
			{
				$rootScope.$broadcast(AUTH_EVENTS.loginFailed);
			});
	};
});
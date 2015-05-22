'use strict';

app.controller('LoginController', function($rootScope, $scope, $http, $location)
{
	$rootScope.nav_active = 'login';

	$scope.error = false;

	var authenticate = function(credentials, callback)
						{
							var headers = credentials ? {authorization : "Basic " + btoa(credentials.username + ":" + credentials.password)} : {};

							$http.get('http://rest.budget.loc/login', {headers : headers})
								.success(function(data)
										{
											if (data.data.user)
											{
												$rootScope.authenticated	= true;
												$rootScope.authorizedRoles	= JSON.parse(data.data.user.roles);
												$rootScope.token_id			= data.data.user.last_session_id;
												$rootScope.userFullName		= data.data.user.firstname + ' ' + data.data.user.lastname;
												$rootScope.userId			= data.data.user.id;
											} else {
												$rootScope.authenticated	= false;
												$rootScope.authorizedRoles	= false;
												$rootScope.token_id			= false;
												$rootScope.userFullName		= false;
												$rootScope.userId			= false;
											}
											callback && callback();
										})
								.error(function()
										{
											$rootScope.authenticated	= false;
											$rootScope.authorizedRoles	= false;
											$rootScope.token_id			= false;
											$rootScope.userFullName		= false;
											$rootScope.userId			= false;
											callback && callback();
										});
						}

	$scope.credentials = {};
	$scope.login = function()
					{
						authenticate($scope.credentials,
							function()
							{
								if ($rootScope.authenticated)
								{
									$location.path("/dashboard");
									$scope.error = false;
								} else {
									$location.path("/login");
									$scope.error = true;
								}
							});
					};
});

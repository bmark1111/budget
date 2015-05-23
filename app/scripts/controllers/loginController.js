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
												$rootScope.userFullName		= data.data.user.firstname + ' ' + data.data.user.lastname;
												$rootScope.token_id			= data.data.user.last_session_id;
												$rootScope.userId			= data.data.user.id;
												$rootScope.username			= credentials.username;
												$rootScope.password			= credentials.password;
											} else {
												$rootScope.authenticated	= false;
												$rootScope.authorizedRoles	= false;
												$rootScope.userFullName		= false;
												$rootScope.token_id			= false;
												$rootScope.userId			= false;
												$rootScope.username			= false;
												$rootScope.password			= false;
											}
											callback && callback();
										})
								.error(function()
										{
											$rootScope.authenticated	= false;
											$rootScope.authorizedRoles	= false;
											$rootScope.userFullName		= false;
											$rootScope.token_id			= false;
											$rootScope.userId			= false;
											$rootScope.username			= false;
											$rootScope.password			= false;

											callback && callback();
										});
						}

	$scope.credentials = {};
	$scope.login = function()
					{
						authenticate($scope.credentials,
							function()
							{
console.log('longinController - response')
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

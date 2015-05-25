'use strict';

app.controller('LoginController', function($rootScope, $scope, $http, $location, $localStorage)
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
												$localStorage.authenticated		= true;
												$localStorage.authorizedRoles	= JSON.parse(data.data.user.roles);
												$localStorage.userFullName		= data.data.user.firstname + ' ' + data.data.user.lastname;
												$localStorage.token_id			= data.data.user.last_session_id;
												$localStorage.userId			= data.data.user.id;
												$localStorage.username			= credentials.username;
												$localStorage.password			= credentials.password;
											} else {
												$localStorage.authenticated		= false;
												$localStorage.authorizedRoles	= false;
												$localStorage.userFullName		= false;
												$localStorage.token_id			= false;
												$localStorage.userId			= false;
												$localStorage.username			= false;
												$localStorage.password			= false;
											}
											callback && callback();
										})
								.error(function()
										{
											$localStorage.authenticated		= false;
											$localStorage.authorizedRoles	= false;
											$localStorage.userFullName		= false;
											$localStorage.token_id			= false;
											$localStorage.userId			= false;
											$localStorage.username			= false;
											$localStorage.password			= false;

											callback && callback();
										});
						}

	$scope.credentials = {};
	$scope.login = function()
					{
						authenticate($scope.credentials,
							function()
							{
								if ($localStorage.authenticated)
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

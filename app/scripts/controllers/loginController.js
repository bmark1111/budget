'use strict';

app.controller('LoginController', function($rootScope, $scope, $http, $location, $localStorage)
{
	$rootScope.nav_active = 'login';

	$scope.error = false;

	var authenticate = function(credentials, callback)
						{
							var headers = credentials ? {authorization : "Basic " + btoa(credentials.username + ":" + credentials.password)} : {};
//							var headers = {
//									authorization : "Basic " + btoa(credentials.username + ":" + credentials.password),
//									'ACCOUNTID': $localStorage.account_id
//								}

							$http.get('http://rest.budget.loc/login', {headers : headers})
								.success(function(data)
										{
											if (data.data.user)
											{
												$localStorage.authenticated		= true;
												$localStorage.authorizedRoles	= JSON.parse(data.data.user.roles);
												$localStorage.userFullName		= data.data.user.firstname + ' ' + data.data.user.lastname;
												$localStorage.token_id			= data.data.user.last_session_id;
												$localStorage.authorization		= "Basic " + btoa(credentials.username + ":" + credentials.password);
											} else {
												$localStorage.authenticated		= false;
												$localStorage.authorizedRoles	= false;
												$localStorage.userFullName		= false;
												$localStorage.token_id			= false;
												$localStorage.authorization		= false;
											}
											callback && callback();
										})
								.error(function()
										{
											$localStorage.authenticated		= false;
											$localStorage.authorizedRoles	= false;
											$localStorage.userFullName		= false;
											$localStorage.token_id			= false;
											$localStorage.authorization		= false;

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

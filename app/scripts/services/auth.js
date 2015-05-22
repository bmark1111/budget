app.factory('AuthService', function ($rootScope, $http)//, Session)
{
	var authService = {};

	authService.login = function (credentials)
	{
		return $http
			.post('http://rest.budget.loc/login', credentials)
			.then(function (response)
				{
					if (response.data.success == 1)
					{
						Session.create(response.data.data.user.id, response.data.data.user.last_session_id, response.data.data.user.role.roles);
					}
					return response.data;
				});
	};

	authService.isAuthenticated = function ()
	{
//		return !!Session.userId;
		return $rootScope.authenticated;
	};

	authService.isAuthorized = function (authorizedRoles)
	{
		if (!authorizedRoles)
		{	// no roles defined so all allowed
			return true;
		}

		var auth = false;
		angular.forEach($rootScope.authorizedRoles,
			function(role)
			{
				if (authorizedRoles.indexOf(role) !== -1)
				{
					auth = true;
				}
			})
		return auth;
	};

	return authService;
});

//app.factory('AuthInterceptor', function ($rootScope, $q, AUTH_EVENTS)
//{
//	return {
//		responseError: function (response)
//		{
//			$rootScope.$broadcast({
//				401: AUTH_EVENTS.notAuthenticated,
//				403: AUTH_EVENTS.notAuthorized,
//				419: AUTH_EVENTS.sessionTimeout,
//				440: AUTH_EVENTS.sessionTimeout
//			}[response.status], response);
//
//			return $q.reject(response);
//		}
//	};
//});
//
//app.factory('AuthResolver', function ($q, $rootScope, $state)
//{
//	return {
//		resolve: function ()
//				{
//					var deferred = $q.defer();
//					var unwatch = $rootScope.$watch('currentUser',
//									function (currentUser)
//									{
//										if (angular.isDefined(currentUser))
//										{
//											if (currentUser)
//											{
//												deferred.resolve(currentUser);
//											} else {
//												deferred.reject();
//												$state.go('user-login');
//											}
//											unwatch();
//										}
//									});
//					return deferred.promise;
//				}
//	};
//});

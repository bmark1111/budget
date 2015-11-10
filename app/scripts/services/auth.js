app.factory('AuthService', function ($rootScope, $localStorage) {

	'use strict'

	var authService = {};

	authService.isAuthorized = function (authorizedRoles) {
		if (!authorizedRoles) {
			// no roles defined so all allowed
			return true;
		}

		$rootScope.is_admin = false;
		var auth = false;
		angular.forEach($localStorage.authorizedRoles,
			function(role) {
				if (authorizedRoles.indexOf(role) !== -1) {
					auth = true;
					if (role === 'admin') {
						$rootScope.is_admin = true;
					}
				}
			})
		return auth;
	};

	return authService;
});

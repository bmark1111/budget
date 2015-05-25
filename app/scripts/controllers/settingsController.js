'use strict';

app.controller('SettingsController', function($scope, $rootScope, $localStorage, $location, RestData)
{
//	$rootScope.nav_active = $location.path().replace("/", "");

//	ngProgress.start();

	RestData(
		{
			Authorization:		$localStorage.authorization,
//			Authorization:		"Basic " + btoa($localStorage.username + ':' + $localStorage.password),
			'TOKENID':			$localStorage.token_id,
			'X-Requested-With':	'XMLHttpRequest'
		})
		.getSetting(
			{
				type: $location.path().replace("/", "").replace("_settings", "")
			},
			function(response)
			{
				if (!!response.success)
				{

				} else {
					$scope.dataErrorMsg = '<p class="text-muted">' + response.errors[0];
				}
//				ngProgress.complete();
			},
			function (error)
			{
				if (error.status == '401' && error.statusText == 'EXPIRED')
				{
					$localStorage.authenticated		= false;
					$localStorage.authorizedRoles	= false;
					$localStorage.userFullName		= false;
					$localStorage.token_id			= false;
					$localStorage.userId			= false;
//					$localStorage.username			= false;
//					$localStorage.password			= false;
					$localStorage.authorization		= false;
					$location.path("/login");
				} else {
					$rootScope.error = error.status + ' ' + error.statusText;
				}
			});

});

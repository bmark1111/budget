'use strict';

app.controller('SettingsController', function($scope, $rootScope, RestData, $filter, $location)
{
	$rootScope.nav_active = $location.path().replace("/", "");

	RestData(
		{
			Authorization:		"Basic " + btoa($rootScope.username + ':' + $rootScope.password),
			'TOKENID':			$rootScope.token_id,
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
				$rootScope.error = error.status + ' ' + error.statusText;
			});

});

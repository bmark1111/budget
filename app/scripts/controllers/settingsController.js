'use strict';

app.controller('SettingsController', function($scope, $rootScope, RestData, $filter, $location)
{
//	$rootScope.nav_active = 'budget_settings';
	$rootScope.nav_active = $location.path().replace("/", "");

	RestData.getSetting(
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
//			ngProgress.complete();
		});

});

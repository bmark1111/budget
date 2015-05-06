'use strict';

app.controller('SettingsController', function($scope, RestData, $filter, $location)
{


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

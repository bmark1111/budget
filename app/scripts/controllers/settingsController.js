'use strict';

app.controller('SettingsController', function($scope, $location, RestData2)
{
	$scope.dataErrorMsg = [];

//	ngProgress.start();

	RestData2().getSetting(
			{
				type: $location.path().replace("/", "").replace("_settings", "")
			},
			function(response)
			{
				if (!!response.success)
				{

				} else {
					if (response.errors)
					{
						angular.forEach(response.errors,
							function(error)
							{
								$scope.dataErrorMsg.push(error.error);
							})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});

});

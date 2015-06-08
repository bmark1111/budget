'use strict';

app.controller('SettingsController', function($scope, $location, RestData2)//, $rootScope, $localStorage)
{
	$scope.dataErrorMsg = [];

//	ngProgress.start();

//	RestData(
//		{
//			Authorization:		$localStorage.authorization,
//			'TOKENID':			$localStorage.token_id,
//			'X-Requested-With':	'XMLHttpRequest'
//		})
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
//			},
//			function (error)
//			{
//				if (error.status == '401' && error.statusText == 'EXPIRED')
//				{
//					$localStorage.authenticated		= false;
//					$localStorage.authorizedRoles	= false;
//					$localStorage.userFullName		= false;
//					$localStorage.token_id			= false;
//					$localStorage.authorization		= false;
//					$location.path("/login");
//				} else {
//					$rootScope.error = error.status + ' ' + error.statusText;
//				}
			});

});

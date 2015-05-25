'use strict';

app.controller('DeleteModalController', function ($scope, $rootScope, $localStorage, $location, $modalInstance, RestData, params)
{
	$scope.title = params.title;
	$scope.message = params.msg;

	$scope.ok = function ()
	{
//		ngProgress.start();

		RestData(
			{
				Authorization:		"Basic " + btoa($localStorage.username + ':' + $localStorage.password),
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.deleteTransaction(
				{
					'id': params.id
				},
				function(response)
				{
					if (!!response.success)
					{
						$modalInstance.close();
					} else {
						if (response.errors)
						{
							$scope.dataErrorMsg = response.errors[0].error;
						} else {
							$scope.dataErrorMsg = response;
						}
					}
//					ngProgress.complete();
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
						$localStorage.username			= false;
						$localStorage.password			= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
				});
	};

	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};
});
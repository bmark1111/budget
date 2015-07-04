'use strict';

app.controller('DeleteBankModalController', function ($scope, $modalInstance, RestData2, params)
{
	$scope.dataErrorMsg	= [];
	$scope.title = params.title;
	$scope.message = params.msg;

	$scope.ok = function ()
	{
//		ngProgress.start();

		RestData2().deleteBank(
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
							angular.forEach(response.errors,
								function(error)
								{
									$scope.dataErrorMsg.push(error.error);
								})
						} else {
							$scope.dataErrorMsg[0] = response;
						}
					}
//					ngProgress.complete();
				});
	};

	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};
});
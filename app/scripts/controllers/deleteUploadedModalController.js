app.controller('DeleteUploadedModalController', function ($scope, $modalInstance, RestData, params)
{
	$scope.title = params.title;
	$scope.message = params.msg;

	$scope.ok = function ()
	{
//		ngProgress.start();

		RestData.deleteUploadedTransaction(
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
//				ngProgress.complete();
			});
	};

	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};
});
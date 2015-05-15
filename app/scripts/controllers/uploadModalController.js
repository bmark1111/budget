app.controller('UploadModalController', function ($scope, $rootScope, $modalInstance, fileUpload, params)
{
	$scope.ignoreFirstLine = 0;
	$scope.bank_account_id = 0;

	$scope.bank_accounts = $rootScope.bank_accounts;

	$scope.title = params.title;
	$scope.upload_errors = {};
	$scope.upload_fail = false;

	// save edited transaction
	$scope.upload = function ()
	{
		var file = $scope.myFile;

		var uploadUrl = 'http://rest.budget.loc/upload/' + $scope.bank_account_id + '/' + $scope.ignoreFirstLine;
		fileUpload.uploadFileToUrl(file, uploadUrl)
			.success(function(response)
			{
				if (response.success === 1)
				{
					$scope.upload_fail = false;
					$scope.upload_errors = {};
					$modalInstance.close(response);
				} else {
					$scope.upload_fail = true;
					$scope.upload_errors = response.errors;
				}
			});
	};

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

});
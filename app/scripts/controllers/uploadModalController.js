app.controller('UploadModalController', function ($scope, $modalInstance, fileUpload, params)
{

	$scope.title = params.title;
	$scope.upload_errors = {};
	$scope.upload_fail = false;

	// save edited transaction
	$scope.upload = function ()
	{
		var file = $scope.myFile;
//console.log('file is ' + JSON.stringify(file));
		var uploadUrl = 'http://rest.budget.loc/upload';
		fileUpload.uploadFileToUrl(file, uploadUrl)
			.success(function(response)
			{
//console.log(response);
				if (response.success === 1)
				{
//console.log('success');
					$scope.upload_fail = false;
					$scope.upload_errors = {};
					$modalInstance.close(response);
				} else {
//console.log('fail');
					$scope.upload_fail = true;
					$scope.upload_errors = response.errors;
//console.log($scope.upload_errors);
				}
			})
//			.error(function(response)
//			{
//console.log(response);
//console.log('fail');
//			});
	};

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

});
app.controller('UploadModalController', function ($scope, $modalInstance, fileUpload, params)
{

	$scope.title = params.title;

//	$scope.open = function($event)
//	{
//		$event.preventDefault();
//		$event.stopPropagation();
//
//		$scope.opened = true;
//	};

	// save edited transaction
	$scope.upload = function ()
	{
		var file = $scope.myFile;
console.log('file is ' + JSON.stringify(file));
		var uploadUrl = 'http://rest.budget.loc/upload';
		fileUpload.uploadFileToUrl(file, uploadUrl);
	};

	// cancel transaction edit
	$scope.cancel = function ()
	{
		$modalInstance.dismiss('cancel');
	};

});
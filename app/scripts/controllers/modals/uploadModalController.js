'use strict';

app.controller('UploadModalController', ['$q', '$scope', '$modalInstance', '$localStorage', 'fileUpload', 'params', 'Accounts', 'Config',

function ($q, $scope, $modalInstance, $localStorage, fileUpload, params, Accounts, Config) {

	$scope.ignoreFirstLine = true;
	$scope.bank_account_id = 0;

	$q.all([
		Accounts.get()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;
	});

	$scope.title = params.title;
	$scope.upload_errors = {};
	$scope.upload_fail = false;

	// upload transaction file
	$scope.upload = function () {
		if ($localStorage.authenticated) {
			var file = $scope.myFile;

			var uploadUrl = Config.get('upload_url') + $scope.bank_account_id + '/' + (($scope.ignoreFirstLine) ? 1: 0);
			fileUpload.uploadFileToUrl(file, uploadUrl)
				.success(function(response) {
					if (response.success === 1) {
						$scope.upload_fail = false;
						$scope.upload_errors = {};
						$modalInstance.close(response);
					} else {
						$scope.upload_fail = true;
						$scope.upload_errors = response.errors;
					}
				});
		}
	};

	// cancel transaction edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
}]);
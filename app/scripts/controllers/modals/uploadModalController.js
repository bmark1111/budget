'use strict';

app.controller('UploadModalController', ['$q', '$scope', '$rootScope', '$modalInstance', '$localStorage', 'fileUpload', 'params', 'BankAccounts', 'Config',

	function ($q, $scope, $rootScope, $modalInstance, $localStorage, fileUpload, params, BankAccounts, Config) {

		$scope.ignoreFirstLine = 0;
		$scope.bank_account_id = 0;

		$q.all([
			BankAccounts.get()
		]).then(function(response) {
			// get the bank account
			if (!!response[0].success) {
				$rootScope.bank_accounts = [];
				angular.forEach(response[0].data.bank_accounts,
					function(bank_account) {
						$rootScope.bank_accounts.push({
							'id': bank_account.id,
							'name': bank_account.bank.name + ' ' + bank_account.name
						})
					});
			}
		});

		$scope.title = params.title;
		$scope.upload_errors = {};
		$scope.upload_fail = false;

		// upload transaction file
		$scope.upload = function () {
			if ($localStorage.authenticated) {
				var file = $scope.myFile;

//				var uploadUrl = API.upload_url + $scope.bank_account_id + '/' + $scope.ignoreFirstLine;
				var uploadUrl = Config.get('upload_url') + $scope.bank_account_id + '/' + $scope.ignoreFirstLine;
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
	}
]);
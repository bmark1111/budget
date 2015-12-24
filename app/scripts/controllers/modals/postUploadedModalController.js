'use strict';

app.controller('PostUploadedModalController', ['$q', '$scope', '$rootScope', '$localStorage', '$modalInstance', 'RestData2', 'params', 'Categories', 'BankAccounts', function($q, $scope, $rootScope, $localStorage, $modalInstance, RestData2, params, Categories, BankAccounts) {

	$scope.uploaded = {
			splits: {}
		};

	$scope.title = params.title;
	$scope.post = 'Post New';

	$scope.dataErrorMsg = [];

	var getTransaction = function() {
		var deferred = $q.defer();
		var result = RestData2().getUploadedTransaction({id: params.id},
			function(response) {
				deferred.resolve(result);
			},
			function(err) {
				deferred.resolve(err);
			});
		return deferred.promise;
	};

	$q.all([
		BankAccounts.get(),
		Categories.get(),
		getTransaction()
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
		// load the categories
		if (!!response[1].success) {
			$rootScope.categories = [];
			angular.forEach(response[1].data.categories,
				function(category) {
					$rootScope.categories.push(category)
				});
		}
		// load the transaction
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.uploaded = response[2].data.result;
				$scope.transactions = response[2].data.transactions;
				$scope.transactions_seq = Object.keys(response[2].data.transactions);
			}
//		} else {
//			if (response[2].errors) {
//				angular.forEach(response[2].errors,
//					function(error) {
//						$scope.dataErrorMsg.push(error.error);
//					})
//			} else {
//				$scope.dataErrorMsg[0] = response[2];
//			}
		}
	});

	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	// post uploaded uploaded
	$scope.postUploaded = function () {
		$scope.dataErrorMsg = [];

		$scope.validation = {};

		$scope.uploaded.transaction_id = $scope.idSelectedTransaction;

		RestData2().postUploadedTransaction($scope.uploaded,
			function(response) {
				if (!!response.success) {
					$modalInstance.close();
					// now update the global intervals data
					delete $rootScope.intervals;
					delete $rootScope.periods;
				} else if (response.validation) {
					angular.forEach(response.validation,
						function(validation) {
							switch (validation.fieldName) {
								case 'category_id':
									$scope.validation.category_id = validation.errorMessage;
									break;
								default:
									break;
							}
						});
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								$scope.dataErrorMsg.push(error.error);
							})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});
	};

	$scope.deleteUploaded = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().deleteUploadedTransaction({
				'id': params.id
			},
			function(response) {
				if (!!response.success) {
					$modalInstance.close();
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								$scope.dataErrorMsg.push(error.error);
							})
					} else {
						$scope.dataErrorMsg[0] = response;
					}
				}
//				ngProgress.complete();
			});
	};

	// cancel uploaded transactionedit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

	$scope.idSelectedTransaction = null;
	$scope.setSelected = function (idSelectedTransaction) {
		if ($scope.idSelectedTransaction !== idSelectedTransaction) {
			$scope.idSelectedTransaction = idSelectedTransaction;
			$scope.post = 'Post New & Overwrite';
		} else {
			$scope.idSelectedTransaction = null;
			$scope.post = 'Post New';
		}
	};

}]);
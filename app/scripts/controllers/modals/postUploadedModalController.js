'use strict';

app.controller('PostUploadedModalController', ['$q', '$scope', '$rootScope', '$modalInstance', 'RestData2', 'params', 'Categories', 'BankAccounts', function($q, $scope, $rootScope, $modalInstance, RestData2, params, Categories, BankAccounts) {

	$scope.uploaded = {
			splits: {}
		};
//	$scope.categories = [];
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

//	var getCategories = function() {
//		var deferred = $q.defer();
//
//		RestData2().getCategories().$promise.then(
//			function(results) {
//				deferred.resolve(results);
//			},
//			function(err) {
//				deferred.resolve(err);
//			}
//		);
//
//		return deferred.promise;
//	};
//
//	if (typeof($rootScope.categories) === 'undefined') {
//		// first check to see if we need to load the categories
//		var categoryPromise = getCategories();
//		categoryPromise.then(
//			function (categoryPromiseResult) {
//				if (categoryPromiseResult.data.categories) {
//					$rootScope.categories = [];
//					angular.forEach(categoryPromiseResult.data.categories,
//						function(category) {
//							$rootScope.categories.push(category)
//						});
//				}
//
//				// now get the YTD totals
//				getTransaction();
//			});
//	} else {
//		getTransaction();
//	}

	$q.all([
		BankAccounts.get(),	//getBankAccounts(),
		Categories.get(),	//getCategories(),
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
//					ngProgress.complete();
				});
	};

	$scope.deleteUploaded = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().deleteUploadedTransaction(
				{
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
//					ngProgress.complete();
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
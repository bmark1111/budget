'use strict';

app.controller('PostUploadedModalController', ['$q', '$scope', '$modalInstance', '$modal', 'RestData2', 'params', 'Categories', 'Accounts', 'Periods',
	
function($q, $scope, $modalInstance, $modal, RestData2, params, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];
	$scope.isSaving = false;

	$scope.title = params.title;
	$scope.post = 'Post New';

	$scope.transaction = {
			splits: {},
			vendor: {}
		};

	$scope.is_split = false;

	$scope.repeats_seq = false;
	$scope.repeats = false;

	//**********************//
	// Live Search			//
	//**********************//

	$scope.$on('liveSearchSelect', function (event, result) {
		if (result.table && result.index) {
			$scope.transaction[result.table][result.index][result.model] = result.result.id;
		} else {
			$scope.transaction[result.model] = result.result.id;
		}
	});

	$scope.$on('liveSearchBlur', function(event, result) {
		if (!result.id && result.name) {
			// nothing has been selected but a name has been entered, so lets see if an new payer/payee should be added
			var modalInstance = $modal.open({
				templateUrl: 'app/views/templates/editVendorModal.html',
				controller: 'EditVendorModalController',
				windowClass: 'app-modal-window',
				resolve: {
					params: function() {
								return {
									name: result.name
								}
							}
				}
			});

			modalInstance.result.then(
				function (response) {
					if (result.table && result.index) {
						$scope.transaction[result.table][result.index][result.model] = response.data.id;
					} else {
						$scope.transaction[result.model] = response.data.id;
					}
				},
				function () {
					console.log('Add Vendor Modal dismissed at: ' + new Date());
					if (result.table && result.index) {
						$scope.transaction[result.table][result.index][result.model] = null;
					} else {
						$scope.transaction[result.model] = null;
					}
			});
		}
	});

	//******************************//
	// Post Uploaded Transaction	//
	//******************************//

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
		Accounts.get(),
		Categories.get(),
		getTransaction()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;
		// load the categories
		$scope.categories = Categories.data;
		// load the transaction
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.transaction = response[2].data.result;
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
		$scope.isSaving = true;

		$scope.validation = {};

		$scope.transaction.transaction_id = $scope.idSelectedTransaction;

		RestData2().postUploadedTransaction($scope.transaction,
			function(response) {
				$scope.isSaving = false;
				if (!!response.success) {
					if (response.data && response.data.repeats) {
						// mulitple repeats found
						$scope.repeats_seq = Object.keys(response.data.repeats);
						$scope.repeats = response.data.repeats;
					} else {
						$modalInstance.close();
						// now update the periods data
						Periods.clear();
					}
				} else if (response.validation) {
					angular.forEach(response.validation, function(validation) {
						switch (validation.fieldName) {
							case 'vendor_id':
								$scope.validation.vendor_id = validation.errorMessage;
								break;
							case 'bank_account_id':
								$scope.validation.bank_account_id = validation.errorMessage;
								break;
							case 'transfer_account_id':
								$scope.validation.transfer_account_id = validation.errorMessage;
								break;
							case 'transaction_date':
								$scope.validation.transaction_date = validation.errorMessage;
								break;
							case 'description':
								$scope.validation.description = validation.errorMessage;
								break;
							case 'category_id':
								$scope.validation.category_id = validation.errorMessage;
								break;
							case 'type':
								$scope.validation.type = validation.errorMessage;
								break;
							case 'amount':
								$scope.validation.amount = validation.errorMessage;
								break;
							case 'splits':
								$scope.validation.splits = validation.errorMessage;
								break;
							default:
								if (validation.fieldName.substr(0,6) == 'splits') {
									var fieldName = validation.fieldName;
									var matches = fieldName.match(/\[(.*?)\]/g);
									if (matches) {
										for (var x = 0; x < matches.length; x++) {
											matches[x] = matches[x].replace(/\]/g, '').replace(/\[/g, '');
										}
										if (typeof $scope.validation.splits[matches[1]] == 'undefined') {
											$scope.validation.splits[matches[1]] = Array();
										}
										$scope.validation.splits[matches[1]].push(validation.errorMessage);
									} else {
										$scope.validation[fieldName] = validation.errorMessage;
									}
								}
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
	$scope.setSelected = function (selectedTransaction) {
		if ($scope.idSelectedTransaction !== selectedTransaction.id) {
			// select this transaction
			if (selectedTransaction.reconciled_date) {
				// this is a reconciled transaction, you may want to discard this new uploaded transaction
				$scope.dataErrorMsg = ['This is a reconciled transaction, you may want to discard this new uploaded transaction'];
				$scope.idSelectedTransaction = null;
				$scope.post = 'Post New';
				$scope.transaction.vendor_id = false;
				$scope.transaction.category_id = false;
				$scope.transaction.notes = '';
			} else {
				$scope.idSelectedTransaction = selectedTransaction.id;
				$scope.post = 'Post New & Overwrite';
				if (selectedTransaction.category_id && selectedTransaction.vendor_id) {
					$( "#liveSearch" ).find('input').val(selectedTransaction.vendor.display_name);
					$scope.is_split = false;
					$scope.transaction.vendor_id = selectedTransaction.vendor_id;
					$scope.transaction.category_id = selectedTransaction.category_id;
				} else {
					$scope.is_split = true;
					$scope.transaction.vendor_id = false;
					$scope.transaction.category_id = false;
					$scope.transaction.splits = selectedTransaction.splits
				}
				$scope.transaction.notes = selectedTransaction.notes;
			}
		} else {
			// deselect this transaction
			$scope.idSelectedTransaction = null;
			$scope.post = 'Post New';
			$scope.transaction.category_id = false;
			$scope.transaction.notes = '';
		}
	};

	$scope.setRepeat = function (selectedRepeat) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/setRepeatTransactionModal.html',
			controller: 'SetRepeatTransactionModalController',
			size: 'sm',
			resolve: {
				params: function() {
						return {
							id: selectedRepeat.id,
							title: 'Set Repeat Transaction ?',
							msg: 'Are you sure you want to set this as the repeat this transaction. This action cannot be undone.'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Modal dismissed at: ' + new Date());
		});
	};

}]);
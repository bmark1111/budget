'use strict';

app.controller('EditModalController', ['$q', '$scope', '$modalInstance', '$modal', 'RestData2', 'params', 'Categories', 'Accounts', 'Periods',

function($q, $scope, $modalInstance, $modal, RestData2, params, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];

	$scope.transaction = {
			splits: {},
			vendor: {}
		};

	$scope.title = params.title;

	$scope.minDate = null;
	$scope.maxDate = null;
	$scope.opened = false;
	$scope.is_split = false;
	$scope.isSaving = false;

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
			// nothing has been selected but a name has been entered, so lets see if a new payer/payee should be added
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
					$scope.transaction.vendor.display_name = response.data.display_name;
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

	//**********************//
	// Edit Transaction		//
	//**********************//

	var getTransaction = function() {
		var deferred = $q.defer();
		if (params.id > 0) {	// if we are editing a transaction - get it from the REST
			var result = RestData2().editTransaction({ id: params.id},
				function(response) {
					deferred.resolve(result);
				},
				function(err) {
					deferred.resolve(err);
				});
		} else {
			deferred.resolve(true);
		}
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
				var dt = $scope.transaction.transaction_date.split('-');
				$scope.transaction.transaction_date = new Date(dt[0], --dt[1], dt[2]);
				if ($scope.transaction.splits) {
					$scope.is_split = true;
				}
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

	/**
	 * @name _addZero
	 * @desc local function to add leading zeros to time parameters
	 * @type {Function}
	 * @param {hours | minutes}
	 * @return {string} hours or minutes woth leading zeros
	 */
	var _addZero = function(i) {
		if (i < 10) {
			i = "0" + i;
		}
		return i;
	}

	// save edited transaction
	$scope.save = function () {
		$scope.dataErrorMsg = [];
		$scope.isSaving = true;

		$scope.validation = {};
		if ($scope.transaction.transaction_date) {
			var dt = new Date($scope.transaction.transaction_date);
			$scope.transaction.transaction_date = dt.getFullYear() + '-' + _addZero(dt.getMonth()+1) + '-' + _addZero(dt.getDate());
		}
		RestData2().saveTransaction($scope.transaction,
			function(response) {
				$scope.isSaving = false;
				if (!!response.success) {
					$modalInstance.close();
					// now update the periods data
					Periods.clear();
				} else if (response.validation) {
					$scope.validation.splits = {};
					angular.forEach(response.validation,
						function(validation) {
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

	// cancel transaction edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};
}]);
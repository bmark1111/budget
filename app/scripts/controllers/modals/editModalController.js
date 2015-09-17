'use strict';

app.controller('EditModalController', ['$q', '$scope', '$rootScope', '$modalInstance', 'RestData2', 'params', 'Categories', 'BankAccounts', function($q, $scope, $rootScope, $modalInstance, RestData2, params, Categories, BankAccounts) {

	$scope.transaction = {
			splits: {}
		};

	$scope.title = params.title;
	
	$scope.dataErrorMsg = [];

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

//	var getCategories = function() {
//		var deferred = $q.defer();
//		if (typeof($rootScope.categories) === 'undefined') {
//			var result = RestData2().getCategories(
//				function(response) {
//					deferred.resolve(result);
//				},
//				function(err) {
//					deferred.resolve(err);
//				});
//		} else {
//			deferred.resolve(true);
//		}
//		return deferred.promise;
//	};

//	var getBankAccounts = function() {
//		var deferred = $q.defer();
//		if (typeof($rootScope.bank_accounts) === 'undefined') {
//			var result = RestData2().getBankAccounts(
//				function(response) {
//					deferred.resolve(result);
//				},
//				function(err) {
//					deferred.resolve(err);
//				});
//		} else {
//			deferred.resolve(true);
//		}
//		return deferred.promise;
//	};

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
				$scope.transaction = response[2].data.result;
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

	// save edited transaction
	$scope.save = function () {
		$scope.dataErrorMsg = [];

		$scope.validation = {};

		RestData2().saveTransaction($scope.transaction,
				function(response) {
					if (!!response.success) {
						$modalInstance.close();
						// now update the global intervals data
						delete $rootScope.intervals;
					} else if (response.validation) {
						$scope.validation.splits = {};
						angular.forEach(response.validation,
							function(validation) {
								switch (validation.fieldName) {
									case 'bank_account_id':
										$scope.validation.bank_account_id = validation.errorMessage;
										break;
									case 'transaction_date':
										$scope.validation.date = validation.errorMessage;
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
//					ngProgress.complete();
				});
	};

	// cancel transaction edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

	// split transaction
	$scope.split = function() {
		if (Object.size($scope.transaction.splits) == 0 && $scope.transaction.amount > 0 && typeof($scope.transaction.type) != 'undefined') {
			var newItem = {
				amount:			'',
				type:			$scope.transaction.type,
				category_id:	'',
				notes:			''
			}
			newItem.amount = $scope.transaction.amount;
			$scope.transaction.splits = {};
			$scope.transaction.splits[0] = newItem;
		}
	};

	$scope.refreshSplits = function() {
		if (Object.size($scope.transaction.splits) > 0) {
			var newItem = {
				amount:			'',
				type:			$scope.transaction.type,
				category_id:	'',
				notes:			''
			}
			// calculate total of all splits
			var split_total = parseFloat(0);
			angular.forEach($scope.transaction.splits,
				function(split) {
					if (split.is_deleted != 1) {
						switch (split.type) {
							case 'DEBIT':
							case 'CHECK':
								split_total -= parseFloat(split.amount);
								break;
							case 'CREDIT':
							case 'DSLIP':
								split_total += parseFloat(split.amount);
								break;
						}
					}
				});

			$scope.calc = Array();
			var yy = Object.keys($scope.transaction.splits).length
			switch ($scope.transaction.type) {
				case 'CREDIT':
				case 'DSLIP':
					var transaction_amount = parseFloat($scope.transaction.amount);
					var split_total = parseFloat(split_total);
					var new_amount_type = 'DEBIT';
					break;
				case 'DEBIT':
				case 'CHECK':
					var transaction_amount = parseFloat($scope.transaction.amount);
					var split_total = -parseFloat(split_total);
					var new_amount_type = 'CREDIT';
				break;
			}
			if (transaction_amount != split_total) {
				newItem.amount = $scope.transaction.amount - split_total
				if (newItem.amount < 0) {
					newItem.amount = -parseFloat(newItem.amount);
					newItem.type = new_amount_type;
				}
				$scope.transaction.splits[yy] = newItem;
			}
		}
	};

	$scope.deleteSplit = function(ele) {
		$scope.transaction.splits[ele].is_deleted = 1;

		// calculate split_total of all splits
		var split_total = parseFloat(0);
		angular.forEach($scope.transaction.splits,
			function(split) {
				if (split.is_deleted != 1) {
					split_total += parseFloat(split.amount);
				}
			});
		$scope.calc = Array();
		if ($scope.transaction.amount != split_total) {
			$scope.calc[ele-1] = 'Split amounts do not match Item amount';
		}
	};

	Object.size = function(obj) {
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	};

}]);
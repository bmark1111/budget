'use strict';

app.controller('EditRepeatModalController', ['$q', '$scope', '$modalInstance', '$modal', 'RestData2', 'params', 'Categories', 'Accounts', 'Periods',

function($q, $scope, $modalInstance, $modal, RestData2, params, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];

	$scope.transaction = {
		splits: {},
		repeats: {
			0:{
				every_day:	false,
				every_date:	'',
				every_month: false}
		},
		every_unit: ''
	};

	$scope.opened = {
		first_due_date: false,
		last_due_date: false,
		next_due_date: false
	};

	$scope.minDate = null;
	$scope.maxDate = null;
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
				templateUrl: 'editVendorModal.html',
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

	//**********************//
	// Edit Repeat			//
	//**********************//

	var getRepeat = function() {
		var deferred = $q.defer();
		if (params.id > 0) {	// if we are editing a repeat - get it from the REST
			var result = RestData2().editRepeat({ id: params.id},
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
		getRepeat()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		// load the categories
		$scope.categories = Categories.data;
		// load the repeat
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.transaction = response[2].data.result;
				var dt = $scope.transaction.first_due_date.split('-');
				$scope.transaction.first_due_date = new Date(dt[0], --dt[1], dt[2]);
				if ($scope.transaction.last_due_date) {
					dt = $scope.transaction.last_due_date.split('-');
					$scope.transaction.last_due_date = new Date(dt[0], --dt[1], dt[2]);
				}
				dt = $scope.transaction.next_due_date.split('-');
				$scope.transaction.next_due_date = new Date(dt[0], --dt[1], dt[2]);
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

	$scope.open = function($event, date_type) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened.first_due_date = false;
		$scope.opened.last_due_date = false;
		$scope.opened.next_due_date = false;
		$scope.opened[date_type] = true;
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

	// save repeat
	$scope.save = function () {
		$scope.dataErrorMsg = [];
		$scope.isSaving = true;

		$scope.validation = {};
		if ($scope.transaction.first_due_date) {
			var dt = new Date($scope.transaction.first_due_date);
			$scope.transaction.first_due_date = dt.getFullYear() + '-' + _addZero(dt.getMonth()+1) + '-' + _addZero(dt.getDate());
		}
		if ($scope.transaction.last_due_date) {
			var dt = new Date($scope.transaction.last_due_date);
			$scope.transaction.last_due_date = dt.getFullYear() + '-' + _addZero(dt.getMonth()+1) + '-' + _addZero(dt.getDate());
		}
		if ($scope.transaction.next_due_date) {
			var dt = new Date($scope.transaction.next_due_date);
			$scope.transaction.next_due_date = dt.getFullYear() + '-' + _addZero(dt.getMonth()+1) + '-' + _addZero(dt.getDate());
		}
		RestData2().saveRepeat($scope.transaction,
			function(response) {
				$scope.isSaving = false;
				if (!!response.success) {
					$modalInstance.close(response);
					// now update the periods data
					Periods.clear();
				} else if (response.validation) {
					angular.forEach(response.validation,
						function(validation) {
							switch (validation.fieldName) {
								case 'description':
								case 'category_id':
								case 'bank_account_id':
								case 'vendor_id':
								case 'first_due_date':
								case 'last_due_date':
								case 'next_due_date':
								case 'type':
								case 'every_unit':
								case 'every':
								case 'amount':
									$scope.validation[validation.fieldName] = validation.errorMessage;
									break;
								default:
									var validationType = validation.fieldName.split('[');
									if (validationType[0] === 'splits' || validationType[0] === 'repeats') {
										var fieldName = validation.fieldName;
										var matches = fieldName.match(/\[(.*?)\]/g);
										if (matches) {
											for (var x = 0; x < matches.length; x++) {
												matches[x] = matches[x].replace(/\]/g, '').replace(/\[/g, '');
											}
											if (typeof $scope.validation[validationType[0]] === 'undefined') {
												$scope.validation[validationType[0]] = Array();
												$scope.validation[validationType[0]][matches[1]] = Array();
											}
											else if (typeof $scope.validation[validationType[0]][matches[1]] === 'undefined') {
												$scope.validation[validationType[0]][matches[1]] = Array();
											}
											$scope.validation[validationType[0]][matches[1]].push(validation.errorMessage);
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

	// cancel repeat edit
	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

}]);
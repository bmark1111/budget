'use strict';

app.controller('EditRepeatModalController', ['$q', '$scope', '$modalInstance', '$modal', 'RestData2', 'params', 'Categories', 'Accounts', 'Periods',

function($q, $scope, $modalInstance, $modal, RestData2, params, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];

	$scope.transaction = {
		every_unit: ''
	};

	$scope.opened = {
		first_due_date: false,
		last_due_date: false,
		last_due_date: false
	};

	$scope.minDate = null;
	$scope.maxDate = null;
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
		$scope.active_accounts = Accounts.active;
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
				$scope.transaction.every = parseInt($scope.transaction.every, 10);
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
								case 'everyDay':
								case 'day':
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
//				ngProgress.complete();
			});
	};

	// cancel repeat edit
	$scope.cancel = function () {
		
		$modalInstance.dismiss('cancel');
	};

	$scope.previousDueDate = function() {

		var dd, day, mnth, year;
		if ($scope.transaction.next_due_date) {
			var ndd = $scope.transaction.next_due_date;
			switch ($scope.transaction.every_unit) {
				case 'Day':
					ndd.setDate(ndd.getDate() - (1 * $scope.transaction.every));
					break;
				case 'Week':
					ndd.setDate(ndd.getDate() - (7 * $scope.transaction.every));
					break;
				case 'Month':
					dd = ndd.toISOString().split('T')[0].split('-');
					mnth = parseInt(dd[1], 10) - parseInt($scope.transaction.every, 10);
					if ((typeof $scope.transaction.everyDay === 'undefined' || $scope.transaction.everyDay === null) && (typeof $scope.transaction.day === 'undefined' || $scope.transaction.day === null)) {
						year = dd[0];
						day = dd[2];
					} else {
						if (mnth < 1) {
							year = parseInt(--dd[0], 10);
							mnth = mnth + 12;
						} else {
							year = parseInt(dd[0], 10);
						}
						day = _getMonthlyWeekday($scope.transaction.everyDay, $scope.transaction.day , mnth, year);
					}
					ndd = new Date(year, --mnth, day, 0, 0, 0, 0);
					break;
				case 'Year':
					dd = ndd.toISOString().split('T')[0].split('-');
					year = parseInt(dd[0], 10) - parseInt($scope.transaction.every, 10);
					ndd = new Date(year, --dd[1], dd[2], 0, 0, 0, 0);
					break;
			}
			if (ndd >= $scope.transaction.first_due_date) {
				$scope.transaction.next_due_date = ndd;
			}
		}
	};

	$scope.nextDueDate = function() {

		var dd, day, mnth, year;
		if ($scope.transaction.next_due_date) {
			var ndd = $scope.transaction.next_due_date;
			switch ($scope.transaction.every_unit) {
				case 'Day':
					ndd.setDate(ndd.getDate() + (1 * $scope.transaction.every));
					break;
				case 'Week':
					ndd.setDate(ndd.getDate() + (7 * $scope.transaction.every));
					break;
				case 'Month':
					dd = ndd.toISOString().split('T')[0].split('-');
					mnth = parseInt(dd[1], 10) + parseInt($scope.transaction.every, 10);
					if ((typeof $scope.transaction.everyDay === 'undefined' || $scope.transaction.everyDay === null) && (typeof $scope.transaction.day === 'undefined' || $scope.transaction.day === null)) {
						year = dd[0];
						day = dd[2];
					} else {
						if (mnth > 12) {
							year = parseInt(++dd[0], 10);
							mnth = mnth - 12;
						} else {
							year = parseInt(dd[0], 10);
						}
						day = _getMonthlyWeekday($scope.transaction.everyDay, $scope.transaction.day , mnth, year);
					}
					ndd = new Date(year, --mnth, day, 0, 0, 0, 0);
					break;
				case 'Year':
					dd = ndd.toISOString().split('T')[0].split('-');
					year = parseInt(dd[0], 10) + parseInt($scope.transaction.every, 10);
					ndd = new Date(year, --dd[1], dd[2], 0, 0, 0, 0);
					break;
			}
			if (!$scope.transaction.last_due_date || ndd <= $scope.transaction.last_due_date) {
				$scope.transaction.next_due_date = ndd;
			}
		} else if ($scope.transaction.first_due_date) {
			$scope.transaction.next_due_date = new Date($scope.transaction.first_due_date.getFullYear(), $scope.transaction.first_due_date.getMonth(), $scope.transaction.first_due_date.getDate());
		}
	};

	/* JavaScript getMonthlyWeekday Function:
	 * Written by Ian L. of Jafty.com
	 *
	 * Description:
	 * Gets Nth weekday for given month/year. For example, it can give you the date of the first monday in January, 2017 or it could give you the third Friday of June, 1999. Can get up to the fifth weekday of any given month, but will return FALSE if there is no fifth day in the given month/year.
	 *
	 *
	 * Parameters:
	 *    n = 'first', 'second', 'third' or 'fourth' weekday of the month
	 *    d = full spelled out weekday Sunday - Saturday
	 *    m = 0 - 11 for month
	 *    y = Four digit representation of the year like 2017
	 *
	 * Return Values:
	 * returns 1-31 for the date of the queried month/year that the nth weekday falls on.
	 * returns false if there isn't an nth weekday in the queried month/year
	*/
	var _getMonthlyWeekday = function(n, d, m, y) {

		var targetDay, curDay = 0, i = 1, seekDay, seekInstance, seekMnth;
		
		switch (n) {
			case 'first':
				seekInstance = 1;
				break;
			case 'second':
				seekInstance = 2;
				break;
			case 'third':
				seekInstance = 3;
				break;
			case 'fourth':
				seekInstance = 4;
				break;
		}

		switch (d) {
			case "Sunday":
				seekDay = 0;
				break;
			case "Monday":
				seekDay = 1;
				break;
			case "Tuesday":
				seekDay = 2;
				break;
			case "Wednesday":
				seekDay = 3;
				break;
			case "Thursday":
				seekDay = 4;
				break;
			case "Friday":
				seekDay = 5;
				break;
			case "Saturday":
				seekDay = 6;
				break;
		}

		switch (m) {
			case 1:
				seekMnth = 'January';
				break;
			case 2:
				seekMnth = 'February';
				break;
			case 3:
				seekMnth = 'March';
				break;
			case 4:
				seekMnth = 'April';
				break;
			case 5:
				seekMnth = 'May';
				break;
			case 6:
				seekMnth = 'June';
				break;
			case 7:
				seekMnth = 'July';
				break;
			case 8:
				seekMnth = 'August';
				break;
			case 9:
				seekMnth = 'September';
				break;
			case 10:
				seekMnth = 'October';
				break;
			case 11:
				seekMnth = 'November';
				break;
			case 12:
				seekMnth = 'December';
				break;
		}

		while (curDay < seekInstance && i < 31) {
			targetDay = new Date(i++ + " " + seekMnth + " " + y);
			if (targetDay.getDay() == seekDay) {
				curDay++;
			}
		}
		if (curDay == seekInstance) {
			targetDay = targetDay.getDate();
			return targetDay;
		} else {
			return false;
		}
	};

	$scope.deleteDueDate = function() {
		
		$scope.transaction.next_due_date = null;
	};
	
	$scope.previousLastDueDate = function() {
		
		var dd, mnth, day, year;
		if ($scope.transaction.last_due_date) {
			var ldd = $scope.transaction.last_due_date;
			switch ($scope.transaction.every_unit) {
				case 'Day':
					ldd.setDate(ldd.getDate() - (1 * $scope.transaction.every));
					break;
				case 'Week':
					ldd.setDate(ldd.getDate() - (7 * $scope.transaction.every));
					break;
				case 'Month':
					dd = ldd.toISOString().split('T')[0].split('-');
					mnth = parseInt(dd[1], 10) - parseInt($scope.transaction.every, 10);
					if ((typeof $scope.transaction.everyDay === 'undefined' || $scope.transaction.everyDay === null) && (typeof $scope.transaction.day === 'undefined' || $scope.transaction.day === null)) {
						year = dd[0];
						day = dd[2];
					} else {
						if (mnth < 1) {
							year = parseInt(--dd[0], 10);
							mnth = mnth + 12;
						} else {
							year = parseInt(dd[0], 10);
						}
						day = _getMonthlyWeekday($scope.transaction.everyDay, $scope.transaction.day , mnth, year);
					}
					ldd = new Date(year, --mnth, day, 0, 0, 0, 0);
					break;
				case 'Year':
					var dd = ldd.toISOString().split('T')[0].split('-');
					var year = parseInt(dd[0], 10) - parseInt($scope.transaction.every, 10);
					ldd = new Date(year, --dd[1], dd[2], 0, 0, 0, 0);
					break;
			}
			if (!$scope.transaction.next_due_date || ldd >= $scope.transaction.next_due_date) {
				$scope.transaction.last_due_date = ldd;
			}
		}
	};

	$scope.nextLastDueDate = function() {

		var dd, mnth, day, year;
		if ($scope.transaction.last_due_date) {
			var ldd = $scope.transaction.last_due_date;
			switch ($scope.transaction.every_unit) {
				case 'Day':
					ldd.setDate(ldd.getDate() + (1 * $scope.transaction.every));
					break;
				case 'Week':
					ldd.setDate(ldd.getDate() + (7 * $scope.transaction.every));
					break;
				case 'Month':
					dd = ldd.toISOString().split('T')[0].split('-');
					mnth = parseInt(dd[1], 10) + parseInt($scope.transaction.every, 10);
					if ((typeof $scope.transaction.everyDay === 'undefined' || $scope.transaction.everyDay === null) && (typeof $scope.transaction.day === 'undefined' || $scope.transaction.day === null)) {
						year = dd[0];
						day = dd[2];
					} else {
						if (mnth > 12) {
							year = parseInt(++dd[0], 10);
							mnth = mnth - 12;
						} else {
							year = parseInt(dd[0], 10);
						}
						day = _getMonthlyWeekday($scope.transaction.everyDay, $scope.transaction.day , mnth, year);
					}
					ldd = new Date(year, --mnth, day, 0, 0, 0, 0);
					break;
				case 'Year':
					var dd = ldd.toISOString().split('T')[0].split('-');
					var year = parseInt(dd[0], 10) + parseInt($scope.transaction.every, 10);
					ldd = new Date(year, --dd[1], dd[2], 0, 0, 0, 0);
					break;
			}
			if (!$scope.transaction.next_due_date || ldd >= $scope.transaction.next_due_date) {
				$scope.transaction.last_due_date = ldd;
			}
		} else if ($scope.transaction.next_due_date) {
			$scope.transaction.last_due_date = new Date($scope.transaction.next_due_date.getFullYear(), $scope.transaction.next_due_date.getMonth(), $scope.transaction.next_due_date.getDate());
		}
	};

	$scope.deleteLastDueDate = function() {
		
		$scope.transaction.last_due_date = null;
	};
	
}]);
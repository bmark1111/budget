'use strict';

app.controller('SheetController', ['$q', '$scope', '$rootScope', '$localStorage', 'RestData2', 'Categories',

function($q, $scope, $rootScope, $localStorage, RestData2, Categories) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var buildPeriods = function(response) {
		$rootScope.periods = [];
		$rootScope.period_start = 0;
		angular.forEach(response.data.result,
			function(period, key) {
				var sd = new Date(new Date(period.interval_beginning).setHours(0,0,0,0));
				var ed = new Date(new Date(period.interval_ending).setHours(0,0,0,0));
				var now = new Date(new Date().setHours(0,0,0,0));
				if (+now >= +sd && +now <= +ed) {
					period.alt_ending = now;			// set alternative ending
					period.current_interval = true;		// mark the current period
				}

				if (period.forecast !== 1) {
//					_isReconciled(period.accounts, ed);
				}

				$rootScope.periods[key] = period;
			});
	};

//	/**
//	 * Checks account balances to see if they are reconciled
//	 * @name _isReconciled
//	 * @param {type} accounts	accounts object
//	 * @param {type} ed			end date for the period
//	 * @returns {undefined}
//	 */
//	var _isReconciled = function(accounts, ed) {
//		angular.forEach(accounts,
//					function(account) {
//						if (account.reconciled_date) {
//							var dt = account.balance_date.split('-');
//							var bd = new Date(dt[0], --dt[1], dt[2]);				// balance date
//							var dt = account.reconciled_date.split('-');
//							var rd = new Date(dt[0], --dt[1], dt[2]);				// reconciled date
//							var now = new Date(new Date().setHours(0,0,0,0));
//							if (+rd === +ed || +rd === +now || +rd >= +bd) {
//								// if everything has been reconciled up to the period ending date...
//								// ... OR reconciled date is today...
//								// ... OR reconciled date is >= balance date
//								account.reconciled = 1;
//					} else {
//						account.reconciled = 0;
//							}
//						}
//					})
//	};

	var loadPeriods = function() {
		var deferred = $q.defer();
		if (typeof($rootScope.periods) === 'undefined') {
			var result = RestData2().getSheet({ interval: interval },
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

	var loadData = function() {
		$q.all([
			Categories.get(),
			loadPeriods()
		]).then(function(response) {
			// load the categories
			if (!!response[0].success) {
				$rootScope.categories = [];
				angular.forEach(response[0].data.categories,
					function(category) {
						$rootScope.categories.push(category)
					});
			}
			// load the intervals
			if (!!response[1].success) {
				buildPeriods(response[1]);
			}
		});
	}
	loadData();

	$scope.moveInterval = function(direction) {
		interval = interval + direction;

		if (direction === -1) {
			if ($rootScope.period_start > 0) {
				// move the start pointer
				$rootScope.period_start--;
			} else {
				// add an array element at the beginning
				getNext(-1);
			}
		} else if (direction === 1) {
			$rootScope.period_start++;
			var last_interval = $rootScope.period_start + $localStorage.sheet_views - 1;
			if (typeof($rootScope.periods[last_interval]) === 'undefined') {
				getNext(1);
			}
		}
	};

	var getNext = function(direction) {
		RestData2().getSheet({
				interval: interval
			},
			function(response) {
				if (!!response.success) {
					var moved = Array();
//					_isReconciled(response.data.result[1].accounts, response.data.result[1].interval_ending);
					// if moving backwards add interval to front of array
					if (direction == -1) {
						moved.push(response.data.result[0]);
					}
					// add the current intervals
					angular.forEach($rootScope.periods,
						function(interval) {
							moved.push(interval)
						});
					// if moving forward add interval to end of array
					if (direction == 1) {
//						// make adjustment to the account balances
//						angular.forEach(response.data.result[0].accounts,
//							function(account, index) {
//								if (typeof(response.data.result[0].adjustments[account.bank_account_id]) !== 'undefined') {
//									account.balance = parseFloat(moved[moved.length-1].accounts[index].balance) + parseFloat(response.data.result[0].adjustments[account.bank_account_id]);
//								} else {
//									account.balance = parseFloat(moved[moved.length-1].accounts[index].balance)
//								}
////console.log("------- account.bank_account_id = "+account.bank_account_id+" ------")
////console.log("prev balances = "+moved[moved.length-1].balances[account.bank_account_id]);
////console.log("this balances = "+response.data.result[0].balances[account.bank_account_id]);
//								if (typeof(moved[moved.length-1].balances) !== 'undefined' && typeof(response.data.result[0].balances) !== 'undefined') {
//									var prev_account_balance = (typeof(moved[moved.length-1].balances[account.bank_account_id]) !== 'undefined') ? moved[moved.length-1].balances[account.bank_account_id]: 0;
//									var this_account_balance = (typeof(response.data.result[0].balances[account.bank_account_id]) !== 'undefined') ? response.data.result[0].balances[account.bank_account_id]: 0;
////console.log("prev_account_balances = " + prev_account_balance);
////console.log("this_account_balances = " + this_account_balance);
////console.log("account.balance = "+account.balance);
//									if (parseFloat(this_account_balance) > parseFloat(prev_account_balance)) {
////console.log('+++++++++')
//										account.balance += (parseFloat(this_account_balance) - parseFloat(prev_account_balance));
//									}
////console.log("account.balance = "+account.balance);
//								}
//							});
						response.data.result[0].balance_forward = moved[moved.length-1].running_total;
						response.data.result[0].running_total = response.data.result[0].balance_forward + response.data.result[0].interval_total;
						moved.push(response.data.result[0]);
					}
					$rootScope.periods = moved;
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
	}

}]);
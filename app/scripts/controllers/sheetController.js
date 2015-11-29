'use strict';

app.controller('SheetController', ['$q', '$scope', '$rootScope', '$localStorage', 'RestData2', '$filter', 'Categories',

function($q, $scope, $rootScope, $localStorage, RestData2, $filter, Categories) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var buildPeriods = function(response) {
		$rootScope.intervals = [];
		$rootScope.start_interval = 0;
		angular.forEach(response.data.result,
			function(interval, key) {
				var sd = new Date(new Date(interval.interval_beginning).setHours(0,0,0,0));
				var ed = new Date(new Date(interval.interval_ending).setHours(0,0,0,0));
				var now = new Date(new Date().setHours(0,0,0,0));
				if (+now >= +sd && +now <= +ed) {
					interval.alt_ending = now;				// set alternative ending
					interval.current_interval = true;		// mark the current interval
				}

				angular.forEach(interval.accounts,
					function(account) {
						if (account.reconciled_date) {
							var dt = account.balance_date.split('-');
							var bd = new Date(dt[0], --dt[1], dt[2]);				// balance date
							var dt = account.reconciled_date.split('-');
							var rd = new Date(dt[0], --dt[1], dt[2]);				// reconciled date
							var now = new Date(new Date().setHours(0,0,0,0));
							if (+rd === +ed || +rd === +now || +rd >= +bd) {
								// if everything has been reconciled up to the period ending date...
								// ... OR reconciled date is today...
								// ... OR reconciled date is >= balance date
								account.reconciled = 1;
							}
						}
					})

				$rootScope.intervals[key] = interval;
			});
	};

	var loadPeriods = function() {
		var deferred = $q.defer();
		if (typeof($rootScope.intervals) === 'undefined') {
			var result = RestData2().getTransactions({ interval: interval },
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

	$scope.showTheseTransactions = function(category_id, index) {
		var idx = index + $rootScope.start_interval;

		$scope.dataErrorMsgThese = false;

		var date = $filter('date')($rootScope.intervals[idx].interval_ending, "EEE MMM dd, yyyy");
		$scope.title = $('#popover_' + idx + '_' + category_id).siblings('th').text() + ' transactions for interval ending ' + date;

		RestData2().getTheseTransactions({
				interval_beginning:	$rootScope.intervals[idx].interval_beginning,
				interval_ending:	$rootScope.intervals[idx].interval_ending,
				category_id:		category_id
			},
			function(response) {
				if (!!response.success) {
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
				} else {
					$scope.dataErrorMsgThese = response.errors;
				}
			});
	};

	$scope.moveInterval = function(direction) {
		interval = interval + direction;

		if (direction === -1) {
			if ($rootScope.start_interval > 0) {
				// move the start pointer
				$rootScope.start_interval -= 2;
			} else {
				// add an array element at the beginning
				getNext(-1);
			}
		} else if (direction === 1) {
			$rootScope.start_interval += 2;
			var last_interval = $rootScope.start_interval + $localStorage.budget_views;
			if (typeof($rootScope.intervals[last_interval]) === 'undefined') {
				getNext(1);
			}
		}
	};

	var getNext = function(direction) {
		RestData2().getTransactions({
				interval: interval
			},
			function(response) {
				if (!!response.success) {
					var moved = Array();
					// if moving backwards add interval to front of array
					if (direction == -1) {
						moved.push(response.data.result[0]);	// add forecast
						moved.push(response.data.result[1]);	// add actual
					}
					// add the current intervals
					angular.forEach($rootScope.intervals,
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
//						response.data.result[0].balance_forward = moved[moved.length-1].running_total;
//						response.data.result[0].running_total = response.data.result[0].balance_forward + response.data.result[0].interval_total;
						moved.push(response.data.result[0]);	// add forecast
						moved.push(response.data.result[1]);	// add actual
					}
					$rootScope.intervals = moved;
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
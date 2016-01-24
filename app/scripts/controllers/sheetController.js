'use strict';

app.controller('SheetController', ['$q', '$scope', '$rootScope', '$localStorage', '$modal', 'RestData2', '$filter', 'Categories',

function($q, $scope, $rootScope, $localStorage, $modal, RestData2, $filter, Categories) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var buildPeriods = function(response) {
		$rootScope.periods = [];
		$rootScope.period_start = 0;
		angular.forEach(response.data.result,
			function(period, key) {
				var dt = period.interval_beginning.split('T');
				var dt = dt[0].split('-');
				var sd = new Date(dt[0], --dt[1], dt[2]);
				var dt = period.interval_ending.split('T');
				var dt = dt[0].split('-');
				var ed = new Date(dt[0], --dt[1], dt[2], 23, 59, 59);
				var now = new Date();
				if (now >= sd && now <= ed) {
					period.alt_ending = now;			// set alternative ending
					period.current_interval = true;		// mark the current period
				}

				_isReconciled(period.accounts, sd, ed);

				$rootScope.periods[key] = period;
			});
	};

	/**
	 * Checks account balances to see if they are reconciled
	 * @name _isReconciled
	 * @param {type} accounts	accounts object
	 * @param {type} ed			end date for the period
	 * @returns {undefined}
	 */
	var _isReconciled = function(accounts, sd, ed) {
		var now = new Date(new Date().setHours(0,0,0,0));
		angular.forEach(accounts,
			function(account) {
				if (+ed <= +now) {
					if (account.reconciled_date) {
						var dt = account.balance_date.split('-');
						var bd = new Date(dt[0], --dt[1], dt[2]);				// balance date
						var dt = account.reconciled_date.split('-');
						var rd = new Date(dt[0], --dt[1], dt[2]);				// reconciled date
						if (+rd === +ed || +rd === +now || +rd >= +bd) {
							// if everything has been reconciled up to the period ending date...
							// ... OR reconciled date is today...
							// ... OR reconciled date is >= balance date
							account.reconciled = 2;
						} else {
							account.reconciled = 1;
						}
					} else {
						account.reconciled = (account.balance) ? 1: 99;
					}
				} else {
					account.reconciled = (+sd >= +now) ? 0: 1;
				}
			});
	};

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
						category.isCollapsed = false;
						$rootScope.categories.push(category)
					});
			}
			// load the periods
			if (!!response[1].success) {
				buildPeriods(response[1]);
			}
		});
	}
	loadData();

	$scope.isCollapsed = function(idx) {
		$rootScope.categories[idx].isCollapsed = !$rootScope.categories[idx].isCollapsed;
	};

	$scope.showTheseTransactions = function(category_id, index, category_name) {
		var idx = index + $rootScope.period_start;

		$scope.dataErrorMsgThese = false;

		var start_date = $filter('date')($rootScope.periods[idx].interval_beginning, "EEE MMM dd, yyyy");
		var end_date = $filter('date')($rootScope.periods[idx].interval_ending, "EEE MMM dd, yyyy");
		$scope.title = category_name + ' for ' + start_date + ' through ' + end_date;

		RestData2().getTheseTransactions({
				interval_beginning:	$rootScope.periods[idx].interval_beginning,
				interval_ending:	$rootScope.periods[idx].interval_ending,
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
					var dt = response.data.result[0].interval_beginning.split('T');
					var dt = dt[0].split('-');
					var sd = new Date(dt[0], --dt[1], dt[2]);
					var dt = response.data.result[0].interval_ending.split('T');
					var dt = dt[0].split('-');
					var ed = new Date(dt[0], --dt[1], dt[2]);
					_isReconciled(response.data.result[0].accounts, sd, ed);
					// if moving backwards add interval to front of array
					if (direction == -1) {
						moved.push(response.data.result[0]);
					}
					// add the current periods
					angular.forEach($rootScope.periods,
						function(interval) {
							moved.push(interval)
						});
					// if moving forward add interval to end of array
					if (direction == 1) {
						// make adjustment to the account balances
						angular.forEach(response.data.result[0].accounts,
							function(account, index) {
								// we are moving foreward so get the last intervals balance and adjust it if necessary
								account.balance = parseFloat(moved[moved.length-1].accounts[index].balance);
								if (typeof(response.data.result[0].adjustments[account.bank_account_id]) !== 'undefined') {
									account.balance += parseFloat(response.data.result[0].adjustments[account.bank_account_id]);
								}
								if (typeof(moved[moved.length-1].balances) !== 'undefined' && typeof(response.data.result[0].balances) !== 'undefined') {
									var prev_account_balance = (typeof(moved[moved.length-1].balances[account.bank_account_id]) !== 'undefined') ? moved[moved.length-1].balances[account.bank_account_id]: 0;
									var this_account_balance = (typeof(response.data.result[0].balances[account.bank_account_id]) !== 'undefined') ? response.data.result[0].balances[account.bank_account_id]: 0;
									if (parseFloat(this_account_balance) > parseFloat(prev_account_balance)) {
										account.balance += (parseFloat(this_account_balance) - parseFloat(prev_account_balance));
									}
								}
							});
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
//				ngProgress.complete();
			});
	}

	/**
	 * @name reconcile
	 * @type method
	 * @param {type} account
	 * @param {type} period
	 * @param {type} bank account index
	 * @returns {undefined}
	 */
	$scope.reconcile = function(account, period, index) {
		var use_date = (period.alt_ending) ? period.alt_ending: period.interval_ending;
		var modalInstance = $modal.open({
			templateUrl: 'reconcileTransactionsModal.html',
			controller: 'ReconcileTransactionsModalController',
			size: 'md',
			resolve: {
				params: function() {
						return {
							account:	account,
							period:		period,
							index:			index,
							date:			use_date
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Reconcile Modal dismissed at: ' + new Date());
		});
	};

}]);
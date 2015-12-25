'use strict';

app.controller('BudgetController', ['$q', '$scope', '$rootScope', '$localStorage', '$modal', 'RestData2', '$filter', 'Categories',

function($q, $scope, $rootScope, $localStorage, $modal, RestData2, $filter, Categories) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var interval = 0;

	var buildPeriods = function(response) {
		$rootScope.intervals = [];
		$rootScope.start_interval = 0;
		angular.forEach(response.data.result,
			function(interval, key) {
				var dt = interval.interval_beginning.split('T');
				var dt = dt[0].split('-');
				var sd = new Date(dt[0], --dt[1], dt[2]);
				var dt = interval.interval_ending.split('T');
				var dt = dt[0].split('-');
				var ed = new Date(dt[0], --dt[1], dt[2], 23, 59, 59);
				var now = new Date();
				if (now >= sd && now <= ed) {
					interval.alt_ending = now;				// set alternative ending
					interval.current_interval = true;		// mark the current interval
				}

				if (interval.forecast !== 1) {
					_isReconciled(interval.accounts, ed);
				}

				$rootScope.intervals[key] = interval;
			});
	};

	/**
	 * Checks account balances to see if they are reconciled
	 * @name _isReconciled
	 * @param {type} accounts	accounts object
	 * @param {type} ed			end date for the period
	 * @returns {undefined}
	 */
	var _isReconciled = function(accounts, ed) {
		var now = new Date(new Date().setHours(0,0,0,0));
		angular.forEach(accounts,
			function(account) {
				if (ed <= now) {
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
						account.reconciled = 1;
					}
				} else {
					account.reconciled = 0;
				}
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
			var last_interval = $rootScope.start_interval + $localStorage.budget_views - 1;
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
					var dt = response.data.result[1].interval_ending.split('T');
					var dt = dt[0].split('-');
					var ed = new Date(dt[0], --dt[1], dt[2]);
					_isReconciled(response.data.result[1].accounts, ed);
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

	/**
	 * @name reconcile
	 * @type method
	 * @param {type} account_name
	 * @param {type} account_id
	 * @param {type} date
	 * @param {type} alt_date
	 * @returns {undefined}
	 */
	$scope.reconcile = function(account_name, account_id, balance, date, alt_date) {
		var use_date = (alt_date) ? alt_date: date;
		var modalInstance = $modal.open({
			templateUrl: 'reconcileTransactionsModal.html',
			controller: 'ReconcileTransactionsModalController',
			size: 'md',
			resolve: {
				params: function() {
						return {
							account_name:	account_name,
							account_id:		account_id,
							date:			use_date,
							balance:		balance
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
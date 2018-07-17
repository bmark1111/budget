'use strict';

app.controller('BudgetController', ['$q', '$scope', '$sce', '$modal', '$filter', '$localStorage', 'Categories', 'Accounts', 'Periods',

function($q, $scope, $sce, $modal, $filter, $localStorage, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var localStorage = $localStorage;

	var moveBusy = false;

	var loadData = function() {

		$q.all([
			Accounts.get(),
			Categories.get(),
			Periods.getTransactions()
		]).then(function(response) {
			// load the accounts
			$scope.accounts = Accounts.data;
			$scope.active_accounts = Accounts.active;
			// load the categories
			$scope.categories = Categories.data;
			// load the transactions
			if (!!response[2].success) {
				Periods.buildPeriods(response[2].data);
				$scope.periods = Periods.periods;
				$scope.period_start = Periods.period_start;
				$scope.balanceForward = $scope.periods[$scope.period_start].balance_forward;
			} else if (response[2]) {
				$scope.periods = Periods.periods;
				$scope.period_start = Periods.period_start;
			}
		});
	};
	loadData();

	/*
	 * Checks to see if category has entries for all periods shown
	 * @param {int} category_id
	 * @returns {Boolean}
	 */
	$scope.showCategory = function(category_id) {

		for (var x = $scope.period_start; x < localStorage.sheet_views; x++) {
			if ($scope.periods[x]['totals'][category_id] !== undefined) {
				return true;
			}
		}
		return false;
	};

	/*
	 * Checks to see if account was opened during periods that are showing
	 * @param {int} account
	 * @returns {Boolean}
	 */
	$scope.showAccount = function(account) {

		var dop = (account.date_opened) ? account.date_opened.split('-'): null;
		var dcl = (account.date_closed) ? account.date_closed.split('-'): null;
		dop = (dop) ? new Date(dop[0], dop[1] - 1, dop[2], 0, 0, 0, 0): null;
		dcl = (dcl) ? new Date(dcl[0], dcl[1] - 1, dcl[2], 0, 0, 0, 0): null;

		var sd = new Date($scope.periods[$scope.period_start].interval_beginning);
		var ed = new Date($scope.periods[$scope.period_start + localStorage.sheet_views - 1].interval_ending);

		if (dcl === null && dop !== null && dop <= ed) {
			return true;
		} else if (dcl !== null && dop !== null && dcl >= sd) {
			return true;
		}
		return false;
	};

	$scope.showTheseTransactions = function(category_id, index, category_name) {

		var period = Periods.getPeriod(index);

		var start_date = $filter('date')(period.interval_beginning, "EEE MMM dd, yyyy");
		var end_date = $filter('date')(period.interval_ending, "EEE MMM dd, yyyy");
		$scope.title = category_name + ' for ' + start_date + ' through ' + end_date;

		if (category_id == 17) {		// Transfer
			$scope.transactions = [];
			var transactions = period.transactions[category_id];
			var xx = 0;
			for(var x in transactions) {
				if (transactions[x].type === 'DEBIT') {
					$scope.transactions[xx] = transactions[x];
					for(var y = 0; y < $scope.accounts.length; y++) {
						if ($scope.accounts[y].id == transactions[x].bank_account_id) {
							$scope.transactions[xx].bankNameFrom = $scope.accounts[y].name;
							$scope.transactions[xx].accountNameFrom = $scope.accounts[y].accountName;
						}
					}
					// find corresponding credit transaction in transfer
					var toTrans = findCreditTransInTransfer(transactions[x], transactions);
					$scope.transactions[xx].bankNameTo = toTrans.name;
					$scope.transactions[xx].accountNameTo = toTrans.accountName;
					xx++;
				}
			}
		} else {
			$scope.transactions = period.transactions[category_id];
			// get the account name
			for(var x in $scope.transactions) {
				for(var y = 0; y < $scope.accounts.length; y++) {
					if ($scope.accounts[y].id == $scope.transactions[x].bank_account_id) {
						$scope.transactions[x].bankName = $scope.accounts[y].name;
					}
				}
				var label = '';
				var vendor = '';
				if ($scope.transactions[x].vendor) {
					vendor = $scope.transactions[x].vendor.name;
				}
				if ($scope.transactions[x].description) {
					label += '<em>' + $scope.transactions[x].description + '</em>';
				}
				if ($scope.transactions[x].notes) {
					if (label !== '') {
						label += ' ';
					}
					label += '<em>' + $scope.transactions[x].notes + '</em>';
				}
				if (vendor != '') {
					label = '<br /><font size="1">' + label + '</font>';
				}
				$scope.transactions[x].label = $sce.trustAsHtml(vendor + label);
			}
		}
	};

	var findCreditTransInTransfer = function(transaction, transactions) {
		for(var x in transactions) {
			if ((transactions[x].type === 'CREDIT' || transactions[x].type === 'PAYMENT') && transactions[x].amount === transaction.amount && transactions[x].transaction_type == transaction.transaction_type && (!transaction.vendor || transactions[x].vendor.id == transaction.vendor.id)) {
				for(var y = 0; y < $scope.accounts.length; y++) {
					if ($scope.accounts[y].id == transactions[x].bank_account_id) {
						return { 'name': $scope.accounts[y].name,
								'accountName': $scope.accounts[y].accountName };
					}
				}
			}
		}
		return { 'name': '--NO CREDIT FOUND--',
				'accountName': '' };
	}

	$scope.moveInterval = function(direction) {

		if (!moveBusy) {
			moveBusy = true;
			Periods.getNext(direction, function() {
				$scope.periods = Periods.periods;
				$scope.period_start = Periods.period_start;
				moveBusy = false;
			});
		}
	};

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
			templateUrl: 'app/views/templates/reconcileTransactionsModal.html',
			controller: 'ReconcileTransactionsModalController',
			size: 'md',
			resolve: {
				params: function() {
						return {
							account:	account,
							period:		period,
							index:		index,
							date:		use_date
						}
					}
			}
		});

		modalInstance.result.then(function () {
//			loadData();
		},
		function () {
			console.log('Reconcile Modal dismissed at: ' + new Date());
		});
	};

}]);
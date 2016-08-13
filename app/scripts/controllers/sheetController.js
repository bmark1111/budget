'use strict';

app.controller('SheetController', ['$q', '$scope', '$rootScope', '$localStorage', '$modal', 'RestData2', '$filter', 'Categories', 'BankAccounts', 'Periods',

function($q, $scope, $rootScope, $localStorage, $modal, RestData2, $filter, Categories, BankAccounts, Periods) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

//	var interval = 0;

	var loadData = function() {
		$q.all([
			BankAccounts.get(),
			Categories.get(),
			Periods.getTransactions()
		]).then(function(response) {
			// load the bank accounts
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
			// load the transactions
			if (!!response[2].success) {
				Periods.buildPeriods(response[2].data);
				$scope.periods = Periods.periods;
				$scope.period_start = Periods.period_start;
			} else if (response[2]) {
				$scope.periods = Periods.periods;
				$scope.period_start = Periods.period_start;
			}
		});
	};
	loadData();

	$scope.showTheseTransactions = function(category_id, index, category_name) {

		var period = Periods.getPeriod(index);

		var start_date = $filter('date')(period.interval_beginning, "EEE MMM dd, yyyy");
		var end_date = $filter('date')(period.interval_ending, "EEE MMM dd, yyyy");
		$scope.title = category_name + ' for ' + start_date + ' through ' + end_date;

		$scope.transactions = period.transactions[category_id];

		// get the account name
		for(var x in $scope.transactions) {
			for(var y = 0; y < $rootScope.bank_accounts.length; y++) {
				if ($rootScope.bank_accounts[y].id == $scope.transactions[x].bank_account_id) {
					$scope.transactions[x].bankName = $rootScope.bank_accounts[y].name;
				}
			}
		};
	};

	$scope.moveInterval = function(direction) {

		Periods.getNext(direction, function() {
			$scope.periods = Periods.periods;
			$scope.period_start = Periods.period_start;
		});
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
			loadData();
		},
		function () {
			console.log('Reconcile Modal dismissed at: ' + new Date());
		});
	};

}]);
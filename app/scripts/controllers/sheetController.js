'use strict';

app.controller('SheetController', ['$q', '$scope', '$sce', '$modal', '$filter', 'Categories', 'Accounts', 'Periods',

function($q, $scope, $sce, $modal, $filter, Categories, Accounts, Periods) {

	$scope.dataErrorMsg = [];
	$scope.dataErrorMsgThese = false;

	var loadData = function() {
		$q.all([
			Accounts.get(),
			Categories.get(),
			Periods.getTransactions()
		]).then(function(response) {
			// load the accounts
			$scope.accounts = Accounts.data;
			// load the categories
			$scope.categories = Categories.data;
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
				label += '<em>' + $scope.transactions[x].notes + '</em>';
			}
			if (vendor != '') {
				label = '<br /><font size="1">' + label + '</font>';
			}
			$scope.transactions[x].label = $sce.trustAsHtml(vendor + label);
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
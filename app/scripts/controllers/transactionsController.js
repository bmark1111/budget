'use strict';

app.controller('TransactionsController', function($scope, $rootScope, $modal, $timeout, RestData2) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages = 5;
	$scope.transactions	= [];

	$scope.dataErrorMsg	= [];
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:	1,
		date:			'',
		description:	'',
		amount:			''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllTransactions(
				{
						'date':					$scope.search.date,
						'description':			$scope.search.description,
						'amount':				$scope.search.amount,
						'sort':					'transaction_date',
						'sort_dir':				'DESC',
						'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
						'pagination_amount':	$scope.itemsPerPage
				},
				function(response) {
					if (!!response.success) {
						$scope.transactions = response.data.result;
						$scope.transactions_seq = Object.keys(response.data.result);
						$scope.recCount = response.data.total_rows;
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

	loadData();

	var timer = null;
	$scope.refreshData = function() {
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadData, 1000);
		loadData();
	};

	$scope.pageChanged = function() {
		loadData();
	};

	// open date picker
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.uploadTransactions = function() {
		var modalInstance = $modal.open({
			templateUrl: 'uploadModal.html',
			controller: 'UploadModalController',
			size: 'sm',
			resolve: {
				params: function() {
						return {
							title: 'Upload Transactions'
						}
					}
			}
		});

		modalInstance.result.then(function (response) {
			$rootScope.transaction_count = (parseInt(response.count) > 0) ? parseInt(response.count): '';
		},
		function () {
			console.log('Upload Modal dismissed at: ' + new Date());
		});
	};

	$scope.addTransaction = function() {
		var modalInstance = $modal.open({
			templateUrl: 'editModal.html',
			controller: 'EditModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
						return {
							id: 0,
							title: 'Add Transaction'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add Modal dismissed at: ' + new Date());
		});
	};

	$scope.editTransaction = function(transaction_id) {
		var modalInstance = $modal.open({
			templateUrl: 'editModal.html',
			controller: 'EditModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
						return {
							id: transaction_id,
							title: 'Edit Transaction'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteTransaction = function (transaction_id) {
		var modalInstance = $modal.open({
			templateUrl: 'deleteModal.html',
			controller: 'DeleteModalController',
			size: 'sm',
			resolve: {
				params: function() {
						return {
							id: transaction_id,
							title: 'Delete Transaction ?',
							msg: 'Are you sure you want to delete this transaction. This action cannot be undone.'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Modal dismissed at: ' + new Date());
		});
	};

});

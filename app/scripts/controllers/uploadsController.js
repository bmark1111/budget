'use strict';

app.controller('UploadsController', function($q, $scope, $rootScope, $modal, $timeout, RestData2, Accounts) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages = 5;
	$scope.transactions	= [];

	$scope.dataErrorMsg	= [];

	$scope.search = {
		currentPage:		1,
		status:				false,
		date:				'',
		description:		'',
		bank_account_id:	'',
		amount:				''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllUploads({
				'status':				$scope.search.status,
				'date':					$scope.search.date,
				'description':			$scope.search.description,
				'bank_account_id':		$scope.search.bank_account_id,
				'amount':				$scope.search.amount,
				'sort':					'transaction_date',
				'sort_dir':				'ASC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.transactions = response.data.result;
					for(var x in $scope.transactions) {
						for(var y = 0; y < $scope.accounts.length; y++) {
							if ($scope.accounts[y].id == $scope.transactions[x].bank_account_id) {
								$scope.transactions[x].bankName = $scope.accounts[y].name;
								break;
							}
						}
					}
					$scope.transactions_seq = Object.keys(response.data.result);
					$scope.recCount = response.data.total_rows;
					$rootScope.transaction_count = (parseInt(response.data.pending_count) > 0) ? parseInt(response.data.pending_count): '';
				} else {
					$rootScope.transaction_count = '';
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

	var getUploads = function() {
		var deferred = $q.defer();
		var result = RestData2().getAllUploads({
				'status':				$scope.search.status,
				'date':					$scope.search.date,
				'description':			$scope.search.description,
				'bank_account_id':		$scope.search.bank_account_id,
				'amount':				$scope.search.amount,
				'sort':					'transaction_date',
				'sort_dir':				'ASC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				deferred.resolve(result);
			},
			function(err) {
				deferred.resolve(err);
			});

		return deferred.promise;
	};

	$q.all([
		Accounts.get(),
		getUploads()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;

		// load the transaction
		if (!!response[1].success) {
			if (response[1].data.result) {
				$scope.transactions = response[1].data.result;
				for(var x in $scope.transactions) {
					for(var y = 0; y < $scope.accounts.length; y++) {
						if ($scope.accounts[y].id == $scope.transactions[x].bank_account_id) {
							$scope.transactions[x].bankName = $scope.accounts[y].name;
							break;
						}
					}
				}
				$scope.transactions_seq = Object.keys(response[1].data.result);
			}
		} else {
			if (response[1].errors) {
				angular.forEach(response[1].errors,
					function(error) {
						$scope.dataErrorMsg.push(error.error);
					})
			} else {
				$scope.dataErrorMsg[0] = response[1];
			}
		}
	});

	var timer = null;
	$scope.refreshData = function()
	{
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadData, 1000);
	};

	$scope.pageChanged = function()
	{
		loadData();
	};

	// open date picker
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.postTransaction = function(transaction_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/postUploadedModal.html',
			controller: 'PostUploadedModalController',
			size: 'lg',
			resolve: {
				params: function() {
							return {
								id: transaction_id,
								title: 'Post Uploaded Transaction ?'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Post Uploaded Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteTransaction = function (transaction_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/deleteModal.html',
			controller: 'DeleteUploadedModalController',
			size: 'sm',
			resolve: {
				params: function() {
							return {
								id: transaction_id,
								title: 'Delete Uploaded Transaction ?',
								msg: 'Are you sure you want to delete this uploaded transaction. This action cannot be undone.'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Uploaded Modal dismissed at: ' + new Date());
		});
	};

});
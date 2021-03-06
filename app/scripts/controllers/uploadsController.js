'use strict';

app.controller('UploadsController', function($scope, $rootScope, $modal, $timeout, RestData2) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages = 5;
	$scope.transactions	= [];

	$scope.dataErrorMsg	= [];

	$scope.search = {
		currentPage:	1,
		date:			'',
		description:	'',
		amount:			''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllUploads({
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

	loadData();

	var timer = null;
	$scope.refreshData = function()
	{
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadData, 1000);
		loadData();
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
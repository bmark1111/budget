'use strict';

app.controller('UploadsController', function($scope, $modal, $timeout, $rootScope, RestData)
{
	$rootScope.nav_active = 'uploads';

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages = 5;
	$scope.transactions	= [];
	$scope.transaction_count = '';

	$scope.dataErrorMsg	= false;

	$scope.search = {
		currentPage:	1,
		date:			'',
		description:	'',
		amount:			''
	};

	var loadData = function()
	{
		$scope.dataErrorMsg = false;

//		ngProgress.start();

		var searchCriteria = {
						'status':				0,					// get all pending uploaded transactions
						'date':					$scope.search.date,
						'description':			$scope.search.description,
						'amount':				$scope.search.amount,
						'sort':					'transaction_date',
						'sort_dir':				'DESC',
						'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
						'pagination_amount':	$scope.itemsPerPage
		};

		RestData.getAllUploads(searchCriteria,
			function(response)
			{
				if (!!response.success)
				{
					$scope.transactions = response.data.result;
					$scope.transactions_seq = Object.keys(response.data.result);
					$scope.recCount = response.data.total_rows;
				} else {
					if (response.errors)
					{
						$scope.dataErrorMsg = response.errors[0].error;
					} else {
						$scope.dataErrorMsg = response;
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
	$scope.open = function($event)
	{
		$event.preventDefault();
		$event.stopPropagation();

		$scope.opened = true;
	};

	$scope.postTransaction = function(transaction_id)
	{
		var modalInstance = $modal.open({
			templateUrl: 'postUploadedModal.html',
			controller: 'PostUploadedModalController',
			size: 'lg',
			resolve: {
				params: function()
					{
						return {
							id: transaction_id,
							title: 'Post Uploaded Transaction ?'
						}
					}
			}
		});

		modalInstance.result.then(function ()
		{
			loadData();
		},
		function ()
		{
			console.log('Edit Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteTransaction = function (transaction_id)
	{
		var modalInstance = $modal.open({
			templateUrl: 'deleteUploadedModal.html',
			controller: 'DeleteUploadedModalController',
			size: 'sm',
			resolve: {
				params: function()
					{
						return {
							id: transaction_id,
							title: 'Delete Uploaded Transaction ?',
							msg: 'Are you sure you want to delete this uploaded transaction. This action cannot be undone.'
						}
					}
			}
		});

		modalInstance.result.then(function ()
		{
			loadData();
		},
		function ()
		{
			console.log('Delete Modal dismissed at: ' + new Date());
		});
	};

});

'use strict';

app.controller('BankController', function($scope, $rootScope, $localStorage, $location, $modal, $timeout, RestData)
{
	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages = 5;
	$scope.banks	= [];

	$scope.dataErrorMsg	= false;
	$scope.searchDisplay = true;
	$scope.opened = false;

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
						'name':					$scope.search.name,
						'sort':					'name',
						'sort_dir':				'DESC',
						'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
						'pagination_amount':	$scope.itemsPerPage
		};

		RestData(
			{
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			})
			.getAllBanks(searchCriteria,
				function(response)
				{
					if (!!response.success)
					{
						$scope.banks = response.data.result;
						$scope.banks_seq = Object.keys(response.data.result);
						$scope.recCount = response.data.total_rows;
					} else {
						if (response.errors)
						{
							$scope.dataErrorMsg = response.errors[0].error;
						} else {
							$scope.dataErrorMsg = response;
						}
					}
//					ngProgress.complete();
				},
				function (error)
				{
					if (error.status == '401' && error.statusText == 'EXPIRED')
					{
						$localStorage.authenticated		= false;
						$localStorage.authorizedRoles	= false;
						$localStorage.userFullName		= false;
						$localStorage.token_id			= false;
						$localStorage.userId			= false;
						$localStorage.authorization		= false;
						$location.path("/login");
					} else {
						$rootScope.error = error.status + ' ' + error.statusText;
					}
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

	$scope.addBank = function()
	{
		var modalInstance = $modal.open({
			templateUrl: 'editBankModal.html',
			controller: 'EditBankModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function()
					{
						return {
							id: 0,
							title: 'Add Bank'
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
			console.log('Add Bank Modal dismissed at: ' + new Date());
		});
	};

	$scope.editBank = function(bank_id)
	{
		var modalInstance = $modal.open({
			templateUrl: 'editBankModal.html',
			controller: 'EditBankModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function()
					{
						return {
							id: bank_id,
							title: 'Edit Bank'
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
			console.log('Edit Bank Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteBank = function (bank_id)
	{
		var modalInstance = $modal.open({
			templateUrl: 'deleteBankModal.html',
			controller: 'DeleteBankModalController',
			size: 'sm',
			resolve: {
				params: function()
					{
						return {
							id: bank_id,
							title: 'Delete Bank ?',
							msg: 'Are you sure you want to delete this bank. This action cannot be undone.'
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
			console.log('Delete Bank Modal dismissed at: ' + new Date());
		});
	};

});

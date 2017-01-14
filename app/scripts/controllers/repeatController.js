'use strict';

app.controller('RepeatController', function($q, $scope, $modal, $timeout, RestData2, Accounts, Categories) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages		= 5;
	$scope.repeats		= [];

	$scope.dataErrorMsg	= [];
	$scope.searchDisplay = true;
	$scope.opened = false;

	$scope.search = {
		currentPage:		1,
		last_due_date:		false,
		name:				'',
		bank_account_id:	'',
		category_id:		'',
		amount:				''
	};

	var loadData = function() {
		$scope.dataErrorMsg = [];

//		ngProgress.start();

		RestData2().getAllRepeats( {
				'last_due_date':		$scope.search.last_due_date,
				'name':					$scope.search.name,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
				'amount':				$scope.search.amount,
				'sort':					'next_due_date',
				'sort_dir':				'ASC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.repeats = response.data.result;
					for(var x in $scope.repeats) {
						for(var y = 0; y < $scope.accounts.length; y++) {
							if ($scope.accounts[y].id == $scope.repeats[x].bank_account_id) {
								$scope.repeats[x].bankName = $scope.accounts[y].name;
								break;
							}
						}
					}
					$scope.repeats_seq = Object.keys(response.data.result);
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
//				ngProgress.complete();
			});
	}
	var getRepeats = function() {
		var deferred = $q.defer();
		var result = RestData2().getAllRepeats({
				'last_due_date':		$scope.search.last_due_date,
				'name':					$scope.search.name,
				'bank_account_id':		$scope.search.bank_account_id,
				'category_id':			$scope.search.category_id,
				'amount':				$scope.search.amount,
				'sort':					'next_due_date',
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
		Categories.get(),
		getRepeats()
	]).then(function(response) {
		// load the accounts
		$scope.accounts = Accounts.data;
		$scope.active_accounts = Accounts.active;
		// load the categories
		$scope.categories = Categories.data;

		// load the transaction
		if (!!response[2].success) {
			if (response[2].data.result) {
				$scope.repeats = response[2].data.result;
				for(var x in $scope.repeats) {
					for(var y = 0; y < $scope.accounts.length; y++) {
						if ($scope.accounts[y].id == $scope.repeats[x].bank_account_id) {
							$scope.repeats[x].bankName = $scope.accounts[y].name;
							break;
						}
					}
				}
				$scope.repeats_seq = Object.keys(response[2].data.result);
			}
		} else {
			if (response[2].errors) {
				angular.forEach(response[2].errors,
					function(error) {
						$scope.dataErrorMsg.push(error.error);
					})
			} else {
				$scope.dataErrorMsg[0] = response[2];
			}
		}
	});

	var timer = null;
	$scope.refreshData = function() {
		$scope.search.currentPage = 1;

		if (timer) $timeout.cancel(timer);
		timer = $timeout(loadData, 1000);
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

	$scope.addRepeat = function() {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editRepeatModal.html',
			controller: 'EditRepeatModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: 0,
								title: 'Add Repeat'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add Repeat Modal dismissed at: ' + new Date());
		});
	};

	$scope.editRepeat = function(repeat_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editRepeatModal.html',
			controller: 'EditRepeatModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: repeat_id,
								title: 'Edit Repeat'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit Repeat Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteRepeat = function (repeat_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/deleteModal.html',
			controller: 'DeleteRepeatModalController',
			size: 'sm',
			resolve: {
				params: function() {
							return {
								id: repeat_id,
								title: 'Delete Repeat ?',
								msg: 'Are you sure you want to delete this Repeat. This action cannot be undone.'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Repeat Modal dismissed at: ' + new Date());
		});
	};

});
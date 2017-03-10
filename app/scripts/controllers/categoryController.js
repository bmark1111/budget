'use strict';

app.controller('CategoryController', function($scope, $modal, $timeout, RestData2) {

	$scope.itemsPerPage	= 20;
	$scope.maxSize		= 10;
	$scope.recCount		= 0;
	$scope.numPages		= 5;
	$scope.categories		= [];

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

		RestData2().getAllCategories({
				'name':					$scope.search.name,
				'sort':					'order',
				'sort_dir':				'ASC',
				'pagination_start':		($scope.search.currentPage - 1) * $scope.itemsPerPage,
				'pagination_amount':	$scope.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					$scope.categories = response.data.result;
					$scope.categories_seq = Object.keys(response.data.result);
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

	loadData();

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

	$scope.addCategory = function() {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editCategoryModal.html',
			controller: 'EditCategoryModalController',
			size: 'sm',
			resolve: {
				params: function()
					{
						return {
							id: 0,
							title: 'Add Category'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add Category Modal dismissed at: ' + new Date());
		});
	};

	$scope.editCategory = function(category_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/editCategoryModal.html',
			controller: 'EditCategoryModalController',
			size: 'sm',
//			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
							return {
								id: category_id,
								title: 'Edit Category'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit Category Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteCategory = function (category_id) {
		var modalInstance = $modal.open({
			templateUrl: 'app/views/templates/deleteModal.html',
			controller: 'DeleteCategoryModalController',
			size: 'sm',
			resolve: {
				params: function() {
							return {
								id: category_id,
								title: 'Delete Category ?',
								msg: 'Are you sure you want to delete this category. This action cannot be undone.'
							}
						}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Delete Category Modal dismissed at: ' + new Date());
		});
	};

});

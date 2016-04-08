'use strict';

app.controller('UserController', userController);

function userController($scope, $timeout, $modal, RestData2) {

	var self = this;
	
	self.itemsPerPage	= 20;
	self.maxSize		= 10;
	self.recCount		= 0;
	self.numPages		= 5;
	self.users			= [];
	self.users_seq		= [];

	self.search = {
		currentPage:	1,
		name:			null
	};

	self.dataErrorMsg	= [];
	self.error			= false;

	var loadData = function() {
//		ngProgress.start();

		RestData2().getAllUsers({
				lastname:			self.name,
				sort:				'lastname',
				sort_dir:			'DESC',
				pagination_start:	(self.search.currentPage - 1) * self.itemsPerPage,
				pagination_amount:	self.itemsPerPage
			},
			function(response) {
				if (!!response.success) {
					self.users		= response.data.result;
					self.users_seq	= Object.keys(response.data.result);
					self.recCount	= response.data.total_rows;
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								self.dataErrorMsg.push(error.error);
							})
					} else {
						self.dataErrorMsg[0] = response;
					}
				}

//				ngProgress.complete();
			});
	};
	
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

	$scope.addUser = function() {
		var modalInstance = $modal.open({
			templateUrl: 'editUserModal.html',
			controller: 'EditUserModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
						return {
							id: 0,
							title: 'Add User'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Add User Modal dismissed at: ' + new Date());
		});
	};

	$scope.editUser = function(user_id) {
		var modalInstance = $modal.open({
			templateUrl: 'editUserModal.html',
			controller: 'EditUserModalController',
//			size: 'lg',
			windowClass: 'app-modal-window',
			resolve: {
				params: function() {
						return {
							id: user_id,
							title: 'Edit User'
						}
					}
			}
		});

		modalInstance.result.then(function () {
			loadData();
		},
		function () {
			console.log('Edit User Modal dismissed at: ' + new Date());
		});
	};

	$scope.deleteUser = function (user_id) {
		var modalInstance = $modal.open({
			templateUrl: 'deleteModal.html',
			controller: 'DeleteUserModalController',
			size: 'sm',
			resolve: {
				params: function() {
						return {
							id: user_id,
							title: 'Delete User ?',
							msg: 'Are you sure you want to delete this user. This action cannot be undone.'
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

};
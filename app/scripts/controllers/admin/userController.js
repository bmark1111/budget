'use strict';

app.controller('UserController', userController);

function userController($scope, $modal, RestData2) {

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

//	ngProgress.start();

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
self.users[0].last_login = '2016-01-02 08:22:33';
				self.users_seq	= Object.keys(response.data.result);
console.log(self.users)
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

//			ngProgress.complete();
		});

};

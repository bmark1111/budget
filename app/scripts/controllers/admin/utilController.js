'use strict';

app.controller('UtilController', userController);

function userController($scope, RestData2) {

	var self = this;
	
	self.dataErrorMsg	= [];
	self.error			= false;

	$scope.runUtility = function(type) {
//		ngProgress.start();
		RestData2().runUtility({
				type:	type,
			},
			function(response) {
				if (!!response.success) {
					console.log(response)
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

};
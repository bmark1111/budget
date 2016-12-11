'use strict';

app.controller('DashboardController', ['$q', '$scope', 'RestData2', 'Categories', 'Periods',

function($q, $scope, RestData2, Categories, Periods) {

	var self = this;

	this.dataErrorMsg = [];
	var now = new Date();
	this.ytdYear = now.getFullYear();
	this.ytdTotals = [];
	this.transactions = false;
	this.transactions_seq = false;

	var getYTDTotals = function() {
		var deferred = $q.defer();
		var result = RestData2().getYTDTotals({
				year: self.ytdYear
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
		Categories.get(),
		getYTDTotals(),
		Periods.getTransactions()
	]).then(function(response) {
		// load the categories
		$scope.categories = Categories.data;
		// load the YTD Totals
		if (!!response[1].success) {
			self.dataErrorMsg = [];
			self.ytdTotals = [];
			self.transactions = false;
			self.transactions_seq = false;
			angular.forEach($scope.categories,function(category, key) {
				if (category.id != 17 && category.id != 22) {	// Do not load Transfer and Opening Balance
					var category = {
						id:			category.id,
						name:		category.name,
						total:		response[1].data.result['total_' + category.id],
						forecast:	response[1].data.forecast[category.id]
					};
					self.ytdTotals.push(category);
				}
			});
		} else if (!!response[2].success) {
			Periods.buildPeriods(response[2].data);
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
	});

	$scope.getYTDTransactions = function(category_id) {
		self.dataErrorMsg = [];

		RestData2().getYTDTransactions({
				year:			self.ytdYear,
				category_id:	category_id
			},
			function(response) {
				if (!!response.success) {
					self.transactions = response.data.result;
					self.transactions_seq = Object.keys(response.data.result);
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
			});
	};

	$scope.getYTD = function() {
		self.dataErrorMsg = [];
		RestData2().getYTDTotals({
				year: self.ytdYear
			},
			function (response) {
			// load the YTD Totals
			if (!!response.success) {
				self.ytdTotals = [];
				self.transactions = false;
				self.transactions_seq = false;
				angular.forEach($scope.categories,
					function(category, key) {
						var category = {
							id:			category.id,
							name:		category.name,
							total:		response.data.result['total_' + category.id],
							forecast:	response.data.forecast[category.id]
						};
						self.ytdTotals.push(category);
					});
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
		});
	}
}]);

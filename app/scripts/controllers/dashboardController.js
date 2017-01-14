'use strict';

app.controller('DashboardController', ['$q', '$scope', 'RestData2', 'Categories', 'Periods',

function($q, $scope, RestData2, Categories, Periods) {

	var self = this;

	this.dataErrorMsg = [];
	this.ytdYear = [];
	this.ytdTotals = [];
	this.transactions = false;
	this.transactions_seq = false;
	this.repeats = false;
	this.repeats_seq = false;

	var getYTDTotals = function(year) {
		var deferred = $q.defer();
		var result = RestData2().getYTDTotals({
				year: year
			},
			function(response) {
				deferred.resolve(result);
			},
			function(err) {
				deferred.resolve(err);
			});
		return deferred.promise;
	};

	var getRepeats = function() {
		var deferred = $q.defer();
		var result = RestData2().getAllRepeats({
				'last_due_date':		false,
				'name':					'',
				'bank_account_id':		'',
				'category_id':			'',
				'amount':				'',
				'sort':					'next_due_date',
				'sort_dir':				'ASC',
				'pagination_start':		0,
				'pagination_amount':	40
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
		Periods.getTransactions(),
		getRepeats()
	]).then(function(response) {
		// load the categories
		self.categories = Categories.data;
		// load the YTD Totals
		if (!!response[1].success) {
			Periods.buildPeriods(response[1].data);
		}
		// load repeats
		if (response[2].success) {
			self.repeats = response[2].data.result;
			self.repeats_seq = Object.keys(response[2].data.result);
console.log(self.repeats)
		}
		// load yearly totals
		var now = new Date();
		for(var year = 2015; year <= (now.getFullYear()+1); year++) {
			getYTDTotals(year).then(function(response) {
				if (!!response.success) {
					var ytdIndex = response.data.year - 2015;
					self.ytdYear[ytdIndex] = response.data.year;
					if (response.data.year == now.getFullYear()) {
						self.selectedYear = response.data.year;
						self.selectedIndex = ytdIndex;
					}

					self.ytdTotals[ytdIndex] = [];
					angular.forEach(self.categories,function(category, key) {
						if (category.id != 17 && category.id != 22) {	// Do not load Transfer and Opening Balance
							self.ytdTotals[ytdIndex].push({id:			category.id,
															name:		category.name,
															total:		Number(response.data.result['total_' + category.id]),
															forecast:	Number(response.data.forecast[category.id]),
															future:		(response.data.year > now.getFullYear()) ? true: false,
															year:		self.ytdYear[ytdIndex]
														});
						}
					});
				}
			});
		}
	});

	$scope.getYTDTransactions = function(category_id, year) {
		self.dataErrorMsg = [];

		RestData2().getYTDTransactions({
				year:			year,	//self.ytdYear,
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
		for(var x = 0; x < self.ytdYear.length; x++) {
			if (self.selectedYear == self.ytdYear[x]) {
				self.selectedIndex = x;
				break;
			}
		}
//		self.dataErrorMsg = [];
//		RestData2().getYTDTotals({
//				year: self.ytdYear
//			},
//			function (response) {
//			// load the YTD Totals
//			if (!!response.success) {
//				self.ytdTotals = [];
//				self.transactions = false;
//				self.transactions_seq = false;
//				angular.forEach($scope.categories,
//					function(category, key) {
//						var category = {
//							id:			category.id,
//							name:		category.name,
//							total:		response.data.result['total_' + category.id],
//							forecast:	response.data.forecast[category.id]
//						};
//						self.ytdTotals.push(category);
//					});
//			} else {
//				if (response.errors) {
//					angular.forEach(response.errors,
//						function(error) {
//							self.dataErrorMsg.push(error.error);
//						})
//				} else {
//					self.dataErrorMsg[0] = response;
//				}
//			}
//		});
	}
}]);

'use strict';

app.controller('DashboardController', ['$q', '$scope', '$localStorage', 'RestData2', 'Categories', 'Periods',

function($q, $scope, $localStorage, RestData2, Categories, Periods) {

	var self = this;

	this.dataErrorMsg = [];
	this.ytdYear = [];
	this.ytdTotals = [];
	this.transactions = false;
	this.transactions_seq = false;
	this.repeats = [];
	this.balances = false;
	this.balances_seq = false;
	this.now = new Date();

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

	var getBankBalances = function() {
		var deferred = $q.defer();
		var result = RestData2().getBankBalances(
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
		getRepeats(),
		getBankBalances()
	]).then(function(response) {
		// load the categories
		self.categories = Categories.data;
		// load the YTD Totals
		if (!!response[1].success) {
			Periods.buildPeriods(response[1].data);
		}
		// load repeats
		if (response[2].success) {
			var repeats = response[2].data.result;
			var repeats_seq = Object.keys(response[2].data.result);
			var due = [];
			self.repeats = [];
			for (var x = 0; x < repeats_seq.length; x++) {
				var idx = repeats_seq[x];
				if (!due[repeats[idx].category_id] || due[repeats[idx].category_id] != repeats[idx].vendor_id) {
					due[repeats[idx].category_id] = repeats[idx].vendor_id
					repeats[idx].dueDate = new Date(repeats[idx].next_due_date + 'T00:00:00.000Z');
					self.repeats.push(repeats[idx]);
				}
			}
		}
		// load bank balances
		if (response[3].success) {
			self.balances = response[3].data.result;
			self.balances_seq = Object.keys(response[3].data.result);
		}

		//get start year of Budget
		var sd = new Date($localStorage.budget_start_date);
		var start_year = sd.getFullYear();

		// load yearly totals
		var now = new Date();
		for(var year = start_year; year <= (now.getFullYear()+1); year++) {
			getYTDTotals(year).then(function(response) {
				if (!!response.success) {
					var ytdIndex = response.data.year - start_year;
					self.ytdYear[ytdIndex] = response.data.year;
					if (response.data.year == now.getFullYear()) {
						self.selectedYear = response.data.year;
						self.selectedIndex = ytdIndex;
					}

					self.ytdTotals[ytdIndex] = [];
					angular.forEach(self.categories,function(category, key) {
						if (category.id != 17 && category.id != 22) {	// Do not show Transfer's and Opening Balance's
//							self.ytdTotals[ytdIndex].push({ id:			category.id,
							self.ytdTotals[ytdIndex][category.id] = {	id:			category.id,
																		name:		category.name,
																		total:		Number(response.data.result['total_' + category.id]),
																		forecast:	Number(response.data.forecast[category.id]),
																		future:		(response.data.year > now.getFullYear()) ? true: false,
																		year:		self.ytdYear[ytdIndex]
																	};
						}
					});
				}
			});
		}
	});

	$scope.getYTDTransactions = function(category_id, year) {
		self.dataErrorMsg = [];

		RestData2().getYTDTransactions({
				year:			year,
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

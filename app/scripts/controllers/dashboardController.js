'use strict';

app.controller('DashboardController', ['$q', '$scope', '$localStorage', 'RestData2', 'Categories', 'Periods', 'Accounts',

function($q, $scope, $localStorage, RestData2, Categories, Periods, Accounts) {

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

	$q.all([
		Categories.get()
	]).then(function(response) {
		// load the categories
		self.categories = Categories.data;

		//get start year of Budget
		var sd = new Date($localStorage.budget_start_date);
		var start_year = sd.getFullYear();

		// load yearly totals
		var now = new Date();
		for(var year = now.getFullYear(); year >= start_year; year--) {
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

	RestData2().getAllRepeats({
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
			if (response.success) {
				console.log('Repeats loaded')
				var repeats = response.data.result;
				var repeats_seq = Object.keys(response.data.result);
				var due = [];
				self.repeats = [];
				for (var x = 0; x < repeats_seq.length; x++) {
					var idx = repeats_seq[x];
			//		if (!due[repeats[idx].category_id] || due[repeats[idx].category_id] != repeats[idx].vendor_id) {
						due[repeats[idx].category_id] = repeats[idx].vendor_id
						repeats[idx].dueDate = new Date(repeats[idx].next_due_date + 'T00:00:00.000Z');
						self.repeats.push(repeats[idx]);
			//		}
				}
			} else {
				if (response.errors) {
					angular.forEach(response.errors,
						function(error) {
							self.dataErrorMsg.push(error.error);
						})
				} else {
					self.dataErrorMsg.push(response);
				}
			}
		},
		function(err) {
			
		});

	RestData2().getBankBalances(
		function(response) {
			if (response.success) {
				console.log('Bank Balances loaded')
				self.balances = response.data.result;
				self.balances_seq = Object.keys(response.data.result);
			} else {
				if (response.errors) {
					angular.forEach(response.errors,
						function(error) {
							self.dataErrorMsg.push(error.error);
						})
				} else {
					self.dataErrorMsg.push(response);
				}
			}
		},
		function(err) {

		});
	Periods.getTransactions();
	Accounts.get();

	$scope.getYTDTransactions = function(category_id, year) {
		self.dataErrorMsg = [];

		RestData2().getYTDTransactions({
				year:			year,
				category_id:	category_id
			},
			function(response) {
				if (!!response.success) {
					self.forecast = null;
					self.forecast_seq = null;
					self.transactions = response.data.result;
					self.transactions_seq = Object.keys(response.data.result);
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								self.dataErrorMsg.push(error.error);
							})
					} else {
						self.dataErrorMsg.push(response);
					}
				}
			});
	};

	$scope.getYTDForecast = function(category_id, year) {
		self.dataErrorMsg = [];

		RestData2().getYTDForecast({
				year:			year,
				category_id:	category_id
			},
			function(response) {
				if (!!response.success) {
					self.transactions = null;
					self.transactions_seq = null;
					self.forecast = response.data.result;
					self.forecast_seq = Object.keys(response.data.result);
				} else {
					if (response.errors) {
						angular.forEach(response.errors,
							function(error) {
								self.dataErrorMsg.push(error.error);
							})
					} else {
						self.dataErrorMsg.push(response);
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
	}
}]);

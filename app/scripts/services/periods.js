'use strict'

var services = {};

/**
 * @constructor
 * @returns {undefined}
 */
services.periods = function($q, RestData2, $rootScope, $localStorage) {
	
	this.$q = $q;
	this.RestData2 = RestData2;
	this.$rootScope = $rootScope;
	this.$localStorage = $localStorage;
};

/**
 * Holds transaction for the periods
 * @name data
 * @private
 * @type {Array}
 */
services.periods.prototype.data = [];

/**
 * @name periods
 * @public
 * @type {number}
 */
services.periods.prototype.periods = null;

/**
 * @name period_start
 * @public
 * @type {number}
 */
services.periods.prototype.period_start = null;

/**
 * @name getTransactions
 * @public
 */
services.periods.prototype.getTransactions = function () {

	var self = this;

	var deferred = self.$q.defer();

	if (this.data.length == 0) {
		self.RestData2().getSheetTransactions({ interval: 0 }, function (response) {
			console.log("transactions got");
			deferred.resolve(response);
		},
		function (error) {
			console.log("failed to get transactions");
			deferred.reject(error);
		});
	} else {
		console.log("already loaded transactions");
		deferred.resolve(true);
	}
	return deferred.promise;
};

/**
 * gets a single period
 * 
 * @name getPeriod
 * @public
 * @param {number} index
 * @returns {Object}
 */
services.periods.prototype.getPeriod = function (index) {

	var idx = index + this.period_start;
//	var idx = index + this.$rootScope.period_start;
	return this.periods[idx];
};

/**
 * @name getDates
 * @public
 * @param {number} direction -1  = backward, 1= forward
 * @returns {Object}
 */
services.periods.prototype.getNext = function (direction, callback) {

	switch (direction) {
		case -1:
			if (this.period_start > 0) {
				// move the start pointer
				--this.period_start;
				callback();
			} else {
				// add an array element at the beginning
				this.loadNext(-1, 0, callback);
			}
			break;
		case 1:
			var last_interval = this.period_start + this.$localStorage.sheet_views;
			++this.period_start;
			if (typeof(this.periods[last_interval]) === 'undefined') {
				this.loadNext(1, --last_interval, callback);
			} else {
				callback();
			}
			break;
	}
};

/**
 * @name loadNext
 * @public
 * @param {number} direction -1  = backward, 1= forward
 * @returns {Object}
 */
services.periods.prototype.loadNext = function (direction, interval, callback) {

	var self = this;

	switch (this.$localStorage.budget_mode) {
		case 'weekly':
			//
			break;
		case 'bi-weekly':
			//
			break;
		case 'semi-monthy':
			//
			break;
		case 'monthly':
			var sd = this.periods[interval].interval_beginning.split('T')[0].split('-');
			var mnth = parseInt(sd[1], 10) + direction;
			sd = new Date(sd[0], --mnth, sd[2], 0, 0, 0, 0);

			var ed = this.periods[interval].interval_ending.split('T')[0].split('-');
			var mnth = parseInt(ed[1], 10) + direction;
			ed = new Date(ed[0], mnth, 0, 0, 0, 0, 0);
			break;
	}

	this.RestData2().getSheetTransactions({
			interval: direction,
			start_date: sd,
			end_date: ed
		},
		function(response) {
			if (!!response.success) {
				var moved = Array();
				var output = {
					'accounts':	JSON.parse(JSON.stringify(self.periods[0].accounts)),
					'amounts': {},
					'balance_forward': response.data.balance_forward,
					'balances': {},
					'interval_beginning': sd.toISOString(),
					'interval_ending': ed.toISOString(),
					'interval_total': 0,
					'running_total': 0,
					'totals': {},
					'types': {},
					'transactions': {}
				};
				// if moving backwards add period to start of array
				if (direction == -1) {
					angular.forEach(response.data.result, function(transaction, x) {
						self.addTransactionToTotals(transaction, output);
					});
					moved.push(output)
				}
				// add the current periods
				angular.forEach(self.periods, function(period) {
					moved.push(period);
				});

				// if moving forward add interval to end of array
				if (direction == 1) {
					angular.forEach(response.data.result,  function(transaction, x) {
						self.addTransactionToTotals(transaction, output);
					});
					moved.push(output);
				}

				self.periods = moved;
				callback();
			} else {
				if (response.errors) {
					angular.forEach(response.errors,
						function(error) {
							self.$scope.dataErrorMsg.push(error.error);
						})
				} else {
					self.$scope.dataErrorMsg[0] = response;
				}
			}
//			ngProgress.complete();
		});
}

services.periods.prototype.clear = function () {

	this.data = [];
};

/**
 * @name buildPeriods
 * @param {Object} data
 * @public
 * @returns {undefined}
 */
services.periods.prototype.buildPeriods = function(data) {

	var self = this;
	
	this.data = data;

	var budget_interval;
	var budget_interval_unit;

	switch (this.$localStorage.budget_mode) {
		case 'weekly':
			budget_interval = 7;
			budget_interval_unit = 'Days';
			break;
		case 'bi-weekly':
			budget_interval = 14;
			budget_interval_unit = 'Days';
//				$offset = $this->_getEndDay();
//				if ($interval == 0) {
//					$start_day = ($offset - (budget_interval * ($localStorage.sheet_views)));					// go back 'sheet views'
//					$end_day = ($offset + (budget_interval * ($localStorage.sheet_views)));						// go forward 'sheet views'
//				} else if ($interval < 0) {
//					$start_day = ($offset - (budget_interval * ($localStorage.sheet_views - $interval)));		// - 'sheet_views' entries and adjust for interval
//					$end_day = ($offset - (budget_interval * ($localStorage.sheet_views - $interval - 1)));		// + 'sheet_views' entries and adjust for interval
//				} else if ($interval > 0) {
//					$start_day = ($offset + (budget_interval * ($localStorage.sheet_views + $interval - 1)));	// - 'sheet_views' entries and adjust for interval
//					$end_day = ($offset + (budget_interval * ($localStorage.sheet_views + $interval)));			// + 'sheet_views' entries and adjust for interval
//				}
//				$sd = date('Y-m-d', strtotime($this->budget_start_date . " +" . $start_day . " Days"));
//				$ed = date('Y-m-d', strtotime($this->budget_start_date . " +" . $end_day . " Days"));
			break;
		case 'semi-monthy':
			budget_interval = 1;
			budget_interval_unit = 'Months';
			break;
		case 'monthly':
			budget_interval_unit = 'Months';

			var start = new Date();
			start.setDate(1);
			start.setMonth(start.getMonth() - (this.$localStorage.sheet_views/2) + 1);
			start.setDate(1);
			start.setHours(0);
			start.setMinutes(0);
			start.setSeconds(0);
			start.setMilliseconds(0);

			var end = new Date();
			end.setDate(1);
			end.setMonth(end.getMonth() + (this.$localStorage.sheet_views/2) + 1);
			end.setDate(0);
			end.setHours(0);
			end.setMinutes(0);
			end.setSeconds(0);
			end.setMilliseconds(0);
			break;
		default:
//			$this->ajax->addError(new AjaxError("Invalid budget_mode setting (sheet/loadAll)"));
//			$this->ajax->output();
	}

	self.periods = [];
	self.period_start = 0;

	var output = [], o_idx = 0;
	var running_total = this.data.balance_forward;
	var idx = 0;
	var transaction_date;
	while (start.getTime() < end.getTime()) {
		var interval_beginning = start.toISOString().split('T')[0] + 'T00:00:00';
		switch (this.$localStorage.budget_mode) {
			case 'weekly':
				break;
			case 'bi-weekly':
				break
			case 'semi-monthly':
				break;
			case 'monthly':
				start.setMonth(start.getMonth() + 1);
				break;
		}
		var interval_ending = new Date(start.getFullYear(), start.getMonth(), start.getDate(), 0, 0, 0);
		interval_ending.setDate(0);
		interval_ending = interval_ending.toISOString().split('T')[0] + 'T23:59:59';
		output[o_idx] = {
			'accounts': [],
			'amounts': {},
			'balance_forward': running_total,
			'balances': {},
			'interval_beginning': interval_beginning,
			'interval_ending': interval_ending,
			'interval_total': 0,
			'running_total': running_total,
			'totals': {},
			'types': {},
			'transactions': {}								// individual transactions in this period
		};
		for(var x in self.$rootScope.bank_accounts) {
			output[o_idx].accounts[x] = {
				balance: null,
				bank_account_id: self.$rootScope.bank_accounts[x].id,
				name: self.$rootScope.bank_accounts[x].name,
				reconciled_date: null,
				balance_date: null
			};
		}

		if (this.data.result[idx]) {
			var trd = this.data.result[idx].transaction_date.split('-');
			transaction_date = new Date(trd[0], --trd[1], trd[2], 0, 0, 0, 0);

			while (transaction_date.getTime() < start.getTime()) {
				this.addTransactionToTotals(this.data.result[idx], output[o_idx]);

				idx++;
				if (!this.data.result[idx]) {
					break;
				}

				var trd = this.data.result[idx].transaction_date.split('-');
				transaction_date = new Date(trd[0], --trd[1], trd[2], 0, 0, 0, 0);
			}
			running_total = output[o_idx].running_total;
		}

		var dt = output[o_idx].interval_beginning.split('T');
		var dt = dt[0].split('-');
		var sd = new Date(dt[0], --dt[1], dt[2], 0, 0, 0, 0);
		var dt = output[o_idx].interval_ending.split('T');
		var dt = dt[0].split('-');
		var ed = new Date(dt[0], --dt[1], dt[2], 23, 59, 59, 0);
		var now = new Date();
		if (now >= sd && now <= ed) {
			output[o_idx].alt_ending = now;				// set alternative ending
			output[o_idx].current_interval = true;		// mark the current period
		}

		this._isReconciled(output[o_idx].accounts, sd, ed);

		o_idx++;
	}
	self.periods = output;
};

/**
 * @name addTransactionToTotals
 * @private
 * @param {Object} transaction
 * @param {Object} output
 * @returns {undefined}
 */
services.periods.prototype.addTransactionToTotals = function(transaction, output) {

	var self = this;

	if (transaction.splits) {
		// split transaction
		angular.forEach(transaction.splits, function(split, i) {
			self.addToTotals(split, output);
		});
	} else {
		this.addToTotals(transaction, output);
	}

	// save account balance
	for(var x = 0; x < output.accounts.length; x++) {
		if (output.accounts[x].bank_account_id == transaction.bank_account_id) {
			output.accounts[x].balance			= transaction.bank_account_balance;
			output.accounts[x].reconciled_date	= (transaction.reconciled_date) ? transaction.reconciled_date: null;
			output.accounts[x].balance_date		= transaction.transaction_date;
output.accounts[x].transaction_id = transaction.id
			break;
		}
	};
};

/**
 * Checks account balances to see if they are reconciled
 * @name _isReconciled
 * @private
 * @param {Object} accounts	accounts object
 * @param {Date} sd			start date for the period
 * @param {Date} ed			end date for the period
 * @returns {undefined}
 */
services.periods.prototype._isReconciled = function(accounts, sd, ed) {
	var now = new Date(new Date().setHours(0,0,0,0));
	angular.forEach(accounts,
		function(account) {
			var dc = false;
			if (account.date_closed) {
				var dt = account.date_closed.split('-');
				var dc = new Date(dt[0], --dt[1], dt[2], 0, 0, 0);
			}
			if (dc && +dc < +sd ) {
				account.reconciled = 98;
			} else {
				if (+ed <= +now) {
					if (account.reconciled_date) {
						var dt = account.balance_date.split('-');
						var bd = new Date(dt[0], --dt[1], dt[2]);				// balance date
						var dt = account.reconciled_date.split('-');
						var rd = new Date(dt[0], --dt[1], dt[2]);				// reconciled date
						if (+rd === +ed || +rd === +now || +rd >= +bd) {
							// if everything has been reconciled up to the period ending date...
							// ... OR reconciled date is today...
							// ... OR reconciled date is >= balance date
							account.reconciled = 2;
						} else {
							account.reconciled = 1;
						}
					} else {
						account.reconciled = (account.balance) ? 1: 99;
					}
				} else {
					account.reconciled = (+sd >= +now) ? 0: 3;
				}
			}
		});
};

/**
 * @method addToTotals
 * @private
 * @param {Object} data
 * @param {Object} output
 * @returns {undefined}
 */
services.periods.prototype.addToTotals = function(data, output) {

	var category_id = parseInt(data.category_id);

	var amount = parseFloat(data.amount);

	switch(data.transaction_type) {
		default:
		case 0:
			if (!output.types[category_id]) {
				output.types[category_id] = 1;
			} else {
				output.types[category_id] |= 1;
			}
			break;
		case 1:
			if (!output.types[category_id]) {
				output.types[category_id] = 2;
			} else {
				output.types[category_id] |= 2;
			}
			break;
		case 2:
			if (!output.types[category_id]) {
				output.types[category_id] = 4;
			} else {
				output.types[category_id] |= 4;
			}
			break;
	}

	// Save the individual transactions
	if (output.transactions[category_id]) {
		output.transactions[category_id].push(data);
	} else {
		output.transactions[category_id] = Array(data);
	}

	// Calculate running total
	switch (data.type) {
		case 'CREDIT':
		case 'DSLIP':
			output.interval_total += amount;
			output.running_total += amount;
			if (output.totals[category_id]) {
				output.totals[category_id] += amount;
			} else {
				output.totals[category_id] = amount;
			}
			break;
		case 'DEBIT':
		case 'CHECK':
			output.interval_total -= amount;
			output.running_total -= amount;
			if (output.totals[category_id]) {
				output.totals[category_id] -= amount;
			} else {
				output.totals[category_id] = -amount;
			}
			break;
	}
};

app.service('Periods',  [ "$q", "RestData2", '$rootScope', '$localStorage', services.periods]);

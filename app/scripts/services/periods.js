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
 * @name data
 * @private
 * @type {Array}
 */
services.periods.prototype.data = [];

/**
 * @name period_start
 * @private
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
			self.data = response.data;
			self.period_start = 0;
			deferred.resolve(response);
		},
		function (error) {
			deferred.reject(error);
		});
	} else {
		deferred.resolve(true);
	}
	return deferred.promise;
};

/**
 * @name getPeriod
 * @public
 * @param {number} index
 * @returns {Object}
 */
services.periods.prototype.getPeriod = function (index) {

	var idx = index + this.$rootScope.period_start;
	return this.$rootScope.periods[idx];
};

services.periods.prototype.clear = function () {

//	delete $rootScope.periods;
	this.data = [];
};

/**
 * @name buildPeriods
 * @public
 * @param {Object} data
 * @returns {undefined}
 */
services.periods.prototype.buildPeriods = function() {
console.log(this.data)
	var self = this;

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
			var end = new Date();
			end.setDate(1);
//			var start_month;
//			var end_month;
//			if (interval == 0) {
				start.setMonth(start.getMonth() - (this.$localStorage.sheet_views/2) + 1);
				start.setDate(1);
				end.setMonth(end.getMonth() + (this.$localStorage.sheet_views/2) + 1);
				end.setDate(0);
//			} else if (interval < 0) {
//				start_month = budget_interval * (this.$localStorage.sheet_views - interval - 1);		// - 'sheet_views' entries and adjust for interval
//			//	$start->sub(new DateInterval("P" . $start_month . "M"));
//				end_month = budget_interval * (this.$localStorage.sheet_views - interval);			// + 'sheet_views' entries and adjust for interval
//			//	$end->add(new DateInterval("P" . $end_month . "M"));
//			} else if (interval > 0) {
//				start_month = budget_interval * (this.$localStorage.sheet_views + interval);			// go back 'sheet views' and adjust for interval
//			//	$start->add(new DateInterval("P" . $start_month . "M"));
//				end_month = budget_interval * (this.$localStorage.sheet_views + interval + 1);		// go forward 'sheet views' and adjust for interval
//			//	$end->add(new DateInterval("P" . $end_month . "M"));
//			}
			start.setHours(0);
			start.setMinutes(0);
			start.setSeconds(0);
			start.setMilliseconds(0);
			end.setHours(0);
			end.setMinutes(0);
			end.setSeconds(0);
			end.setMilliseconds(0);
			break;
		default:
//			$this->ajax->addError(new AjaxError("Invalid budget_mode setting (sheet/loadAll)"));
//			$this->ajax->output();
	}

	self.$rootScope.periods = [];
	self.$rootScope.period_start = 0;

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
				if (this.data.result[idx].splits) {
					// split transaction
					angular.forEach(this.data.result[idx].splits, function(split, ii) {
						self.addToTotals(split, output[o_idx]);
					});
				} else {
					self.addToTotals(this.data.result[idx], output[o_idx]);
				}

				// save account balance
				for(var x = 0; x < output[o_idx].accounts.length; x++) {
					if (output[o_idx].accounts[x].bank_account_id == this.data.result[idx].bank_account_id) {
						output[o_idx].accounts[x].balance			= this.data.result[idx].bank_account_balance;
						output[o_idx].accounts[x].reconciled_date	= (this.data.result[idx].reconciled_date) ? this.data.result[idx].reconciled_date: null;
						output[o_idx].accounts[x].balance_date		= this.data.result[idx].transaction_date;
output[o_idx].accounts[x].transaction_id = this.data.result[idx].id
						break;
					}
				};

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
	self.$rootScope.periods = output;
//	return output;
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

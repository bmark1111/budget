'use strict'

app.service('Periods',  [ "$q", "RestData2", '$rootScope', '$localStorage', 

function ($q, RestData2, $rootScope, $localStorage) {
	/**
	 * @method get
	 * @public
	 * @param {type} interval
	 * @returns {$q@call;defer.promise}
	 */
	this.get = function (interval) {
		var deferred = $q.defer();

		$rootScope.periods = [];
		$rootScope.period_start = 0;

		RestData2().getSheetTransactions({ interval: interval },
			function (response) {
				response.data = buildPeriods(response.data, interval);
				deferred.resolve(response);
			},
			function (error) {
				deferred.reject(error);
			});
		return deferred.promise;
	};

	/**
	 * @method buildPeriods
	 * @private
	 * @param {Object} data
	 * @param {number} interval
	 * @returns {periods_L3.buildPeriods.periodsAnonym$1}
	 */
	function buildPeriods(data, interval) {
console.log(data);
		var budget_interval;
		var budget_interval_unit;

		switch ($localStorage.budget_mode) {
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
				var end = new Date();
				var start_month;
				var end_month;
				if (interval == 0) {
					start.setMonth(start.getMonth() - ($localStorage.sheet_views/2) + 1);
					start.setDate(1);
					end.setMonth(end.getMonth() + ($localStorage.sheet_views/2) + 1);
					end.setDate(0);
				} else if (interval < 0) {
					start_month = budget_interval * ($localStorage.sheet_views - interval - 1);		// - 'sheet_views' entries and adjust for interval
				//	$start->sub(new DateInterval("P" . $start_month . "M"));
					end_month = budget_interval * ($localStorage.sheet_views - interval);			// + 'sheet_views' entries and adjust for interval
				//	$end->add(new DateInterval("P" . $end_month . "M"));
				} else if (interval > 0) {
					start_month = budget_interval * ($localStorage.sheet_views + interval);			// go back 'sheet views' and adjust for interval
				//	$start->add(new DateInterval("P" . $start_month . "M"));
					end_month = budget_interval * ($localStorage.sheet_views + interval + 1);		// go forward 'sheet views' and adjust for interval
				//	$end->add(new DateInterval("P" . $end_month . "M"));
				}
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
//				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (sheet/loadAll)"));
//				$this->ajax->output();
		}
console.log(start)
console.log(end)
		var output = [], o_idx = 0;

		var idx = 0, running_total = data.balance_forward;
		var transaction_date;
		while (start.getTime() < end.getTime()) {
			var interval_beginning = start.toISOString().split('T')[0] + 'T00:00:00';
			switch ($localStorage.budget_mode) {
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
				'accounts': {},
				'amounts': {},
				'balance_forward': running_total,
				'balances': {},
				'interval_beginning': interval_beginning,
				'interval_ending': interval_ending,
				'interval_total': 0,
				'running_total': running_total,
				'totals': {},
				'types': {},
				'transactions': {}
			};
//console.log('======================')
			if (data.result[idx]) {
				var trd = data.result[idx].transaction_date.split('-');
				transaction_date = new Date(trd[0], --trd[1], trd[2]);
				transaction_date.setHours(0);
				transaction_date.setMinutes(0);
				transaction_date.setSeconds(0);
				transaction_date.setMilliseconds(0);
				while (transaction_date.getTime() < start.getTime()) {
//console.log('-------------------')
//console.log("transaction_date = "+transaction_date)
//console.log("start = "+start)
//console.log(transaction_date.getTime())
//console.log(start.getTime())
					if (data.result[idx].splits) {
						// split transaction
						angular.forEach(data.result[idx].splits, function(split, ii) {
							addToTotals(split, output[o_idx]);
						});
					} else {
						addToTotals(data.result[idx], output[o_idx]);
					}

					idx++;
					if (data.result[idx]) {
						var trd = data.result[idx].transaction_date.split('-');
						transaction_date = new Date(trd[0], --trd[1], trd[2]);
						transaction_date.setHours(0);
						transaction_date.setMinutes(0);
						transaction_date.setSeconds(0);
						transaction_date.setMilliseconds(0);
					} else {
						break;
					}
				}
				running_total = output[o_idx].running_total;
			}
			o_idx++;
		}
		
		return output;
	};

	/**
	 * @method addToTotals
	 * @private
	 * @param {Object} data
	 * @param {Object} output
	 * @returns {undefined}
	 */
	function addToTotals(data, output) {

		var category_id = parseInt(data.category_id);

		var amount = parseFloat(data.amount);

		output.types[category_id] = 0;		// 0 = only actual transactions conatined in this period 
		if (output.transactions[category_id]) {
			output.transactions[category_id].push(data);
		} else {
			output.transactions[category_id] = Array(data);
		}
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

}]);
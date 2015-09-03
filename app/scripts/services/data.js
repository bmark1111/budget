app.factory('RestData2', function ($resource, $localStorage)
{
	return function()
	{
		var headers = {
				Authorization:		$localStorage.authorization,
				'TOKENID':			$localStorage.token_id,
				'X-Requested-With':	'XMLHttpRequest'
			}

		return $resource('//rest.budget.loc/data/:object/:action/:param', {}, {
				getTransactions:			{ method: 'GET', headers: headers, params: {object: 'budget', action: 'load', param: null} },
				getTheseTransactions:		{ method: 'GET', headers: headers, params: {object: 'budget', action: 'these', param: null} },

				getYTDTotals:				{ method: 'GET', headers: headers, params: {object: 'dashboard', action: 'ytdTotals', param: null} },
				getYTDTransactions:			{ method: 'GET', headers: headers, params: {object: 'dashboard', action: 'these', param: null} },

				getAllTransactions:			{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'loadAll', param: null} },
				deleteTransaction:			{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'delete', param: null} },
				editTransaction:			{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'edit', param: null} },
				saveTransaction:			{ method: 'POST', headers: headers, params: {object: 'transaction', action: 'save', param: null} },

				getAllForecasts:			{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'loadAll', param: null} },
				deleteForecast:				{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'delete', param: null} },
				editForecast:				{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'edit', param: null} },
				saveForecast:				{ method: 'POST', headers: headers, params: {object: 'forecast', action: 'save', param: null} },

				getAllUploads:				{ method: 'GET', headers: headers, params: {object: 'upload', action: 'loadAll', param: null} },
				getUploadedTransaction:		{ method: 'GET', headers: headers, params: {object: 'upload', action: 'assign', param: null} },
				postUploadedTransaction:	{ method: 'POST', headers: headers, params: {object: 'upload', action: 'post', param: null} },
				deleteUploadedTransaction:	{ method: 'GET', headers: headers, params: {object: 'upload', action: 'delete', param: null} },
				getUploadCounts:			{ method: 'GET', headers: headers, params: {object: 'upload', action: 'counts', param: null} },

				getAllBanks:				{ method: 'GET', headers: headers, params: {object: 'bank', action: 'load', param: null} },
				editBank:					{ method: 'GET', headers: headers, params: {object: 'bank', action: 'edit', param: null} },
				saveBank:					{ method: 'POST', headers: headers, params: {object: 'bank', action: 'save', param: null} },
				deleteBank:					{ method: 'GET', headers: headers, params: {object: 'bank', action: 'delete', param: null} },
				getBankAccounts:			{ method: 'GET', headers: headers, params: {object: 'bank', action: 'accounts', param: null} },

				getAllCategories:			{ method: 'GET', headers: headers, params: {object: 'category', action: 'load', param: null} },
				editCategory:				{ method: 'GET', headers: headers, params: {object: 'category', action: 'edit', param: null} },
				saveCategory:				{ method: 'POST', headers: headers, params: {object: 'category', action: 'save', param: null} },
				deleteCategory:				{ method: 'GET', headers: headers, params: {object: 'category', action: 'delete', param: null} },
				getCategories:				{ method: 'GET', headers: headers, params: {object: 'category', action: '', param: null} },

				getSetting:					{ method: 'GET', headers: headers, params: {object: 'setting', action: 'load', param: null} },

				logout:						{ method: 'POST', headers: headers, params: {object: 'logout', action: null, param: null} }
			});
	}
});

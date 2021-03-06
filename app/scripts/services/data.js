app.factory('RestData2', function ($resource, $localStorage, Config) {

	return function() {
		var headers = {
				Authorization:		$localStorage.authorization,
				TOKENID:			$localStorage.token_id,
				ACCOUNTID:			$localStorage.account_id,
				'X-Requested-With':	'XMLHttpRequest'
			}

		return $resource(Config.get('api_url'), {}, {
			// Admin Functions
				getAllUsers:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'user', action: 'load', param: null} },
				deleteUser:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'user', action: 'delete', param: null} },
				editUser:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'user', action: 'edit', param: null} },
				saveUser:					{ method: 'POST', headers: headers, params: {path: 'data', object: 'user', action: 'save', param: null} },
				
				runUtility:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'utility', action: null, param: null} },

			// user Functions
				getTransactions:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'budget', action: 'load', param: null} },
				getTheseTransactions:		{ method: 'GET', headers: headers, params: {path: 'data', object: 'budget', action: 'these', param: null} },

				getSheet:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'sheet', action: 'load', param: null} },

				getYTDTotals:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'dashboard', action: 'ytdTotals', param: null} },
				getYTDTransactions:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'dashboard', action: 'these', param: null} },

				liveSearch:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'livesearch', action: null, param: null} },

				getAllTransactions:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'transaction', action: 'loadAll', param: null} },
				deleteTransaction:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'transaction', action: 'delete', param: null} },
				editTransaction:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'transaction', action: 'edit', param: null} },
				saveTransaction:			{ method: 'POST', headers: headers, params: {path: 'data', object: 'transaction', action: 'save', param: null} },

				getAllVendors:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'vendor', action: 'get', param: null} },
				deleteVendor:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'vendor', action: 'delete', param: null} },
				editVendor:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'vendor', action: 'edit', param: null} },
				saveVendor:					{ method: 'POST', headers: headers, params: {path: 'data', object: 'vendor', action: 'save', param: null} },

				getAllRepeats:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'repeat', action: 'get', param: null} },
				deleteRepeat:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'repeat', action: 'delete', param: null} },
				editRepeat:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'repeat', action: 'edit', param: null} },
				saveRepeat:					{ method: 'POST', headers: headers, params: {path: 'data', object: 'repeat', action: 'save', param: null} },

				getAllForecasts:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'forecast', action: 'loadAll', param: null} },
				deleteForecast:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'forecast', action: 'delete', param: null} },
				editForecast:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'forecast', action: 'edit', param: null} },
				saveForecast:				{ method: 'POST', headers: headers, params: {path: 'data', object: 'forecast', action: 'save', param: null} },

				getAllUploads:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'upload', action: 'loadAll', param: null} },
				getUploadedTransaction:		{ method: 'GET', headers: headers, params: {path: 'data', object: 'upload', action: 'assign', param: null} },
				postUploadedTransaction:	{ method: 'POST', headers: headers, params: {path: 'data', object: 'upload', action: 'post', param: null} },
				deleteUploadedTransaction:	{ method: 'GET', headers: headers, params: {path: 'data', object: 'upload', action: 'delete', param: null} },
				getUploadCounts:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'upload', action: 'counts', param: null} },

				getAllBanks:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'bank', action: 'load', param: null} },
				editBank:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'bank', action: 'edit', param: null} },
				saveBank:					{ method: 'POST', headers: headers, params: {path: 'data', object: 'bank', action: 'save', param: null} },
				deleteBank:					{ method: 'GET', headers: headers, params: {path: 'data', object: 'bank', action: 'delete', param: null} },
				getBankAccounts:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'bank', action: 'accounts', param: null} },
				reconcileTransactions:		{ method: 'POST', headers: headers, params: {path: 'data', object: 'rest', action: 'reconcileTransactions', param: null} },

				getAllCategories:			{ method: 'GET', headers: headers, params: {path: 'data', object: 'category', action: 'load', param: null} },
				editCategory:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'category', action: 'edit', param: null} },
				saveCategory:				{ method: 'POST', headers: headers, params: {path: 'data', object: 'category', action: 'save', param: null} },
				deleteCategory:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'category', action: 'delete', param: null} },
				getCategories:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'category', action: '', param: null} },

				getSettings:				{ method: 'GET', headers: headers, params: {path: 'data', object: 'setting', action: 'load', param: null} },
				saveSettings:				{ method: 'POST', headers: headers, params: {path: 'data', object: 'setting', action: 'save', param: null} },

				logout:						{ method: 'POST', headers: headers, params: {path: 'data', object: 'logout', action: null, param: null} },

				login:						{ method: 'POST', headers: headers, params: {path: 'login', object: null, action: null, param: null} },
				register:					{ method: 'POST', headers: headers, params: {path: 'register', object: null, action: null, param: null} }
			});
	}
});

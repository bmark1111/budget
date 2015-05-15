app.factory('RestData', function ($resource)
{
	// Public API here
	var headers = {
		'Authorization'		  : 'Basic cHJvb3ZlYmlvOlByb292ZTIwMTQ=',
		'SESSIONID'			  : '1234567890',//$cookies.SESSIONID,
		'X-Requested-With'   : 'XMLHttpRequest'
	};

	return $resource('//rest.budget.loc/data/:object/:action/:param', {}, {
		  getTransactions:				{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'load', param: null} },
		  getTheseTransactions:			{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'these', param: null} },
		  deleteTransaction:			{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'delete', param: null} },
		  editTransaction:				{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'edit', param: null} },
		  saveTransaction:				{ method: 'POST', headers: headers, params: {object: 'transaction', action: 'save', param: null} },
		  getThisForecast:				{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'this', param: null} },
		  getForecast:					{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'load', param: null} },
		  getAllTransactions:			{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'loadAll', param: null} },
		  getAllUploads:				{ method: 'GET', headers: headers, params: {object: 'upload', action: 'loadAll', param: null} },
		  getUploadedTransaction:		{ method: 'GET', headers: headers, params: {object: 'upload', action: 'assign', param: null} },
		  postUploadedTransaction:		{ method: 'POST', headers: headers, params: {object: 'upload', action: 'post', param: null} },
		  deleteUploadedTransaction:	{ method: 'GET', headers: headers, params: {object: 'upload', action: 'delete', param: null} },
		  getUploadCounts:				{ method: 'GET', headers: headers, params: {object: 'upload', action: 'counts', param: null} },
		  getCategories:				{ method: 'GET', headers: headers, params: {object: 'category', action: '', param: null} },
		  getBankAccounts:				{ method: 'GET', headers: headers, params: {object: 'bank', action: 'accounts', param: null} },
		  getSetting:					{ method: 'GET', headers: headers, params: {object: 'setting', action: 'load', param: null} }
	  });

});
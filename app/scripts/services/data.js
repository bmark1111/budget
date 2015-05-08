app.factory('RestData', function ($resource)
{
	// Public API here
	var headers = {
		'Authorization'		  : 'Basic cHJvb3ZlYmlvOlByb292ZTIwMTQ=',
		'SESSIONID'			  : '1234567890',//$cookies.SESSIONID,
		'X-Requested-With'   : 'XMLHttpRequest'
	};

	return $resource('//rest.budget.loc/data/:object/:action/:param', {}, {
		  getTransactions:		{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'load', param: null} },
		  getTheseTransactions:	{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'these', param: null} },
		  deleteTransaction:	{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'delete', param: null} },
		  editTransaction:		{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'edit', param: null} },
		  saveTransaction:		{ method: 'POST', headers: headers, params: {object: 'transaction', action: 'save', param: null} },
		  getThisForecast:		{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'this', param: null} },
		  getForecast:			{ method: 'GET', headers: headers, params: {object: 'forecast', action: 'load', param: null} },
		  getAllTransactions:	{ method: 'GET', headers: headers, params: {object: 'transaction', action: 'loadAll', param: null} },
		  getAllUploads:		{ method: 'GET', headers: headers, params: {object: 'upload', action: 'loadAll', param: null} },
		  getSetting:			{ method: 'GET', headers: headers, params: {object: 'setting', action: 'load', param: null} },
		  getCounts:			{ method: 'GET', headers: headers, params: {object: 'upload', action: 'counts', param: null} }
	  });

});
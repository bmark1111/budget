app.service('fileUpload', ['$http', '$localStorage', function ($http, $localStorage) {

	this.uploadFileToUrl = function(file, uploadUrl) {
		var fd = new FormData();
		fd.append('file', file);
		return $http.post(uploadUrl, fd, {
			transformRequest: angular.identity,
			headers: {
				'Content-Type':		undefined,
				Authorization:		$localStorage.authorization,
				TOKENID:			$localStorage.token_id,
				ACCOUNTID:			$localStorage.account_id,
				'X-Requested-With':	'XMLHttpRequest'
			}
		})
		.success(function(data) {
			console.log('upload success');
		})
		.error(function(data) {
			console.log('upload failure');
		});
	}
}]);

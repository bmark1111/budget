app.service('fileUpload', ['$http', function ($http)
{
	this.uploadFileToUrl = function(file, uploadUrl)
	{
		var fd = new FormData();
		fd.append('file', file);
		return $http.post(uploadUrl, fd,
		{
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined}
		})
		.success(function(data)
		{
			console.log('upload success');
		})
		.error(function(data)
		{
			console.log('upload failure');
		});
	}
}]);

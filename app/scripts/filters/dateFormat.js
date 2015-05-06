app.filter('displayDate', function ()
{
	return function (input)
	{
console.log(input);
		return '01/01/2015';
	};
});
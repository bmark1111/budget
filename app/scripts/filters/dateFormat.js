app.filter('displayDate', function ()
{
	return function (input)
	{
console.log('displayDate - input = '+input);
		return '01/01/2015';
	};
});
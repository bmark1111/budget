<?php

function ep_exception_handler($exception)
{
	 // Add Code For Exception Handler Here

	$_error =& load_class('Exceptions', 'core');

	$_error->logException($exception);

	// display error page
	die($_error->show_error('Application Exception', $exception->getMessage()));
}

set_exception_handler('ep_exception_handler');

//EOF
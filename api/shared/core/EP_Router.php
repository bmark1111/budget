<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/* load the MX_Router class */
require SHAREPATH."third_party/MX/Router.php";

class EP_Router extends MX_Router
{
	public function __construct()
	{
		parent::__construct();
	}
}

// EOF
<?php
/*
 * locations.php *
 * Brian Markham 06-24-2014
 *
*/
class locations extends Nagilum
{
    public $table = 'tblImportSalesForceLocation';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}
//EOF

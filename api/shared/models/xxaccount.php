<?php
/*
 * accounts.php *
 * Brian Markham 05-02-2014
 *
*/
class accounts extends Nagilum
{
    public $table = 'tblImportSalesForceAccount';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}
//EOF

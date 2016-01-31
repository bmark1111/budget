<?php
/*
 * contacts.php *
 * Brian Markham 06-23-2014
 *
*/
class contacts extends Nagilum
{
    public $table = 'tblImportSalesForceContact';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
    }
}
//EOF

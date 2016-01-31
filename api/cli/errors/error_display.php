<html>
	<head>
	<title>Error Encountered</title>
	<link href="/css/base.css" type="text/css" rel="stylesheet"/>

<style type="text/css">

/* from admin/error_view */
nav#sub
{
	background: -moz-linear-gradient(center bottom , #000000 0%, #333333 0%, #000000 100%) repeat scroll 0 0 transparent;
}

nav#sub li
{
	cursor: pointer;
}

li.monospace
{
	font-family: 'courier new', 'monospace' !important;
}

#errorContainer
{
	background-color: #fff;
	margin: 10px 10px 0 10px;
}

#err-details
{
	border-collapse: collapse;
	margin: 5px 10px 20px 10px;
	font-size: 13px;
	font-family: sans-serif;
}

#err-details tr:hover
{
	background: inherit;
	cursor: auto;
}

#err-details td
{
	padding-top: 4px;
	padding-right: 10px;
	padding-bottom: 4px;
	color: white;
}

#err-details .label
{
	color: #ccc;
}

#err-details a
{
	color: #7af;
}

#err-details .url
{
	font-size: 20px;
}

#err-details .odd,#err-details .odd:hover
{
	background: #191919;
}

section.nonbt
{
	padding: 15px;
}


/* from EP_Debug */
.backtrace a
{
	color: #00f;
}

.backtrace
{
    text-align: left;
    background-color: #666;
    border: 5px solid #000;
}

.backtrace-inner
{
    background-color: #000;
    color: #ccc;
    margin: 10px;
    font-size: 13px;
}

.backtrace-head-mesg
{
    font-size: 20px;
    font-weight: bold;
    color: #adf;
    margin: 0 10px;
    padding: 10px 0;    
}

.backtrace .error-msg
{
    margin: 0 0 15px 10px;
    color: #ddd;
}

.backtrace .call
{
    border: 0px solid #999;
    padding: 10px 0;
    margin: 0 10px;
    border-bottom: 1px dotted #ccc;
}

.backtrace .call-args
{
    margin: 15px 0 0 0;
}

.backtrace .call .file, .backtrace .call .line
{
    font-weight: bold;
    color: #fff;
}

.backtrace .call .class, .backtrace .call .function, .backtrace .call .type, .backtrace .call .file, .backtrace .call .line
{
    font-family: 'courier new';
    font-size: 13px;
}

.backtrace .call .function .name
{
    color: #0bb;
}

.backtrace .call .function .prnths
{
    color: #b0b;
}


/* fixups for error view */
nav#sub { background: transparent }
/* nav#sub ul.subMenu { background-color: transparent; } */

</style>
	</head>
	<body>
<div style="padding-left:20px; padding-right:20px; margin:0 0 10px 0;">

<?php

    $errorType = array (
               E_ERROR            => 'ERROR',
               E_WARNING        => 'WARNING',
               E_PARSE          => 'PARSING ERROR',
               E_NOTICE         => 'NOTICE',
               E_CORE_ERROR     => 'CORE ERROR',
               E_CORE_WARNING   => 'CORE WARNING',
               E_COMPILE_ERROR  => 'COMPILE ERROR',
               E_COMPILE_WARNING => 'COMPILE WARNING',
               E_USER_ERROR     => 'USER ERROR',
               E_USER_WARNING   => 'USER WARNING',
               E_USER_NOTICE    => 'USER NOTICE',
               E_STRICT         => 'STRICT NOTICE',
               E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
               );

    // create error message
	$aLastErr = error_get_last();
	$errno = intval($aLastErr['type']);
    if (array_key_exists($errno, $errorType))
        $err = $errorType[$errno];
    else
        $err = 'UNCAUGHT EXCEPTION';

    $sErrMsg = $err . ': ' . $aErrorInfo['msg'];

	$aErrData = array();

	foreach (array('get','post','session') as $sErrName)
	{
		$sAttrName = $sErrName . '_array';
		$val = unserialize($aErrorInfo[$sAttrName]);
		$aErrorInfo[$sErrName] = var_export($val, TRUE);
		$aErrorInfo[$sErrName . ' count'] = is_array($val) ? count($val) : 0;
	}

	// TODO: move this into the error_log model so it can be used for reporting
	// errors as well as viewing errors in the admin console

	$aErrorInfo['url'] = 'https://' . $aErrorInfo['http_host'] . $aErrorInfo['uri'];
	$aErrorInfo['user_agent'] = $aErrorInfo['user_agent'];
	$aErrorInfo['remote_addr'] = $aErrorInfo['remote_addr'];
	
	$aErrorInfo['account'] = '[none]';			// TODO: implement
	$aErrorInfo['user'] = '[none]';				// TODO: implement
	
	// output the error information
	echo $aErrorInfo['backtrace'];
?>

</div>
<!-- <script type="text/javascript" src="/js/jquery-1.4.2.min.js?v=20110104.1"></script> -->
<script src="/js/jquery.min.js"></script>

<script type="text/javascript" src="/js/ui/jquery-ui-1.8.2.js?v=20110104.1"></script>

<script type="text/javascript">
var err_data = <?php echo json_encode($aErrorInfo); ?>;
$(document).ready(function()
{
	$('.backtrace-head-mesg:eq(1)').remove();
	$('.error-msg').after('<nav id="sub"><ul class="subMenu">' +
		'<li class="nochange bt currentItem">Backtrace</li>' +
		'<li class="monospace get">$_GET</li>' +
		'<li class="monospace post">$_POST</li>' +
		'<li class="monospace session">$_SESSION</li>'
		);
		
	$('.subMenu li').click(function()
	{
		showSub($(this).attr('class').split(' ')[1]);
	});

	$('.subMenu li').each(function(i, li)
	{
		var the_count = err_data[$(this).attr('class').split(' ')[1] + ' count'];

		if (the_count > 0)
			$(li).append(' (' + the_count + ')');
	});
	
	$('.error-msg').after('<table id="err-details"><tbody>' +
		'<tr class="odd"><td class="label">URL:</td><td class="url">' + err_data['url'] + '</td></tr>' +
		'<tr class="even"><td class="label">User Agent:</td><td>' + err_data['user_agent'] + '</td></tr>' +
		'<tr class="odd"><td class="label">IP address:</td><td>' + err_data['remote_addr'] + '</td></tr>' +
		'<tr class="even"><td class="label">Account:</td><td>' + err_data['account'] + '</td></tr>' +
		'<tr class="odd"><td class="label">User:</td><td class="user">' + err_data['user'] + '</td></tr>' +
		'</tbody></table>');
//console.log("href = " + err_data['url']);
	$('#err-details .url').wrap(function()
	{
		return $('<a>').attr({ href: err_data['url'], target: '_blank'});
	});
	
	if (err_data['user_link'])
	{
		$('#err-details .user').wrap(function() {
			return $('<a>').attr({ href: err_data['user_link'], target: '_blank'});
		});
	}

//	$('.call')
//		.wrapAll('<section class="sub-bt">');
});

$('.backtrace-inner')
	.append($('<section class="sub-get nonbt">$_GET array:<p/><pre>').find('pre').html(err_data['get']).end().hide())
	.append($('<section class="sub-post nonbt">$_POST array:<p/><pre>').find('pre').html(err_data['post']).end().hide())
	.append($('<section class="sub-session nonbt">$_SESSION array:<p/><pre>').find('pre').html(err_data['session']).end().hide());

function showSub(subname)
{
	$('.backtrace-inner section.sub-' + subname).show()
		.siblings('section').hide();

	$('.subMenu li.' + subname).addClass('currentItem').siblings().removeClass('currentItem');
}

</script>
</body>
</html>
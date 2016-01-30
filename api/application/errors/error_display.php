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
<!-- <script type="text/javascript" src="/js/jquery-1.4.2.min.js?v=20110104.1"></script> -->
<script src="/js/jquery.min.js"></script>

<script type="text/javascript" src="/js/ui/jquery-ui-1.8.2.js?v=20110104.1"></script>

<script type="text/javascript">
var err_data = <?php echo json_encode($aErrorInfo); ?>;
$(document).ready(function()
{
	$('.subMenu li').click(function()
	{
		showSub($(this).attr('class').split(' ')[1]);
	});
});

function showSub(subname)
{
	$('.backtrace-inner section.sub-' + subname).show()
		.siblings('section').hide();

	$('.subMenu li.' + subname).addClass('currentItem').siblings().removeClass('currentItem');
}

</script>
</head>
<body>
<div style="padding-left:20px; padding-right:20px; margin:0 0 10px 0;">
	<div class="backtrace">
		<div class="backtrace-inner">
			<div class="backtrace-head-mesg">Oops! An error occurred:</div>
			<div class="error-msg"><?php echo $aErrorInfo['message'];?></div>
			<table id="err-details">
				<tbody>
					<tr class="odd">
						<td class="label">URL:</td>
						<td class="url"><?php echo base_url();?><?php echo $aErrorInfo['uri'];?></td>
					</tr>
					<tr class="even">
						<td class="label">User Agent:</td>
						<td><?php
						if(isset($aErrorInfo['user_agent']))
						{
							echo $aErrorInfo['user_agent'];
						}
						?></td>
					</tr>
					<tr class="odd">
						<td class="label">IP address:</td>
						<td><?php
						if(isset($aErrorInfo['remote_address']))
						{
							echo $aErrorInfo['remote_address'];
						}
					 	?></td>
					</tr>
					<tr class="even">
						<td class="label">Account:</td>
						<td><?php
						if($aErrorInfo['account_id'] && APPLICATION == 'REST')
						{
							$account = new account($aErrorInfo['account_id']);
							echo $account->name;
						}
						?></td>
					</tr>
					<tr class="odd">
						<td class="label">User:</td>
						<td class="user">
						<?php
						if($aErrorInfo['user_id'] && APPLICATION == 'REST')
						{
							$user = new user($aErrorInfo['user_id']);
							echo $user->first_name . ' ' . $user->last_name;
						}
						?>
						</td>
					</tr>
				</tbody>
			</table>
			<nav id="sub">
				<ul class="subMenu">
					<li class="nochange bt currentItem">Backtrace</li>
					<li class="monospace get">$_GET <?php
					if(isset($_GET))
					{
						echo '(' . count($_GET) . ')';
					}
					?></li>
					<li class="monospace post">$_POST <?php
					if(isset($_POST))
					{
						echo '(' . count($_POST) . ')';
					}
					?></li>
					<li class="monospace session">$_SESSION <?php
					if(isset($_SESSION))
					{
						echo '(' . count($_SESSION) . ')';
					}
					?></li>
				</ul>
			</nav>
			<section class="sub-bt nonbt">Backtrace Information:<br />
			<pre>
<?php
if(isset($aErrorInfo['backtrace']))
{
	echo $aErrorInfo['backtrace'];
}
?>
			</pre>
			</section>
			<section class="sub-get nonbt" style="display: none; ">$_GET array:<br />
			<pre>
<?php
if(isset($aErrorInfo['get_array']))
{
	$get_array = unserialize($aErrorInfo['get_array']);
	print_r($get_array);
}
?>
			</pre>
			</section>
			<section class="sub-post nonbt" style="display: none; ">$_POST array:<br />
			<pre>
<?php
if(isset($aErrorInfo['post_array']))
{
	$post_array = unserialize($aErrorInfo['post_array']);
	print_r($post_array);
}
?>
			</pre>
			</section>
			<section class="sub-session nonbt" style="display: none; ">$_SESSION array:<br />
			<pre>
<?php
if(isset($aErrorInfo['session_array']))
{
	$session_array = unserialize($aErrorInfo['session_array']);
	print_r($session_array);
}
?>
			</pre>
			</section>
		</div>
	</div>
</div>
</body>
</html>
<?php
die('clock');
echo("\r\n");
$realtime = microtime(true);
echo("\rSyncronizing Clock");
while(($realtime - floor($realtime)) > .001)
{
        $realtime = microtime(true);
}
echo("\r\n");
$time = date('D g:i:s A');
echo("\r".$time);
while(true)
{
        $new_time = date('D g:i:s A');
        if ($new_time != $time)
        {
                echo("\r". $new_time);
                $time = $new_time;
        }
}

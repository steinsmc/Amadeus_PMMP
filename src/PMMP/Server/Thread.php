<?php
$sid = $argv[1];
$cache = $argv[2];
$id = $argv[3];
$directory = $argv[4];
$base = $argv[5];
@cli_set_process_title('Amadeus Pocketmine-MP Support Plugin Worker Thread' . $sid);
@unlink($cache . '/server' . $sid . '.stop');
$user = posix_getpwnam('server' . $sid);
$uid = $user['uid'];
$gid = $user['gid'];
posix_setuid($uid);
posix_setgid($gid);
posix_seteuid($uid);
posix_setegid($gid);
$pipe = msg_get_queue($id);
@unlink($cache . '/runtime' . '/server' . $sid . '.pid');
@unlink($cache . '/server' . $sid . '.stdout');
@unlink($cache . '/server' . $sid . '.stderr');
$descriptorspec = array(
    array("pipe", "r"),
    array("file", $cache . '/server' . $sid . '.stdout', "w"),
    array("file", $cache . '/server' . $sid . '.stderr', "w")
);
$process = proc_open('exec ' . $directory . '/bin/php7/bin/php ' . $directory . '/Pocketmine-MP.phar', $descriptorspec, $pipes, $directory);
file_put_contents($cache . '/runtime' . '/server' . $sid . '.pid', proc_get_status($process)['pid']);
register_shutdown_function(function () use ($pipes, $process, $cache, $sid) {
    @fwrite($pipes[0], 'stop' . PHP_EOL);
    @fclose($pipes[0]);
    @proc_close($process);
    @unlink($cache . '/runtime' . '/server' . $sid . '.pid');
    @unlink($cache . '/server' . $sid . '.stdout');
    @unlink($cache . '/server' . $sid . '.stderr');
});
while (is_resource($process) && !file_exists($cache . '/server' . $sid . '.stop') && file_exists($base . '/Amadeus.pid')) {
    if (msg_stat_queue($pipe)['msg_qnum'] > 0) {
        msg_receive($pipe, 1, $msgType, 1024, $message);
        fwrite($pipes[0], $message);
    }
    usleep(250);
}
exit(0);
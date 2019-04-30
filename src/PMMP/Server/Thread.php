<?php
$loader = require($argv[4] . '/vendor/autoload.php');
$wid = $argv[1];
$writeQueue = msg_get_queue($wid);
$rid = $argv[6];
$readQueue = msg_get_queue($rid);
$user = posix_getpwnam('server' . $argv[3]);
$uid = $user['uid'];
$gid = $user['gid'];
posix_setuid($uid);
posix_setgid($gid);
posix_seteuid($uid);
posix_setegid($gid);
$descriptorspec = array(
    array("pipe", "r"),
    array("pipe", "w"),
    array("pipe", "w")
);
$process = proc_open('cd ' . $argv[2] . ' && ' . $argv[2] . '/bin/php7/bin/php ' . $argv[2] . '/Pocketmine-MP.phar 2>&1', $descriptorspec, $pipes, $argv[2]);
msg_send($writeQueue, 1, proc_get_status($process)['pid']);
sleep(1);
$loop = \React\EventLoop\Factory::create();
$write = $loop->addPeriodicTimer(0.1, function () use ($pipes, $writeQueue) {
    if (($content = fread($pipes[1], 1)) != null) {
        msg_send($writeQueue, 1, $content);
    }
    if (($content = fread($pipes[2], 1)) != null) {
        msg_send($writeQueue, 1, $content);
    }
});
$read = $loop->addPeriodicTimer(0.1, function () use ($pipes, $readQueue, $writeQueue) {
    if (msg_stat_queue($readQueue)['msg_qnum'] > 0) {
        msg_receive($readQueue, 1, $msgType, 1024, $message);
        msg_send($writeQueue, 1, $message);
    }
});
$checkDaemon = $loop->addPeriodicTimer(1, function () use ($loop, $pipes, $process, $write, $argv) {
    echo 233;
    if (!file_exists($argv[5])) {
        $loop->cancelTimer($write);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        echo "closed";
        exit(0);
    }
});
$loop->run();
while (is_resource($process)) {
    usleep(100);
}
$loop->cancelTimer($write);
$loop->cancelTimer($read);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);
$loop->cancelTimer($checkDaemon);
exit(0);
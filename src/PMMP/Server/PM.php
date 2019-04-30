<?php


namespace PMMP\Server;


use Amadeus\IO\Logger;
use Amadeus\Process;

class PM
{
    private
        $SID,
        $directory,
        $pluginDirectory;

    private $process;

    public function __construct(int $SID, string $Directory, string $PluginDirectory)
    {
        $this->SID = $SID;
        $this->directory = $Directory;
        $this->pluginDirectory = $PluginDirectory;
    }

    public function init(): bool
    {
        if (!file_exists($this->directory . '/bin/php7/bin/php')) {
            system('tar -zxvf ' . Process::getCache() . '/php7.2-linux.tar.gz -C ' . $this->directory . '/ 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to decompress php@7.2 library', Logger::LOG_FATAL);
                return false;
            }
        }
        if (!file_exists($this->directory . '/Pocketmine-MP.phar')) {
            system('cp -r ' . Process::getCache() . '/Pocketmine-MP.phar ' . $this->directory . '/Pocketmine-MP.phar 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to copy Pocketmine-MP@latest', Logger::LOG_FATAL);
                return false;
            }
        }
        system('chmod -R 700 ' . $this->directory, $ret);
        if ($ret != 0) {
            Logger::printLine('Failed to set permission', Logger::LOG_FATAL);
        }
        return true;
    }

    public function start(): int
    {
        $id = ftok(__FILE__, 'm');
        $msgQueue = msg_get_queue($id);
        exec('id server' . $this->SID . ' -u', $uid);
        exec('id server' . $this->SID . ' -g', $gid);
        $uid = $uid[0];
        $gid = $gid[0];
        Logger::printLine('server' . $this->SID . ' uid:' . $uid . ' gid:' . $gid);
        $pid = pcntl_fork();
        if ($pid == -1) {
            Logger::printLine('Failed to fork', Logger::LOG_FATAL);
        } elseif ($pid == 0) {
            $pid = posix_getpid();
            posix_setuid($uid);
            posix_setgid($gid);
            posix_seteuid($uid);
            posix_setegid($gid);
            $descriptorspec = array(
                array("pipe", "r"),
                array("pipe", "w"),
                array("pipe", "w")
            );
            $process = proc_open('cd ' . $this->directory . ' && ' . $this->directory . '/bin/php7/bin/php ' . $this->directory . '/Pocketmine-MP.phar 2>&1', $descriptorspec, $pipes, $this->directory);
            ob_end_flush();
            msg_send($msgQueue, 1, proc_get_status($process)['pid']);
            sleep(1);
            while (1) {
                if (($content = fread($pipes[1], 1024)) != null) {
                    msg_send($msgQueue, 1, $content);
                }
                if (($content = fread($pipes[2], 1024)) != null) {
                    msg_send($msgQueue, 1, $content);
                }
                usleep(100);
            }
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } else {
            $ret = msg_receive($msgQueue, 1, $msgType, 1024, $message);
            while(true){
                msg_receive($msgQueue, 1, $msgType, 1024, $message);
                Logger::printLine($message,Logger::LOG_INFORM);
            }
            return $message;
        }
        return 0;
    }

    public function stop()
    {

    }

    public function getSID()
    {
        return $this->SID;
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function __destruct()
    {

    }
}
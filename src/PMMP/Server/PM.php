<?php


namespace PMMP\Server;


use Amadeus\IO\Logger;
use Amadeus\Process;
use React\EventLoop\Factory;
use Swoole\Exception;

class PM
{
    private
        $SID,
        $directory,
        $pluginDirectory;

    private $process;
    private $pipe;

    public function __construct(int $SID, string $Directory, string $PluginDirectory)
    {
        $this->SID = $SID;
        $this->directory = $Directory;
        $this->pluginDirectory = $PluginDirectory;
    }

    public function init(): bool
    {
        if (!file_exists($this->directory . '/bin/php7/bin/php')) {
            system('tar -zxvf ' . Process::getCache() . '/php7.2-linux.tar.gz -C ' . $this->directory . '/ > /dev/null 2>&1', $ret);
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
        system('chown -R server' . $this->SID . ':server' . $this->SID . ' ' . $this->directory,$ret);
        if ($ret != 0) {
            Logger::printLine('Failed to set permission', Logger::LOG_FATAL);
        }
        system('chmod -R 700 ' . $this->directory, $ret);
        if ($ret != 0) {
            Logger::printLine('Failed to set permission', Logger::LOG_FATAL);
        }
        file_put_contents(Process::getCache() . '/server' . $this->SID . '.shm', '');
        return true;
    }

    public function start(): int
    {
        Logger::printLine('Pocketmine-MP starting', Logger::LOG_INFORM);
        $id = ftok(Process::getCache() . '/server' . $this->SID . '.shm', 'r');
        $this->pipe = msg_get_queue($id);
        $pid = pcntl_fork();
        if ($pid == -1) {
            Logger::printLine('Failed to fork', Logger::LOG_FATAL);
        } elseif ($pid == 0) {
            Logger::shutUp();
            ob_end_flush();
            $user = posix_getpwnam('server' . $this->SID);
            $uid = $user['uid'];
            $gid = $user['gid'];
            posix_setuid($uid);
            posix_setgid($gid);
            posix_seteuid($uid);
            posix_setegid($gid);
            @unlink(Process::getCache() . '/server' . $this->SID . '.pid');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stop');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stdout');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stderr');
            $descriptorspec = array(
                array("pipe", "r"),
                array("file", Process::getCache() . '/server' . $this->SID . '.stdout', "w"),
                array("file", Process::getCache() . '/server' . $this->SID . '.stderr', "w")
            );
            $this->process = proc_open('cd ' . $this->directory . ' && ' . $this->directory . '/bin/php7/bin/php ' . $this->directory . '/Pocketmine-MP.phar', $descriptorspec, $pipes, $this->directory);
            file_put_contents(Process::getCache() . '/server' . $this->SID . '.pid', proc_get_status($this->process)['pid']);
            while (is_resource($this->process) && !file_exists(Process::getCache() . '/server' . $this->SID . '.stop') && file_exists(Process::getBase() . '/Amadeus.pid')) {
                if (msg_stat_queue($this->pipe)['msg_qnum'] > 0) {
                    msg_receive($this->pipe, 1, $msgType, 1024, $message);
                    fwrite($pipes[0], $message);
                }
                usleep(50);
            }
            fwrite($pipes[0], 'stop' . PHP_EOL);
            fclose($pipes[0]);
            proc_close($this->process);
            @unlink(Process::getCache() . '/server' . $this->SID . '.pid');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stop');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stdout');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stderr');
            echo 'stopping server' . $this->SID;
            try{
                exit(0);
            }catch(Exception $e){
                return true;
            }
        }
        while (!file_exists(Process::getCache() . '/server' . $this->SID . '.pid')) {
            usleep(50);
        }
        //msg_send($this->pipe,1,''.PHP_EOL);
        $message = file_get_contents(Process::getCache() . '/server' . $this->SID . '.pid');
        return $message;
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
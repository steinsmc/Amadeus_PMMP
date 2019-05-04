<?php


namespace PMMP\Server;


use Amadeus\IO\Logger;
use Amadeus\Process;

/**
 * Class PM
 * @package PMMP\Server
 */
class PM
{
    /**
     * @var int
     */
    /**
     * @var int|string
     */
    /**
     * @var int|string
     */
    private
        $SID,
        $directory,
        $pluginDirectory;

    /**
     * @var
     */
    private $process;
    /**
     * @var
     */
    private $pipe;
    /**
     * @var
     */
    private $running;
    /**
     * @var
     */
    private $pid;

    /**
     * PM constructor.
     * @param int $SID
     * @param string $Directory
     * @param string $PluginDirectory
     */
    public function __construct(int $SID, string $Directory, string $PluginDirectory)
    {
        $this->SID = $SID;
        $this->directory = $Directory;
        $this->pluginDirectory = $PluginDirectory;
    }

    /**
     * @return bool
     */
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
        system('chown -R server' . $this->SID . ':server' . $this->SID . ' ' . $this->directory, $ret);
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

    /**
     * @return int
     */
    public function start(): int
    {
        Logger::printLine('Pocketmine-MP starting', Logger::LOG_INFORM);
        $id = ftok(Process::getCache() . '/server' . $this->SID . '.shm', 'r');
        $this->pipe = msg_get_queue($id);
        $this->process = popen('/usr/bin/env php ' . $this->pluginDirectory . '/src/PMMP/Server/Thread.php' . ' ' . $this->SID . ' ' . Process::getCache() . ' ' . $id . ' ' . $this->directory . ' ' . Process::getBase(), 'r');
        while (!file_exists(Process::getRuntime() . '/server' . $this->SID . '.pid')) {
            usleep(50);
        }
//        msg_send($this->pipe,1,''.PHP_EOL);
//        msg_send($this->pipe,1,'y'.PHP_EOL);
//        msg_send($this->pipe,1,'y'.PHP_EOL);
        $this->running = true;
        $this->pid = file_get_contents(Process::getRuntime() . '/server' . $this->SID . '.pid');
        return $this->pid;
    }

    /**
     * @return bool
     */
    public function stop(): bool
    {
        Logger::printLine('Pocketmine-MP stopping', Logger::LOG_INFORM);
        file_put_contents(Process::getCache() . '/server' . $this->SID . '.stop', '');
        return true;
    }

    /**
     *
     */
    public function tick()
    {
        system('kill -0 ' . $this->pid . ' >/dev/null 2>&1', $ret);
        if (!file_exists(Process::getCache() . '/server' . $this->SID . '.pid') || $ret != 0) {
            @unlink(Process::getCache() . '/server' . $this->SID . '.stop');
            @unlink(Process::getCache() . '/server' . $this->SIDk . '.stdout');
            @unlink(Process::getCache() . '/server' . $this->SID . '.stderr');
            @unlink(Process::getCache() . '/server' . $this->SID . '.pid');
            @pclose($this->process);
            $this->running = false;
        } else {
            $this->running = true;
        }
    }

    /**
     * @return int|string
     */
    public function getSID()
    {
        return $this->SID;
    }

    /**
     * @return int|string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->tick();
    }
}
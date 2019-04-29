<?php


namespace PMMP\Server;


use Amadeus\IO\Logger;
use Amadeus\Process;

class PM
{
    private
        $SID,
        $Directory,
        $PluginDirectory;

    public function __construct(int $SID, string $Directory, string $PluginDirectory)
    {
        $this->SID = $SID;
        $this->Directory = $Directory;
        $this->PluginDirectory = $PluginDirectory;
    }

    public function init(): bool
    {
        if (!file_exists($this->Directory . '/bin/php7/bin/php')) {
            system('tar -zxvf ' . Process::getCache() . '/php7.2-linux.tar.gz -C ' . $this->Directory . '/ 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to decompress php@7.2 library', Logger::LOG_FATAL);
                return false;
            }
        }
        if (!file_exists($this->Directory . '/Pocketmine-MP.phar')) {
            system('cp -r ' . Process::getCache() . '/Pocketmine-MP.phar ' . $this->Directory . '/Pocketmine-MP.phar 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to copy Pocketmine-MP@latest', Logger::LOG_FATAL);
                return false;
            }
        }
        return true;
    }

    public function getSID()
    {
        return $this->SID;
    }

    public function getDirectory()
    {
        return $this->Directory;
    }
}
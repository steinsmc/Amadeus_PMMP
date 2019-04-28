<?php


namespace PMMP\Server;


use Amadeus\IO\Logger;

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

    public function init()
    {
        if(!file_exists($this->Directory.'/bin/php7/bin/php')){
            system('tar -zxvf '.$this->PluginDirectory.'/res/php7.2-linux.tar.gz -C '.$this->Directory.'/ 2>&1',$ret);
            Logger::printLine($ret,Logger::LOG_INFORM);
        }
        if(!file_exists($this->Directory.'/Pocketmine-MP.phar')){
            system('wget https://jenkins.pmmp.io/job/PocketMine-MP/lastSuccessfulBuild/artifact/PocketMine-MP.phar -O '.$this->Directory.'/Pocketmine-MP.phar 2>&1',$ret);
            Logger::printLine($ret,Logger::LOG_INFORM);
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
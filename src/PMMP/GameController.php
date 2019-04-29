<?php


namespace PMMP;


use Amadeus\IO\Logger;
use Amadeus\Plugin\Listener\GameListener;
use Amadeus\Process;
use PMMP\Server\PM;

class GameController extends \Amadeus\Plugin\Game\GameController implements GameListener
{
    private $servers = array();
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
        Process::getPluginManager()->registerGameType('pm', $this);
    }

    public function onLoading()
    {
        Logger::printLine('Pocketmine-MP support for Amadeus is loading');
        if (!file_exists(Process::getCache() . '/php7.2-linux.tar.gz')) {
            file_put_contents(Process::getCache() . '/php.sh', file_get_contents('https://raw.githubusercontent.com/pmmp/php-build-scripts/master/compile.sh'));
            Logger::printLine('Building php@7.2 library', Logger::LOG_INFORM);
            system('cd ' . Process::getCache() . ' && sh php.sh -t linux64 -l -g -u -j4 -f x86_64 >> ' . Process::getBase() . '/Amadeus.log 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to build php@7.2 library', Logger::LOG_FATAL);
                return false;
            }
            system('cd ' . Process::getCache() . ' && tar -zcvf php7.2-linux.tar.gz ./bin', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to compress php@7.2 library', Logger::LOG_FATAL);
                return false;
            }
            //system('cd '.Process::getCache().' && rm -rf bin php.sh');
            Logger::printLine('Successfully built php@7.2 library', Logger::LOG_SUCCESS);
        }
        Logger::printLine('Found php@7.2 library', Logger::LOG_INFORM);
        if (!file_exists(Process::getCache() . '/Pocketmine-MP.phar')) {
            Logger::printLine('Downloading Pocketmine-MP@latest', Logger::LOG_INFORM);
            system('wget https://jenkins.pmmp.io/job/PocketMine-MP/lastSuccessfulBuild/artifact/PocketMine-MP.phar -O ' . Process::getCache() . '/Pocketmine-MP.phar >> ' . Process::getBase() . '/Amadeus.log 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to download Pocketmine-MP@latest', Logger::LOG_FATAL);
                return false;
            }
        }
        Logger::printLine('Found Pocketmine-MP@latest', Logger::LOG_INFORM);
        return true;
    }

    public function onLoaded()
    {
        Logger::printLine('Pocketmine-MP support for Amadeus is ready');
        return true;
    }

    public function initServer($sid)
    {
        Logger::printLine('Initializing server' . $sid);
        $this->servers[$sid] = new PM($sid, Process::getServerManager()->getServer($sid)->getDirectory(), $this->directory);
        $this->servers[$sid]->init();
        //file_exists(Process::getServerManager()->getServer($sid)->getDirectory() . '/php/bin/php');
    }

    public function getName()
    {
        return 'Pocketmine-MP support for Amadeus';
    }

    public function getServerType()
    {
        return 'pm';
    }

    public function onServerStart($sid)
    {
        Logger::printLine('server' . $sid . ' has started');
        return 1;
    }

    public function onServerStop($sid)
    {
        Logger::printLine('server' . $sid . ' has stopped');
    }

    public function onClientGetLog()
    {

    }

    public function finServer($sid)
    {
        Logger::printLine('server' . $sid . ' has gone');
    }
}
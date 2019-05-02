<?php


namespace PMMP;


use Amadeus\IO\Logger;
use Amadeus\Plugin\Listener\GameListener;
use Amadeus\Process;
use PMMP\Server\PM;

/**
 * Class GameController
 * @package PMMP
 */
class GameController extends \Amadeus\Plugin\Game\GameController implements GameListener
{
    /**
     * @var array
     */
    private $servers = array();
    /**
     * @var
     */
    private $directory;

    /**
     * GameController constructor.
     * @param $directory
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
        Process::getPluginManager()->registerGameType('pm', $this);
    }

    /**
     * @return bool|mixed
     */
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

    /**
     * @return bool|mixed
     */
    public function onLoaded()
    {
        Logger::printLine('Pocketmine-MP support for Amadeus is ready');
        return true;
    }

    /**
     * @param int $sid
     * @return bool
     */
    public function initServer(int $sid): bool
    {
        Logger::printLine('Initializing server' . $sid);
        $this->servers[$sid] = new PM($sid, Process::getServerManager()->getServer($sid)->getDirectory(), $this->directory);
        $this->servers[$sid]->init();
        return true;
    }

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return 'Pocketmine-MP support for Amadeus';
    }

    /**
     * @return string
     */
    public function getServerType(): string
    {
        return 'pm';
    }

    /**
     * @param int $sid
     * @return int
     */
    public function onServerStart(int $sid): int
    {
        $pid = $this->servers[$sid]->start();
        Logger::printLine('server' . $sid . ' has started');
        return $pid;
    }

    /**
     * @param int $sid
     * @return bool
     */
    public function onServerStop(int $sid): bool
    {
        $this->servers[$sid]->stop();
        Logger::printLine('server' . $sid . ' has stopped');
        return true;
    }

    /**
     * @param int $sid
     * @return mixed
     */
    public function onClientGetLog(int $sid)
    {
        return $this->servers[$sid]->getLog();
    }

    /**
     *
     */
    public function onServerTick()
    {
        foreach ($this->servers as $server) {
            $server->tick();
        }
    }

    /**
     * @param int $sid
     * @return bool
     */
    public function finServer(int $sid): bool
    {
        Logger::printLine('server' . $sid . ' has gone');
        return true;
    }
}
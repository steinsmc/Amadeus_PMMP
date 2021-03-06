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
        require_once($this->directory . '/src/PMMP/Server/PM.php');
        Process::getPluginManager()->registerGameType('pm', $this);
    }

    /**
     * @return bool|mixed
     */
    public function onLoading()
    {
        Logger::printLine('Pocketmine-MP support for Amadeus is loading');
        if (!file_exists(Process::getCache() . '/php7.3-linux.tar.gz')) {
            Logger::printLine('Downloading pre-built php@7.3 library', Logger::LOG_INFORM);
            system('wget https://raw.githubusercontent.com/steinsmc/php-build-scripts/master/php7.3-linux.tar.gz -O ' . Process::getCache() . '/php7.3-linux.tar.gz >> ' . Process::getBase() . '/Amadeus.log 2>&1', $ret);
            if ($ret != 0) {
                Logger::printLine('Failed to download php@7.3 library', Logger::LOG_DEADLY);
                Logger::printLine('Trying to build php@7.3 library', Logger::LOG_INFORM);
                file_put_contents(Process::getCache() . '/php.sh', file_get_contents('https://raw.githubusercontent.com/steinsmc/php-build-scripts/master/compile.sh'));
                Logger::printLine('Building php@7.3 library', Logger::LOG_INFORM);
                system('cd ' . Process::getCache() . ' && sh php.sh -t linux64 -l -g -u -j4 -f x86_64 >> ' . Process::getBase() . '/Amadeus.log 2>&1', $ret);
                if ($ret != 0) {
                    Logger::printLine('Failed to build php@7.3 library', Logger::LOG_FATAL);
                    return false;
                }else{
                system('cd ' . Process::getCache() . ' && tar -zcvf php7.3-linux.tar.gz ./bin > /dev/null', $ret);
                if ($ret != 0) {
                    Logger::printLine('Failed to compress php@7.3 library', Logger::LOG_FATAL);
                    return false;
                }
                Logger::printLine('Successfully built php@7.3 library', Logger::LOG_SUCCESS);
                //system('cd '.Process::getCache().' && rm -rf bin php.sh');
            }
            }else{
                Logger::printLine('Successfully downloaded php@7.3 library', Logger::LOG_SUCCESS);
            }
            Logger::printLine('Successfully installed php@7.3 library', Logger::LOG_SUCCESS);
        }
        Logger::printLine('Found php@7.3 library', Logger::LOG_INFORM);
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
        if ($this->servers[$sid] instanceof PM) {
            $pid = $this->servers[$sid]->start();
        } else {
            return -1;
        }
        Logger::printLine('server' . $sid . ' has started');
        return $pid;
    }

    /**
     * @param int $sid
     * @return bool
     */
    public function onServerStop(int $sid): bool
    {
        if ($this->servers[$sid] instanceof PM) {
            $this->servers[$sid]->stop();
        } else {
            return false;
        }
        Logger::printLine('server' . $sid . ' has stopped');
        return true;
    }

    /**
     * @param int $sid
     * @return mixed
     */
    public function onClientGetLog(int $sid): string
    {
        if ($this->servers[$sid] instanceof PM) {
            return $this->servers[$sid]->getLog();
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function onServerTick()
    {
        foreach ($this->servers as $sid => $server) {
            if ($server instanceof PM) {
                $server->tick();
            } else {
                unset($this->servers[$sid]);
            }
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
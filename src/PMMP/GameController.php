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
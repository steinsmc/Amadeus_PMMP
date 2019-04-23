<?php


namespace PMMP;


use Amadeus\IO\Logger;
use Amadeus\Plugin\Listener\GameListener;
use Amadeus\Process;

class GameController extends \Amadeus\Plugin\Game\GameController implements GameListener
{
    public function __construct(){
        Process::getPluginManager()->registerGameType('pm',$this);
    }
    public function onLoading(){
        Logger::printLine('Pocketmine-MP support for Amadeus is loading');
        return true;
    }
    public function onLoaded(){
        Logger::printLine('Pocketmine-MP support for Amadeus is ready');
        return true;
    }
    public function getName()
    {
        return 'Pocketmine-MP support for Amadeus';
    }
    public function getServerType()
    {
        return 'pm';
    }
    public function onServerStart()
    {
        // TODO: Implement onServerStart() method.
    }
    public function onServerStop()
    {
        // TODO: Implement onServerStop() method.
    }
}
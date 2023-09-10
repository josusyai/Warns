<?php

namespace josus;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use josus\FormAPI\CustomForm;
use josus\FormAPI\SimpleForm;
use pocketmine\utils\Config;

class Warn extends PluginBase implements Listener {
    public $target;
    public $warns;
    public $warnsplayer = [];

    public function onEnable(): void {
        $this->getLogger()->info("PLUGIN CARGADO BRO!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->message = (new Config($this->getDataFolder() . "Message.yml", config::YAML, array()));
        $this->saveResource("Config.yml");
        $this->update = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        @mkdir($this->getDataFolder());
        
        $this->Files(); 
    }

    public function Files () {
        @mkdir($this->getDataFolder()."Warning");	
        $this->warns = new Config($this->getDataFolder() . "Warning/Warnings.yml", Config::YAML);
    } 
    
    public function getWarnings() : Config {
        return $this->warns;
    }
    
    public function addWarn(string $n): bool {
        $this->getWarnings()->set($n, $this->getWarns($n) + 1);
        $this->getWarnings()->save();
        return true;
    }
    
    public function delWarn(string $n): bool {
        $this->getWarnings()->set($n, $this->getWarns($n) - 1);
        $this->getWarnings()->save();
        return true;
    }
    
    public function getWarns(string $n) : int {
        $warn = $this->getWarnings()->get($n);
        return $warn;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        switch($command->getName()){
            case "warn":
                if (!$sender->hasPermission("warns.cmd")){ 
                    $sender->sendMessage("No tienes permisos para este comando.");
                    return false;
                }
                $this->getWarnForm($sender);
                break;

            case "warnadd":
                if(!empty($args[0])) {
                    if(!empty($args[1])) {
                        $online = $this->getServer()->getPlayerByPrefix($args[0]);

                        if($online!=null) {
                            $this->addWarn($online->getName()); 
                            $warns = $this->getWarns($online->getName());
                            $motivo = implode(" ", $args); 
                            $worte = explode(" ", $motivo);  
                            unset($worte[0]);
                            $motivo = implode(" ", $worte);
                            $reportado = $online->getName();
                            $sender->getName();
                            $this->getServer()->broadcastMessage("§dSe le agrego una advertencia a §f$reportado §dpor la razon §f $motivo §dahora tiene §f $warns §dadvertencias");
                            $online->sendMessage("§dSe te agrego una advertencia por §f ".$sender->getName()." §dpor la razon §f $motivo §d¡Ahora tienes §f $warns §d advertencias!");
                        } else {
                            $sender->sendMessage("§dJugador no encontrado");
                        }
                    }
                } else {
                    $this->WarnAddUI($sender);
                }
                break;
            case "warnremove":
                if(!empty($args[0])) {
                    if(!empty($args[1])) {
                        $online = $this->getServer()->getPlayerByPrefix($args[0]);
                        
                        if($online!=null) {
                            $motivo = implode(" ", $args); 
                            $worte = explode(" ", $motivo);  
                            unset($worte[0]);
                            $motivo = implode(" ", $worte);
                            $reportado = $online->getName();
                            $sender->getName();
                            $warns = $this->getWarns($online->getName()); 
                            if($warns > 0) {
                                $warns -= 1;
                                $this->delWarn($online->getName());
                                $sender->sendMessage("§dAdvertencia removida con éxito a §f $reportado §dAhora tiene §f $warns §dpor la razon §f $motivo");
                                $online->sendMessage("§dTu advertencia fue eliminada por  §f ".$sender->getName()." §dAhora tienes §f $warns §dadvertencias!");
                            } else {
                                $sender->sendMessage("§cEl jugador no tiene advertencias!");
                            }
                                
                        }
                    }
                } else {
                    $this->WarnRemoveUI($sender);
                }
                break;
        }
        return true;
    }

    public function WarnAddUI($player) {
        $list = [];
        foreach($this->getServer()->getOnlinePlayers() as $p) {
            $list[] = $p->getName();
        }

        $this->warnsplayer[$player->getName()] = $list;

        $form = new CustomForm(function (Player $player, array $data = null) {
            if($data === null) {
                $player->sendMessage("Warn Failed");
                  return true;
              }
              
              $index=$data[1];
              $this->getServer()->dispatchCommand($player, "warnadd {$this->warnsplayer[$player->getName()][$index]}  {$data[2]}");
        });
        $form->setTitle($this->getConfig()->get("Title-AddWarn"));
        $form->addLabel($this->getConfig()->get("Label-AddWarn"));
        $form->addDropdown("Selection to Player ", $this->warnsplayer[$player->getName()]);
        $form->addInput("Reason", "¿Motivo? ", "Fly");
        $form->sendToPlayer($player);
        return $form;
    }
    
    public function WarnRemoveUI($player) { 
        $list = [];
        foreach($this->getServer()->getOnlinePlayers() as $p) {
            $list[] = $p->getName();
        }

        $this->warnsplayer[$player->getName()] = $list;
        
        $form = new CustomForm(function (Player $player, array $data = null) {
            if($data === null) {
                $player->sendMessage("Warn Failed");
                  return true;
              }
      
            $index=$data[1];
            $this->getServer()->dispatchCommand($player, "warnremove {$this->warnsplayer[$player->getName()][$index]}  {$data[2]}");
        });
        $form->setTitle($this->getConfig()->get("Title-RemoveWarn"));
        $form->addLabel($this->getConfig()->get("Label-RemoveWarn"));
        $form->addDropdown("Selecciona a un jugador ", $this->warnsplayer[$player->getName()]);
        $form->addInput("Razón", "¿Motivo? ", "Fly");
        $form->sendToPlayer($player);
        return $form;
    }

    public function getWarnForm($player) {
        $form = new SimpleForm(function ($player, $data){
		$result = $data;
		if($result === null){
			return true;
			}
			switch($result){
				case 0:
				$this->WarnAddUI($player);
                break;
                case 1:
                $this->WarnRemoveUI($player);
                break;
			}
		});					
		$form->setTitle($this->getConfig()->get("Title-Form"));
        $form->setContent($this->getConfig()->get("Content-Form"));
        $form->addButton($this->getConfig()->get("Button1"));
        $form->addButton($this->getConfig()->get("Button2"));
		$form->sendToPlayer($player);
    }

}
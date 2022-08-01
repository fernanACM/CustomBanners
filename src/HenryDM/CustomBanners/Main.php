<?php

declare(strict_types=1);

namespace HenryDM\CustomBanners;

#Pocketmine Libs
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;

#Plugin Libs
use HenryDM\CustomBanners\libs\jojoe77777\FormAPI\SimpleForm;
use pocketmine\nbt\JsonNbtParser;

class Main extends PluginBase implements Listener {

    public function onEnable() : void {
	    
        $this->patterns = ['gra', 'gru', 'bri', 'hh','hhb','vh','vhr','ts','bs','ls','rs','ld','rud','lud','rd','cr','dls','drs','sc','cs','ms','tl','bl','tr','br','tt','bt','mr','mc','bts','tts','ss','bo','cbo','flo','cre','sku','moj'];
        $this->saveResource("config.yml");
        $this->saveResource("players-data.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->playerdata = new Config($this->getDataFolder() . "players-data.yml", Config::YAML);
        if($this->config->get("banner-number") == FALSE or !is_numeric($this->config->get("banner-number")) or $this->config->get("banner-number") > 16 or $this->config->get("banner-number") < 1){
            $this->config->set("banner-number", 16);
            $this->config->save();
        }
        if($this->config->get("banner-timeout") == FALSE or !is_numeric($this->config->get("banner-timeout")) or $this->config->get("banner-timeout") < 0){
            $this->config->set("banner-timeout", 0);
            $this->config->save();
        }
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$player = $sender->getName();
		switch($command->getName()){
            case "banner":
                $plconfig = $this->playerdata->get($player);
                if($plconfig == FALSE){
                    $timeout = TRUE;
                }elseif(!is_numeric($plconfig)){
                    $timeout = TRUE;
                }elseif($plconfig+$this->config->get("banner-timeout") > microtime(TRUE)){
                    $timeout = FALSE;
                }else{
                    $timeout = TRUE;
                }
                if($sender->hasPermission("custombanner.use") or $timeout){
                    if(isset($args[0])){
                        if(!in_array(strtoupper($args[0]), $this->colors)){
                            $sender->sendMessage(str_replace("{x}", $args[0], self::getTranslation("Color_not_found")).'§r');
                        }else{ 
                            $this->$player =  new \stdClass(); 
                            $this->layer($sender, strtolower($args[0]));
                        }
                    }else{
                        $sender->sendMessage(self::getTranslation("Choose_background").'§r');
                    }
                }else{
                    $towait = $plconfig+$this->config->get("banner-timeout");
                    $sender->sendMessage(str_replace("{x}", strval(intval($towait-microtime(TRUE))), self::getTranslation("Please_wait")).'§r');
                }
			default:
				return false;
        }
        
	}
    public function layer($player, $color, $all = false){
       $form = new SimpleForm(function (Player $player, $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                default:
                    $playern = $player->getName();
                    if($this->$playern->all === false){
                        $selected = $result;
                    }elseif($result == 0){
                        $playern = $player->getName();
                        $to_text = '§'.$this->colortags[strtoupper($this->$playern->color)].str_replace("{x}", $this->$playern->color, self::getTranslation("Pattern_name"));
                        $player->sendMessage(str_replace("{x}", $to_text, self::getTranslation("Finished_message")));
                        $item = Item::fromString("minecraft:banner:".$this->bannerc[strtoupper($this->$playern->color)]);
                        $item->setCount(intval($this->config->get("banner-number")));
                        $item->setNamedTag(JsonNbtParser::parseJSON("{display:{Name:".$to_text."},BlockEntityTag:{Base:".$this->bannerc[strtoupper($this->$playern->color)].",Patterns:[".substr($this->$playern->all, 0, -1)."]}}"));
                        $player->getInventory()->addItem($item);
                        $this->$playern->color = null;
                        $this->$playern->all = null;
                        $this->$playern->pattern = null;
                        $this->playerdata->set($playern, microtime(true));
                        $this->playerdata->save();
                        return;
                    }else{
                        $selected = $result-1;
                    }
                    $this->color($player, $this->$playern->color, $this->$playern->all, $selected);
                    return;
            }
        });
        $colortag = '§'.$this->colortags[strtoupper($color)];
        $form->setTitle(str_replace("{x}", $colortag.$color.'§r', self::getTranslation("Creating_banner")));
        $form->setContent(self::getTranslation("Select_pattern"));
        if($all !== false) $form->addButton(self::getTranslation("Done"));
        foreach($this->items as $item){
            $form->addButton($item);
        }
        $playern = $player->getName();
        $this->$playern->color = $color;
        $this->$playern->all = $all;
        $form->sendToPlayer($player);
    }
    public function color($player, $color, $all, $pattern){
       $form = new SimpleForm(function (Player $player, $data = null ) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            $playern = $player->getName();
            $this->$playern->all .=  '{Pattern:' . $this->patterns[$this->$playern->pattern] . ',Color:' . $this->bannerc[$this->colors[$result]].'},';
            $this->layer($player, $this->$playern->color, $this->$playern->all);
            return;
        });
        $colortag = '§'.$this->colortags[strtoupper($color)];
        $form->setTitle(str_replace("{x}", $colortag.$color.'§r', self::getTranslation("Creating_banner")));
        $form->setContent(str_replace("{x}", $this->items[$pattern], self::getTranslation("Choose_color")));
        foreach($this->colors as $item){
            $form->addButton('§'.$this->colortags[$item] . ucfirst(strtolower(str_replace('_', ' ', $item))));
        }
        $playern = $player->getName();
        $this->$playern->pattern = $pattern;
        $form->sendToPlayer($player);
    }
}

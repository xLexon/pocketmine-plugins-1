<?php
namespace aliuly\worldprotect;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;

use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

abstract class BaseWp {
	protected $owner;
	protected $wcfg;

	public function __construct($owner) {
		$this->owner = $owner;
		$this->wcfg = [];
	}

	static $items = [];
	public function itemName(Item $item) {
		if (count(self::$items) == 0) {
			$constants = array_keys((new \ReflectionClass("pocketmine\\item\\Item"))->getConstants());
			foreach ($constants as $constant) {
				$id = constant("pocketmine\\item\\Item::$constant");
				$constant = str_replace("_", " ", $constant);
				self::$items[$id] = $constant;
			}
		}
		$n = $item->getName();
		if ($n != "Unknown") return $n;
		if (isset(self::$items[$item->getId()]))
			return self::$items[$item->getId()];
		return $n;
	}

	public function enableSCmd($cmd,$opts) {
		$this->owner->registerScmd($cmd,[$this,"onSCommand"],$opts);
	}

	// Paginate output
	protected function getPageNumber(array &$args) {
		$pageNumber = 1;
		if (count($args) && is_numeric($args[count($args)-1])) {
			$pageNumber = (int)array_pop($args);
			if($pageNumber <= 0) $pageNumber = 1;
		}
		return $pageNumber;
	}
	protected function paginateText(CommandSender $sender,$pageNumber,array $txt) {
		$hdr = array_shift($txt);
		if($sender instanceof ConsoleCommandSender){
			$sender->sendMessage( TextFormat::GREEN.$hdr.TextFormat::RESET);
			foreach ($txt as $ln) $sender->sendMessage($ln);
			return true;
		}
		$pageHeight = 5;
		$lineCount = count($txt);
		$pageCount = intval($lineCount/$pageHeight) + ($lineCount % $pageHeight ? 1 : 0);
		$hdr = TextFormat::GREEN.$hdr. TextFormat::RESET;
		if ($pageNumber > $pageCount) {
			$sender->sendMessage($hdr);
			$sender->sendMessage("Only $pageCount pages available");
			return true;
		}
		$hdr .= TextFormat::RED." ($pageNumber of $pageCount)";
		$sender->sendMessage($hdr);
		for ($ln = ($pageNumber-1)*$pageHeight;$ln < $lineCount && $pageHeight--;++$ln) {
			$sender->sendMessage($txt[$ln]);
		}
		return true;
	}
	protected function paginateTable(CommandSender $sender,$pageNumber,array $tab) {
		$cols = [];
		for($i=0;$i < count($tab[0]);$i++) $cols[$i] = strlen($tab[0][$i]);
		foreach ($tab as $row) {
			for($i=0;$i < count($row);$i++) {
				if (($l=strlen($row[$i])) > $cols[$i]) $cols[$i] = $l;
			}
		}
		$txt = [];
		$fmt = "";
		foreach ($cols as $c) {
			if (strlen($fmt) > 0) $fmt .= " ";
			$fmt .= "%-".$c."s";
		}
		foreach ($tab as $row) {
			$txt[] = sprintf($fmt,...$row);
		}
		return $this->paginateText($sender,$pageNumber,$txt);
	}
	//
	// Config look-up cache
	//
	public function setCfg($world,$value) {
		$this->wcfg[$world] = $value;
	}
	public function unsetCfg($world) {
		if (isset($this->wcfg[$world])) unset($this->wcfg[$world]);
	}
	public function getCfg($world,$default) {
		if (isset($this->wcfg[$world])) return $this->wcfg[$world];
		return $default;
	}
}
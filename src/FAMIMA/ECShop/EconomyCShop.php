<?php

namespace FAMIMA\ECShop;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

use FAMIMA\ECShop\EventListener;
use FAMIMA\ECShop\DatabaseManager;


class EconomyCShop extends PluginBase
{
	private $db;

	public $server;

	public function onEnable()
	{
		$plugin = "EconomyCShop";
		$logger = $this->getLogger();
		$this->server = $this->getServer();
		new EventListener($this);
		$dir = $this->getDataFolder();
		@mkdir($dir, 0755);
		$this->db = new DatabaseManager($dir."ECShopPos.sqlite3");
		if(($this->economy = $this->server->getPluginManager()->getPlugin("EconomyAPI")) === null)
		{
			$logger->alert("EconomyAPIが存在しません, EconomyAPIを導入してください");
			$this->server->getPluginManager()->disablePlugin($this);
		}

		$config = new Config($dir."Message.yml", Config::YAML,
			[
			"Message1" => "§a【運営】 §fEconomyCShopの作成が完了しました",
			"Message2" => "§a【運営】 §cChestが見つかりません！  横にChestがあるか確認してください",
			"Message3" => "§a【運営】 §cこれはあなたのSHOPです",
			"Message4" => "§a【運営】 §cインベントリに空きがありません",
			"Message5" => "§a【運営】 §cChestにアイテムがありません, 補充してもらいましょう",
			"Message6" => "§a【運営】 §cお金が足りないため購入できませんでした",
			"Message7" => "§a【運営】 §cあなたはこのchestを開けることができません",
			"Message8" => "§a【運営】 §f%itemを%amount個購入しました",
			"Message9" => "§a【運営】 §f%itemを購入しますか?(%price円です)",
			"Message10" => "§a【運営】 §cあなたはこのShopを破壊することができません",
			"Message11" => "§a 【運営】 §cあなたはこのChestを破壊することができません",
			"Message12" => "§a【運営】 §fShopを閉店しました"
			]);
		$this->message = $config->getAll();
		//var_dump($this->message);
	}

	public function MessageReplace(string $str, array $serrep)
	{
		foreach($serrep as $search => $replace)
		{
			$str = str_replace($search, $replace, $str);
		}
		return $str;
	}

	public function getMessage(string $message, $serrep = [])
	{
		return $this->MessageReplace( (isset($this->message[$message])) ? $this->message[$message] : メッセージが存在しません", $serrep);
	}

	public function createChestShop($cpos, $spos, $owner, $item, $price)
	{
		$this->db->createChestShop($cpos->x, $cpos->y, $cpos->z, $spos->x, $spos->y, $spos->z,
		$owner, $item->getID(), $item->getDamage(), $item->getCount(), $price, $spos->getLevel()->getName());
	}

	public function updateChestShopData($spos, $owner, $item, $price)
	{
		$this->db->updateChestShopData($spos->x, $spos->y, $spos->z, $owner, $item->getID(), $item->getDamage(), $item->getCount(), $price, $spos->getLevel()->getName());
	}

	public function isShopExists($pos)
	{
		return $this->db->isShopExists($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function isShopChestExists($pos)
	{
		return $this->db->isShopChestExists($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function getShopData($pos)
	{
		return $this->db->getShopData($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function isExistsChests($pos)
	{
		$l = $pos->level;
		$existsdata = false;
		$cpos = [$pos->add(1), $pos->add(-1), $pos->add(0, 0, 1), $pos->add(0, 0, -1)];
		foreach ($cpos as $vector) {
			if($l->getBlock($vector)->getID() === 54)
			{
				$existsdata = true;
			}
		}
		return $existsdata;
	}

	public function getChests($pos)
	{
		$l = $pos->level;
		$posdata = false;
		$cpos = [$pos->add(1), $pos->add(-1), $pos->add(0, 0, 1), $pos->add(0, 0, -1)];
		foreach ($cpos as $vector) {
			if($l->getBlock($vector)->getID() === 54)
			{
				$posdata = $vector;
			}
		}
		return $posdata;
	}

	public function isExistChestInItem($pos, $item)
	{
		return $pos->level->getTile($pos)->getInventory()->contains($item);
	}

	public function removeChestInItem($pos, $item)
	{
		$pos->level->getTile($pos)->getInventory()->removeItem($item);
	}

	public function removeShop($pos)
	{
		$this->db->deleteShop($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function onBuy($owner, $target, $amount)
	{
		$tmoney = $this->economy->myMoney($target);
		if($tmoney < $amount)
		{
			return false;
		}else{
			$this->economy->reduceMoney($target, $amount);
			$this->economy->addMoney($owner, $amount);
			return true;
		}
	}
}

<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IPresentador;
use MasExperto\ME\M;

abstract class Presentador implements IPresentador
{
	public $documento = null;

	function __construct() {}
	function __destruct() {
		unset($this->documento);
	}
}

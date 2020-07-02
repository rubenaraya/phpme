<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IRuteador;

abstract class Ruteador implements IRuteador
{
	public $campos = array();
	public $parametros = array();
	public $estados = array();

	function __construct() {}
	function __destruct() {
		$this->campos = null;
		$this->parametros = null;
		unset($this->campos);
		unset($this->parametros);
	}
}
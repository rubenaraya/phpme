<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IRuteador;
use MasExperto\ME\M;

abstract class Ruteador implements IRuteador
{
	public $campos = array();
	public $parametros = array();
	public $estados = array();

	function __construct() {
        M::$entorno['RUTA']['ME'] = str_replace( '\\', '/', dirname(__DIR__) );
    }
	function __destruct() {
		$this->campos = null;
		$this->parametros = null;
		unset($this->campos);
		unset($this->parametros);
	}
}
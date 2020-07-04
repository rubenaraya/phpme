<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IRuteador;
use MasExperto\ME\M;

abstract class Ruteador implements IRuteador
{
	public $campos = array();
	public $parametros = array();
	public $estados = array();

	function __construct( $back = '', $front = '' ) {
		if ( strlen($back)>0 ) {
			if ( strlen($front)==0 ) { $front = $back; }
			M::$entorno['RUTA']['BACKEND'] = str_replace( '\\', '/', $back );
			M::$entorno['RUTA']['FRONTEND'] = str_replace( '\\', '/', $front );
		}
        M::$entorno['RUTA']['ME'] = str_replace( '\\', '/', dirname(__DIR__) );
    }
	function __destruct() {
		$this->campos = null;
		$this->parametros = null;
		unset($this->campos);
		unset($this->parametros);
	}
}
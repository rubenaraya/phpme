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
			M::$entorno['RUTA']['BACKEND'] = $back;
			M::$entorno['RUTA']['FRONTEND'] = $front;
		}
		M::$entorno['RUTA']['BACKEND'] = str_replace( '\\', '/', realpath( M::$entorno['RUTA']['BACKEND'] ) );
		M::$entorno['RUTA']['FRONTEND'] = str_replace( '\\', '/', realpath( M::$entorno['RUTA']['FRONTEND'] ) );
		M::$entorno['ALMACEN']['PUBLICO'] = str_replace( '\\', '/', realpath( M::$entorno['ALMACEN']['PUBLICO'] ) );
		M::$entorno['ALMACEN']['PRIVADO'] = str_replace( '\\', '/', realpath( M::$entorno['ALMACEN']['PRIVADO'] ) );
		M::$entorno['RUTA']['WEBME'] = str_replace( '\\', '/', realpath( M::$entorno['RUTA']['WEBME'] ) );
        M::$entorno['RUTA']['PHPME'] = str_replace( '\\', '/', dirname(__DIR__) );
    }
	function __destruct() {
		$this->campos = null;
		$this->parametros = null;
		unset($this->campos);
		unset($this->parametros);
	}
}
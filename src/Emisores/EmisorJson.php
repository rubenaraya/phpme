<?php
namespace MasExperto\ME\Emisores;

use MasExperto\ME\Bases\Emisor;
use MasExperto\ME\M;

final class EmisorJson extends Emisor {

	public function Imprimir( $contenido, $opciones = array() ) {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept' );
		header( 'P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"' );
		$callback = M::E('CALLBACK');
		if ( strlen( $callback )>0 ) {
			header( 'Access-Control-Allow-Methods: GET' );
			header( 'Content-Type: application/javascript; charset=utf-8' ); 
		} else {
			header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
			header( 'Content-Type: application/json; charset=utf-8' );
		}
		if ( $this->_validarJson( $contenido ) ) {
			if ( strlen( $callback )>0 ) {
				echo $callback . '([' . $contenido . '])';
			} else {
				echo $contenido;
			}
		} else {
			$json = json_encode( $contenido );
			$json = str_replace(':null,', ':"",', $json);
			if ( strlen( $callback )>0 ) {
				echo $callback . '([' . $json . '])';
			} else {
				echo $json;
			}
		}
		flush();
		exit;
	}

	public function mostrarErrores( $errores ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		$contenido = array();
		$contenido['ERRORES'] = $errores;
		$json = json_encode( $contenido );
		echo $json;
		flush();
		exit;
	}

	private function _validarJson( $json ) {
		if ( is_string($json) ) {
			@json_decode( $json );
			return ( json_last_error() === JSON_ERROR_NONE );
		}
		return false;
	}
}

<?php
namespace MasExperto\ME\Emisores;
use MasExperto\ME\Emisor;
use MasExperto\ME\M;

final class EmisorHtml extends Emisor {

	//METODOS PUBLICOS

	public function Imprimir( $contenido, $opciones = array() ) {
		$charset = ( isset($opciones['charset']) ? $opciones['charset'] : 'UTF-8' );
		header( 'Content-Type: text/html; charset=' . $charset );
		if ( is_array($contenido) ) {
			$contenido = M::convertirMatrizHtml( $contenido, $opciones ); 
		}
		if ( strtoupper($charset) == 'UTF-8' ) {
			echo $contenido;
		} else {
			echo utf8_decode( $contenido );
		}
		flush();
		exit;
	}

	public function mostrarErrores( $errores ) {
		$texto = '';
		foreach ( $errores as $error ) {
			$texto = $texto . '<p>' . htmlspecialchars( $error['mensaje'] ) . '</p>';
		}
		header( 'Content-Type: text/html; charset=utf-8' );
		echo $texto;
		flush();
		exit;
	}
}

<?php
namespace MasExperto\ME\Emisores;

use MasExperto\ME\Emisor;

final class EmisorWebm extends Emisor {

	//METODOS PUBLICOS

	public function Imprimir( $contenido, $opciones = array() ) {
		if ( file_exists( $contenido ) && !is_dir( $contenido ) ) {
			$eliminar = ( isset($opciones['eliminar']) ? $opciones['eliminar'] : false );
			header( 'Content-Type: audio/webm; codecs="opus"' );
			header( 'Content-Length: ' . filesize( $contenido ) );
			readfile( $contenido );
			flush();
			if ( $eliminar ) {
				sleep(1);
				@unlink( $contenido );
			}
			exit;
		}
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

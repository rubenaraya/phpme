<?php
namespace MasExperto\ME\Emisores;

use MasExperto\ME\Bases\Emisor;

final class EmisorFile extends Emisor {

	public function Imprimir( $contenido, $opciones = array() ) {
		if ( file_exists( $contenido ) && !is_dir( $contenido ) ) {
			$nombre = ( isset($opciones['nombre']) ? $opciones['nombre'] : date('Ymd_His') . substr( $contenido, strrpos( $contenido, '.') ) );
			$eliminar = ( isset($opciones['eliminar']) ? $opciones['eliminar'] : false );
			header( 'Content-Type: application/octet-stream; charset=utf-8' );
			header( 'Content-Length: ' . filesize( $contenido ) );
			header( 'Content-Disposition: attachment;filename="' . $nombre . '";' );
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

<?php
namespace MasExperto\ME\Emisores;

use MasExperto\ME\Emisor;
use MasExperto\ME\M;

final class EmisorCsv extends Emisor {

	//METODOS PUBLICOS

	public function Imprimir( $contenido, $opciones = array() ) {
		$charset = ( isset($opciones['charset']) ? $opciones['charset'] : 'ISO-8859-1' );
		$nombre = ( isset($opciones['nombre']) ? $opciones['nombre'] : date('Ymd_His') . '.csv' );
		header( 'Content-Type: text/csv; charset=' . $charset );
		header( 'Content-Disposition: inline;filename="' . $nombre . '";' );
		if ( is_array($contenido) ) {
			$aux = M::adquirirDatosMatriz( $contenido, '0' );
			if ( is_array($aux) && count($aux)>0 ) {
				$contenido = $aux[0];
			} else {
				$contenido = '';
			}
			$contenido = $this->_exportarCsv( $contenido, $opciones );
		}
		if ( strtoupper($charset) == 'UTF-8' ) {
			echo $contenido;
		} else {
			echo utf8_decode($contenido);
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

	//FUNCIONES PRIVADAS

	private function _exportarCsv( $contenido, $opciones ) {
		if ( !isset($contenido) ) {
			$csv = ''; 
		} else {
			if ( !is_array($contenido) || count($contenido)==0 ) { return ''; }
			$encabezado = ( isset($opciones['encabezado']) ? $opciones['encabezado'] : 'S' );
			$csv = '';
			if ( $encabezado == 'S' ) {
				if ( isset($contenido[0]) ) {
					foreach( $contenido[0] as $key => $value ) {
						$csv .= chr(34) . htmlspecialchars( $key ) . chr(34) . ';';
					}
				}
				$csv .= chr(10);
			}
			foreach( $contenido as $key => $value ) {
				if ( is_array($value) ) {
					foreach( $value as $key2 => $value2 ) {
						$csv .= chr(34) . htmlspecialchars( $value2 ) . chr(34) . ';';
					}
					$csv .= chr(10);
				}
			}
		}
		return $csv;
	}
}

<?php
namespace MasExperto\ME\Emisores;

use MasExperto\ME\Emisor;

final class EmisorXml extends Emisor {

	//METODOS PUBLICOS

	public function Imprimir( $contenido, $opciones = array() ) {
		header( 'Content-Type: text/xml; charset=utf-8' );
		if ( is_array($contenido) ) { 
			$xml = simplexml_load_string( '<MASEXPERTO />' );
			$this->_exportarXml( $contenido, $xml );
		} elseif ( strlen($contenido)>0 ) {
			$xml = simplexml_load_string( $contenido );
		} else {
			$xml = simplexml_load_string( '<MASEXPERTO />' );
		}
		echo $xml->asXML();
		unset($xml);
		flush();
		exit;
	}

	public function mostrarErrores( $errores ) {
		$xml = simplexml_load_string( '<MASEXPERTO />' );
		$aux = $xml->addChild( 'errores' );
		foreach ( $errores as $error ) {
			$aux->addChild( 'error', htmlspecialchars( $error['mensaje'], ENT_COMPAT, 'UTF-8' ) );
		}
		header( 'Content-Type: text/xml; charset=utf-8' );
		echo $xml->asXML();
		unset($xml);
		flush();
		exit;
	}

	//FUNCIONES PRIVADAS

	private function _exportarXml( $data, &$xml ) {
		if ( is_array($data) ) {
			foreach ( $data as $key => $value ) {
				if ( is_array($value) ) {
					if ( is_numeric($key) ) {
						$subnode = $xml->addChild('elemento');
						$subnode->addAttribute( 'num', $key + 1 );
					} else {
						$subnode = $xml->addChild($key);
					}
					$this->_exportarXml( $value, $subnode );
				} else if ( is_numeric($key) ) {
					$xml->addChild("item", htmlspecialchars( "$value" ));
				} else {
					$xml->addChild("$key", htmlspecialchars( "$value" ));
				}
			}
		}
	}
}

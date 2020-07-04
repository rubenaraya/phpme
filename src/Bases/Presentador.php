<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IPresentador;
use MasExperto\ME\M;

abstract class Presentador implements IPresentador
{
	public $documento = null;

	function __construct() {}
	function __destruct() {
		unset($this->documento);
	}

	public function abrirPlantilla( $archivo, $ruta = '', $reemplazar = true ) {
		$txt = '';
		if ( strlen($ruta)==0 ) { $ruta = M::E('RUTA/PUNTOFINAL'); }
		$origen = $ruta . '/' . $archivo;
		if ( file_exists( $origen ) && !is_dir( $origen ) ) {
			$txt = file_get_contents( $origen );
			if ($reemplazar) {
				foreach ( M::$entorno as $nombre => $valor ) {
					if ( !is_array($valor) ) { 
						$txt = str_replace( '{{'. $nombre .'}}', htmlspecialchars( $valor, ENT_COMPAT, 'UTF-8'), $txt );
					} else {
						foreach ( $valor as $nombre2 => $valor2 ) {
							if ( !is_array($valor2) ) { 
								$txt = str_replace( '{{'. $nombre . '/' . $nombre2 .'}}', htmlspecialchars( $valor2, ENT_COMPAT, 'UTF-8'), $txt );
							}
						}
					}
				}
				$exp = '~\(\((.*?)\)\)~';
				preg_match_all( $exp, $txt, $coincidencias );
				if ( isset($coincidencias[1]) ) {
					foreach ( $coincidencias[1] as $indice => $valor ) {
						$reemp = _($valor);
						$txt = str_replace( '(('. $valor . '))', $reemp, $txt );
					}
				}
				$txt = M::reemplazarEtiquetas( $txt );
			}
		}
		return $txt;
	}
}

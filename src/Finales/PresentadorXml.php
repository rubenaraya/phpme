<?php 
namespace MasExperto\ME\Finales;

use MasExperto\ME\M;
use MasExperto\ME\Bases\Presentador;
use DateTime;
use DOMDocument;
use DOMXPath;
use XSLTProcessor;

final class PresentadorXml extends Presentador
{

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

    public function crearVista( $documento = '', $ruta = '' ) {
		$this->documento = null;
		if ( strlen($documento)>0 ) {
			if ( strlen($ruta)==0 ) {
				$ruta = M::E('RUTA/ESQUEMAS');
				if ( !file_exists( $ruta . '/' . $documento ) || is_dir( $ruta . '/' . $documento ) ) {
					$ruta = M::E('RUTA/BACKEND');
				}
			}
			$doc = $ruta . '/' . $documento;
			if ( file_exists( $doc ) && !is_dir( $doc ) ) {
				$this->documento = simplexml_load_file( $doc );
				if ( !is_object( $this->documento ) ) {
					$msg = sprintf(dgettext('me', "El-documento-'%s'-no-se-pudo-cargar"), $documento);
					trigger_error( 'PresentadorXml.crearVista: ' . $msg, E_USER_ERROR );
				}
			}
		}
		if ( !is_object($this->documento) ) {
			$this->documento = simplexml_load_string( '<MASEXPERTO />' );
		}
		$aux = $this->documento->addChild( 'entorno' );
		foreach ( M::E('') as $nombre => $valor ) {
			if ( !is_array($valor) ) { $aux->addChild( $nombre, htmlspecialchars( $valor, ENT_COMPAT, 'UTF-8' ) ); }
		}
		foreach ( M::E('SOLICITUD') as $nombre => $valor ) {
			if ( !is_array($valor) ) { $aux->addChild( $nombre, htmlspecialchars( $valor, ENT_COMPAT, 'UTF-8' ) ); }
		}
		foreach ( M::E('RECURSO') as $nombre => $valor ) {
			if ( !is_array($valor) ) { $aux->addChild( $nombre, htmlspecialchars( $valor, ENT_COMPAT, 'UTF-8' ) ); }
		}
		if ( is_array( M::E('PARAMETROS') ) ) {
			foreach ( M::E('PARAMETROS') as $nombre => $valor ) {
				if ( !is_array($valor) ) {
					$aux->addChild( $nombre, htmlspecialchars( $valor, ENT_COMPAT, 'UTF-8' ) );
				} else {
					$aux->addChild( $nombre, htmlspecialchars( implode(',', $valor), ENT_COMPAT, 'UTF-8' ) );
				}
			}
		}
		if ( is_array( M::E('USUARIO') ) ) {
			$aux = $this->documento->addChild( 'usuario' );
			foreach ( M::E('USUARIO') as $nombre => $valor ) {
				if ( !is_array($valor) ) { $aux->addChild( $nombre, htmlspecialchars( $valor, ENT_COMPAT, 'UTF-8' ) ); }
			}
		}
		return $this->documento->asXML();
	}

	public function anexarDocumento( $documento = '', $ruta = '' ) {
		if ( is_object($this->documento) && strlen($documento)>0 ) {
			if ( strlen($ruta)==0 ) {
				$ruta = M::E('RUTA/ESQUEMAS');
				if ( !file_exists( $ruta . '/' . $documento ) || is_dir( $ruta . '/' . $documento ) ) {
					$ruta = M::E('RUTA/BACKEND');
				}
			}
			$doc = $ruta . '/' . $documento;
			if ( file_exists( $doc ) && !is_dir( $doc ) ) {
				$origen = dom_import_simplexml( simplexml_load_file( $doc ) );
				$destino = dom_import_simplexml( $this->documento );
				$destino->appendChild( $destino->ownerDocument->importNode( $origen, true ) );
			}
		}
	}

	public function Transformar( $plantilla, $ruta = '', $opciones = array() ) {
		$txt = '';
		$doctype = ( isset($opciones['doctype']) ? $opciones['doctype'] : false );
		$reemplazar = ( isset($opciones['reemplazar']) ? $opciones['reemplazar'] : array() );
		$vista = ( isset($opciones['vista']) ? $opciones['vista'] : '' );
		$pagina = ( isset($opciones['pagina']) ? $opciones['pagina'] : '' );
		$seccion = ( isset($opciones['seccion']) ? $opciones['seccion'] : '' );
		$fondo = ( isset($opciones['fondo']) ? $opciones['fondo'] : '' );
		$info = ( isset($opciones['info']) ? $opciones['info'] : '' );
		$clase = ( isset($opciones['clase']) ? $opciones['clase'] : '' );
		$mensaje = ( isset($opciones['mensaje']) ? $opciones['mensaje'] : '' );
		$incluir = ( isset($opciones['incluir']) ? $opciones['incluir'] : '' );
		if ( strlen($ruta)==0 ) { $ruta = M::E('RUTA/FRONTEND'); }
		$archivo = $ruta . '/' . $plantilla;
		if ( file_exists( $archivo ) && !is_dir( $archivo ) ) {
			$doc = new DOMDocument( '1.0', 'utf-8' );
			$doc->load( $archivo );
            $base = str_replace( '\\', '/', M::E('RUTA/WEBME')) . '/base.xsl';
            if ( file_exists( $base ) ) {
                $nodo = $doc->createElementNS( 'http://www.w3.org/1999/XSL/Transform', 'include' );
                $nodo->setAttribute( 'href', $base );
                $doc->documentElement->appendChild( $nodo );
            }
			$incluir = str_replace( '\\', '/', $incluir );
			if ( strlen( $incluir )>0 && file_exists( $incluir ) && !is_dir( $incluir ) ) {
				$nodo = $doc->createElementNS( 'http://www.w3.org/1999/XSL/Transform', 'include' );
				$nodo->setAttribute( 'href', $incluir );
				$doc->documentElement->appendChild( $nodo );
			}
			$fecha = new DateTime();
			$xslt = new XSLTProcessor;
			$xslt->importStyleSheet( $doc );
			$xslt->setParameter( '', 'hoy_dma', $fecha->format('d-m-Y') );
			$xslt->setParameter( '', 'hoy_amd', $fecha->format('Y/m/d') );
			$xslt->setParameter( '', 'hoy_mda', $fecha->format('m-d-Y') );
			$xslt->setParameter( '', 'hoy_md', $fecha->format('m-d') );
			$xslt->setParameter( '', 'hoy_mes', $fecha->format('m') );
			$xslt->setParameter( '', 'hoy_dia', $fecha->format('d') );
			$xslt->setParameter( '', 'hoy_ano', $fecha->format('Y') );
			$xslt->setParameter( '', 'hoy_per', $fecha->format('Ym') );
			$xslt->setParameter( '', 'vista', $vista );
			$xslt->setParameter( '', 'pagina', $pagina );
			$xslt->setParameter( '', 'seccion', $seccion );
			$xslt->setParameter( '', 'clase', $clase );
			$xslt->setParameter( '', 'fondo', $fondo );
			$xslt->setParameter( '', 'info', $info );
			$xslt->setParameter( '', 'mensaje', $mensaje );
			$xslt->setParameter( '', 'm_uid', M::E('UID') );
			if ( isset(M::$entorno['USUARIO']) ) {
				foreach ( M::$entorno['USUARIO'] as $clave => $valor ) {
					$xslt->setParameter( '', 'usu_' . $clave, $valor );
				}
			}
			if ( is_object($this->documento) ) {
				if ( M::E('M_SALIDA')=='XML' && M::E('M_MODO')=='PRUEBA' ) {
					$txt = $this->documento->asXML();
				} else {
					$txt = $xslt->transformToXML( $this->documento );
					if ( !$doctype ) {
						$txt = mb_eregi_replace( '<!DOCTYPE \s*[^>]*\s*>', '', $txt ); 
						if ( substr($txt, 0, 1) == chr(10) ) { $txt = substr( $txt, (strlen($txt) - 1 ) * -1 ); }
					}
				}
				if ( is_array($reemplazar) ) {
					foreach ( $reemplazar as $key => $value ) {
						$txt = str_replace( '(('. $key . '))', '(('. $value . '))', $txt );
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
			}
		} else {
			$msg = sprintf(dgettext('me', "La-plantilla-'%s'-no-se-encontro-en-'%s'"), $plantilla, $ruta);
			trigger_error( 'PresentadorXml.Transformar: ' . $msg, E_USER_ERROR );
		}
		unset( $xslt, $doc, $fecha );
		return $txt;
	}

	public function anexarResultados( $dto ) {
		if ( is_object($this->documento) ) {
			if ( is_object($dto) ) {
				foreach ( $dto->resultados as $etiqueta => $matriz ) {
					$resultado = simplexml_load_string( '<resultados grupo="'. $etiqueta .'" />' );
					$this->_exportarXml( $dto->resultados[$etiqueta], $resultado );
					$origen = dom_import_simplexml( $resultado );
					$destino = dom_import_simplexml( $this->documento );
					$destino->appendChild( $destino->ownerDocument->importNode( $origen, true ) );
					unset($origen); unset($destino);
				}
			}
			return $this->documento->asXML();
		}
		return '';
	}

	public function anexarMetadatos( $matriz, $etiqueta = 'd' ) {
		if ( is_object($this->documento) ) {
			if ( is_array($matriz) ) {
				$resultado = simplexml_load_string( '<' . $etiqueta . ' />' );
				$this->_exportarValorXml( $matriz, $resultado );
				$origen = dom_import_simplexml( $resultado );
				$destino = dom_import_simplexml( $this->documento );
				$destino->appendChild( $destino->ownerDocument->importNode( $origen, true ) );
				unset($origen); unset($destino); 
			}
			return $this->documento->asXML();
		}
		return '';
	}

	public function filtrarVisibles( $roles, $entidad = '' ) {
		if ( strlen($roles)==0 ) { return; }
		$credenciales = explode( ',', trim($roles, ',') );
		$super = in_array( 'Super', $credenciales );
		$xml = dom_import_simplexml( $this->documento );
		$xpath = new domXPath( $xml->ownerDocument );
		$items = $xpath->query( "//*[@roles]" );
		foreach( $items as $item ) {
			$autorizar = $item->getAttribute( 'roles' );
			$requisitos = explode( ',', trim( $autorizar, ',') );
			$comunes = array_intersect( $credenciales, $requisitos );
			if ( $autorizar == '-' || (!$super && $autorizar != '*' && count($comunes) == 0 ) ) {
				$item->parentNode->removeChild($item);
			}
		}
		if ( strlen($entidad)>0 ) {
			$items = $xpath->query( "//modelo[@id!='" . $entidad . "'][permisos]" );
			foreach( $items as $item ) {
				$item->parentNode->removeChild($item);
			}
		}
		unset( $xpath, $xml );
	}

	private function _exportarXml( $data, &$xml ) {
		if ( is_array($data) ) {
			foreach ( $data as $key => $value ) {
				if ( is_array($value) ) {
					if ( is_numeric($key) ) {
						$subnode = $xml->addChild('elemento');
						$subnode->addAttribute( 'num', $key );
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
	private function _exportarValorXml( $data, &$xml ) {
		if ( is_array($data) ) {
			foreach ( $data as $key => $value ) {
				if ( is_array($value) ) {
					if ( is_numeric($key) ) {
						$subnode = $xml->addChild('valor');
					} else {
						$subnode = $xml->addChild($key);
					}
					$this->_exportarValorXml( $value, $subnode );
				} else if ( is_numeric($key) ) {
					$xml->addChild("valor", htmlspecialchars( "$value" ));
				} else {
					$xml->addChild("$key", htmlspecialchars( "$value" ));
				}
			}
		}
	}
}

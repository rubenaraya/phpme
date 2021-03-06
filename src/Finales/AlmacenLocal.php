<?php
namespace MasExperto\ME\Finales;

use MasExperto\ME\Bases\Almacen;
use MasExperto\ME\M;
use DOMDocument;
use Mpdf\Mpdf;
use ZipArchive;

final class AlmacenLocal extends Almacen {

	public function Conectar( $rutas ) {
		$this->rutas = $rutas;
		return;
	}

	public function cargarArchivos( $destino = 1, $opciones = array() ) {
		$resultado = array();
		$resultado['contenidos'] = null;
		$resultado['errores'] = array();
		$resultado['estado'] = false;
		$ruta = $this->_seleccionarAlmacen( $destino );
		$tipos = ( 
			isset($opciones['tipos']) ? $opciones['tipos'] : 
			array( 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xsl', 'xslx', 'ppt', 'pptx', 'pdf', 'zip' ) 
		);
		$nombre = ( isset($opciones['nombre']) ? $opciones['nombre'] : '' );
		$reemplazar = ( isset($opciones['reemplazar']) ? $opciones['reemplazar'] : true );
		$carpeta = ( isset($opciones['carpeta']) ? '/' . $opciones['carpeta'] : '' );
		$peso = ( isset($opciones['peso']) ? $opciones['peso'] : '10 MB' );
		$factor = 1;
		$aux = explode( ' ', $peso );
		if ( count($aux)==2 ) {
			if ( strtoupper($aux[1]) == 'MB' ) { $factor = 1048576; }
			else { $factor = 1024; }
		}
		$peso_maximo = intval($aux[0]) * $factor;
		foreach ( M::E('ARCHIVOS') as $archivo => $valor ) {
			if ( is_array($valor) ) {
				$valido = true;
				$nombre_original = $valor['name'];
				$tipo = strtolower( pathinfo( $nombre_original, PATHINFO_EXTENSION ) );
				if ( $valor['size'] > $peso_maximo ) {
					$resultado['errores'][] = sprintf(dgettext('me', "El-archivo-'%s'-supera-%s"), $nombre_original, $peso);
					$valido = false;
				}
				if ( !in_array( $tipo, $tipos ) ) {
					if ( strlen($tipo)>0 || $tipos[0]!='*' ) {
						$resultado['errores'][] = sprintf(dgettext('me', "No-se-permiten-archivos-%s"), $tipo);
						$valido = false;
					}
				}
				if ( $valido ) {
					if ( strlen( $carpeta ) >0 && !is_dir( "$ruta$carpeta" ) ) {
						@mkdir( "$ruta$carpeta" );
						chmod( "$ruta$carpeta", 0755 );
					}
					$nombre_final = M::limpiarNombre( $nombre_original, $nombre );
					if ( !$reemplazar ) {
						$i = 1; 
						$aux = $nombre_final;
						while ( file_exists( trim("$ruta$carpeta/$nombre_final.$tipo", '.') ) ) {
							$nombre_final = "$aux($i)";
							$i++; 
							if ( $i > 99 ) break;
						}
					}
					if ( move_uploaded_file( 
						$valor['tmp_name'], 
						"$ruta$carpeta/" . utf8_decode( trim("$nombre_final.$tipo", '.') ) 
					)) {
						if ( $valor['error'] == 0 ) {
							$resultado['estado'] = true;
							$resultado['contenidos'][] = array(
								'nombre' => trim("$nombre_final.$tipo", '.'), 
								'absoluta' => trim("$ruta$carpeta/$nombre_final.$tipo", '.'), 
								'relativa' => trim("$carpeta/$nombre_final.$tipo", '.'), 
								'tipo' => $tipo, 
								'peso' => $valor['size'],
								'original' => $valor['name']
							);
						}
					}
				}				
			}
		}
		return $resultado;
	}

	public function adaptarImagenes( $destino, $archivos, $opciones = array() ) {
		$resultado = array();
		$resultado['contenidos'] = null;
		$resultado['errores'] = array();
		$resultado['estado'] = false;
		if ( !is_array($archivos) || count($archivos)==0 ) { 
			$resultado['errores'][] = dgettext('me', 'No-hay-imagenes');
			return $resultado; 
		}
		$ruta = $this->_seleccionarAlmacen( $destino );
		$formato = ( isset($opciones['formato']) ? $opciones['formato'] : 'jpg' );
		$calidad = ( isset($opciones['calidad']) ? $opciones['calidad'] : 95 );
		$ancho_max = ( isset($opciones['ancho']) ? $opciones['ancho'] : 600 );
		$alto_max = ( isset($opciones['alto']) ? $opciones['alto'] : 600 );
		$carpeta = ( isset($opciones['carpeta']) ? '/' . $opciones['carpeta'] : '' );
		foreach ( $archivos as $pos => $archivo ) {
			if ( is_array($archivo) ) {
				$valido = false;
				$tipo = strtolower($archivo['tipo']);
				$nombre = $archivo['nombre'];
				$ubicacion = $archivo['absoluta'];
				if ( $tipo == 'png' ) {
					$img_original = imagecreatefrompng( $ubicacion );
					$valido = true;
				} else if ( $tipo == 'jpg' || $tipo == 'jpeg' ) {
					$img_original = imagecreatefromjpeg( $ubicacion );
					$valido = true;
				} else if ( $tipo == 'gif' ) {
					$img_original = imagecreatefromgif( $ubicacion );
					$valido = true;
				}
				if ( $valido ) {
					if ( strlen( $carpeta ) >0 && !is_dir( "$ruta$carpeta" ) ) {
						@mkdir( "$ruta$carpeta" );
						chmod( "$ruta$carpeta", 0755 );
					}
					list( $ancho_real, $alto_real ) = getimagesize( $ubicacion );
					$x_ratio = $ancho_max / $ancho_real; 
					$y_ratio = $alto_max / $alto_real;
					if ( ($ancho_real <= $ancho_max) && ($alto_real <= $alto_max) ) {
						$ancho_final = $ancho_real;
						$alto_final = $alto_real;
					} else if ( ($x_ratio * $alto_real) < $alto_max ) {
						$alto_final = ceil( $x_ratio * $alto_real );
						$ancho_final = $ancho_max;
					} else {
						$ancho_final = ceil( $y_ratio * $ancho_real );
						$alto_final = $alto_max;
					}
					$tmp = imagecreatetruecolor( $ancho_final, $alto_final );
					if ( $tipo == 'png' || $tipo == 'gif' ) { 
						imagefilledrectangle($tmp, 0, 0, $ancho_real, $alto_real, imagecolorallocate($tmp,  255, 255, 255));
					}
					imagecopyresampled( $tmp, $img_original, 0, 0, 0, 0, $ancho_final, $alto_final, $ancho_real, $alto_real );
					switch ( $formato ) {
						case 'png': 
							$nombre_final = pathinfo( $nombre, PATHINFO_FILENAME ) . '.png';
							$ruta_final = "$ruta$carpeta/$nombre_final";
							imagepng( $tmp, $ruta_final );	
							break;
						case 'jpg': 
						default:
							$nombre_final = pathinfo( $nombre, PATHINFO_FILENAME ) . '.jpg';
							$ruta_final = "$ruta$carpeta/$nombre_final";
							imagejpeg( $tmp, $ruta_final, $calidad );
							break;
					}
					imagedestroy( $tmp );
					imagedestroy( $img_original );
					if ( $ubicacion != $ruta_final ) { unlink( $ubicacion ); }
					$resultado['estado'] = true;
					$resultado['contenidos'][] = array(
						'nombre' => $nombre_final, 
						'absoluta' => "$ruta$carpeta/$nombre_final", 
						'completa' => str_replace( M::E('RUTA/RAIZ'), '', "$ruta$carpeta/$nombre_final" ), 
						'relativa' => "$carpeta/$nombre_final", 
						'ancho' => $ancho_final, 
						'alto' => $alto_final 
					);
				} else {
					$resultado['errores'][] = sprintf(dgettext('me', "'%s'-no-es-una-imagen-valida"), $nombre);
				}
			} else {
				$resultado['errores'][] = dgettext('me', 'No-hay-imagenes');
			}
		}
		return $resultado;
	}

	public function extraerZip( $destino, $archivos, $opciones = array() ) {
		$resultado = array();
		$resultado['contenidos'] = null;
		$resultado['errores'] = array();
		$resultado['estado'] = false;
		if ( !is_array($archivos) || count($archivos)==0 ) { 
			$resultado['errores'][] = dgettext('me', 'No-hay-archivos-para-descomprimir');
			return $resultado; 
		}
		$ruta = $this->_seleccionarAlmacen( $destino );
		$carpeta = ( isset($opciones['carpeta']) ? '/' . $opciones['carpeta'] : '' );
		foreach ( $archivos as $pos => $archivo ) {
			if ( is_array($archivo) ) {
				$ubicacion = $archivo['absoluta'];
				$tipo = strtolower($archivo['tipo']);
				$nombre = strtolower($archivo['nombre']);
				if ( $tipo == 'zip' ) {
					if ( strlen( $carpeta ) >0 && !is_dir( "$ruta$carpeta" ) ) {
						@mkdir( "$ruta$carpeta" );
						chmod( "$ruta$carpeta", 0755 );
					}
					if ( file_exists($ubicacion) && is_dir("$ruta$carpeta") ) {
						$zip = new ZipArchive;
						if ( $zip->open( $ubicacion ) === true ) {
							$zip->extractTo( "$ruta$carpeta" );
							$zip->close();
							$resultado['estado'] = true;
							$resultado['contenidos'][] = array(
								'nombre' => $nombre,
								'ubicacion' => "$ruta$carpeta" 
							);
						}
						unset($zip);
						unlink($ubicacion);
					}
				}
			}
		}
		return $resultado;
	}

	public function empaquetarZip( $origen, $destino, $opciones = array() ) {
		$resultado = array();
		$resultado['contenidos'] = null;
		$resultado['errores'] = array();
		$resultado['estado'] = false;
		$ruta = $this->_seleccionarAlmacen( $origen );
		$carpeta = ( isset($opciones['carpeta']) ? '/' . $opciones['carpeta'] : '' );
		$ruta2 = $this->_seleccionarAlmacen( $destino );
		$ubicacion = ( isset($opciones['ubicacion']) ? '/' . $opciones['ubicacion'] : '' );
		$nombre = ( isset($opciones['nombre']) ? $opciones['nombre'] : date('Ymd_His') . '.zip' );
		if ( substr($nombre, -4) != '.zip' ) { $nombre .= '.zip'; }
		$ruta_origen = "$ruta$carpeta";
		if ( !is_dir( $ruta_origen ) ) {
			$resultado['errores'][] = dgettext('me', 'No-existe-la-ruta-de-origen');
			return $resultado; 
		}
		$ruta_destino = "$ruta2$ubicacion/$nombre";
		if ( substr($ruta_origen, -1) === '/' ) { $ruta_origen = substr($ruta_origen, 0, -1); }
		if ( substr($ruta_destino, -1) === '/' ) { $ruta_destino = substr($ruta_destino, 0, -1); }
		$pos = strlen($ruta_origen) + 1;
		if ( file_exists($ruta_destino) ) { unlink($ruta_destino); }
		$zip = new ZipArchive;
		if ( $zip->open( $ruta_destino, ZipArchive::CREATE ) !== TRUE ) {
			$resultado['errores'][] = dgettext('me', 'No-se-puede-abrir-la-ruta-de-destino');
			return $resultado; 
		} else {
			$resultado['estado'] = true;
			$resultado['contenidos'][] = array(
				'origen' => $ruta_origen, 
				'destino' => $ruta_destino
			);
		}
		if ( is_file($ruta_origen) ) {
			$zip->addFile( $ruta_origen, substr($ruta_origen, $pos) );
		} else {
			if ( !is_dir($ruta_origen) ) {
				$zip->close();
				unlink( $ruta_destino );
				$resultado['errores'][] = dgettext('me', 'No-se-puede-abrir-la-ruta-de-origen');
				return $resultado; 
			}
			$this->_agregarEnZip( $ruta_origen, $pos, $zip );
		}
		$zip->close();
		return $resultado;
	}

	public function abrirArchivo( $ubicacion ) {
		$resultado = array();
		$resultado['contenido'] = '';
		$resultado['error'] = '';
		$resultado['estado'] = false;
		if ( file_exists( $ubicacion ) && !is_dir( $ubicacion ) ) {
			$tipo = strtolower( pathinfo( $ubicacion, PATHINFO_EXTENSION ) );
			if ( in_array( $tipo, array('txt','xml','html','json') ) ) {
				if ( $tipo == 'xml' ) {
					$xml = simplexml_load_file( $ubicacion, 'SimpleXMLElement', LIBXML_NOCDATA );
					if ( $xml ) {
						$json = json_encode( $xml );
						$resultado['contenido'] = array( $xml->getName() => json_decode( $json, true ) );
						$resultado['estado'] = true;
					} else {
						$resultado['contenido'] = '';
						$resultado['error'] = dgettext('me', 'El-formato-del-archivo-no-es-valido');
					}
				} else {
					$temp = file_get_contents( $ubicacion );
					if ( strlen($temp)>0 ) {
						switch ( $tipo ) {
							case 'txt':
								$temp = str_replace( array("\r\n", "\r"), "\n", $temp);
								while ( substr_count( $temp, "\n\n" )>0 ) { $temp = str_replace("\n\n", "\n", $temp); }
								$resultado['contenido'] = explode( "\n", trim( $temp ) );
								$resultado['estado'] = true;
								break;
							case 'html':
								$resultado['contenido'] = array( trim( $temp ) );
								$resultado['estado'] = true;
								break;
							case 'json':
								$resultado['contenido'] = @json_decode( trim( $temp ), true );
								if ( (json_last_error() === JSON_ERROR_NONE) ) {
									$resultado['estado'] = true;
								} else {
									$resultado['contenido'] = '';
									$resultado['error'] = dgettext('me', 'El-formato-del-archivo-no-es-valido');
								}
								break;
						}
					} else {
						$resultado['error'] = dgettext('me', 'El-archivo-no-tiene-contenido');
					}
				}
			} else {
				$resultado['error'] = sprintf(dgettext('me', "El-tipo-'%s'-no-esta-permitido"), $tipo);
			}
		} else {
			$resultado['error'] = dgettext('me', 'El-archivo-no-existe');
		}
		return $resultado;
	}

	public function guardarArchivo( $destino, $contenido, $opciones = array() ) {
		$resultado = array();
		$resultado['ubicacion'] = '';
		$resultado['error'] = '';
		$resultado['estado'] = false;
		if ( !is_array($contenido) || count($contenido)==0 ) { 
			$resultado['error'] = dgettext('me', 'No-hay-contenido-para-guardar');
			return $resultado; 
		}
		$ruta = $this->_seleccionarAlmacen( $destino );
		$carpeta = ( isset($opciones['carpeta']) ? '/' . $opciones['carpeta'] : '' );
		$formato = ( isset($opciones['formato']) ? $opciones['formato'] : '' );
		$nombre = ( isset($opciones['nombre']) ? $opciones['nombre'] : '' );
		$nombre_final = M::limpiarNombre( $nombre, $nombre );
		if ( strlen( $carpeta ) >0 && !is_dir( "$ruta$carpeta" ) ) {
			@mkdir( "$ruta$carpeta" );
			chmod( "$ruta$carpeta", 0755 );
		}
		switch ( $formato ) {
			case Almacen::F_TXT:
				$guardar = implode( "\n", $contenido );
				$ubicacion = "$ruta$carpeta/$nombre_final.txt";
				if ( file_put_contents( $ubicacion, $guardar )>0 ) {
					$resultado['ubicacion'] = $ubicacion;
					$resultado['estado'] = true;
				} else {
					$msg = sprintf(dgettext('me', "No-se-guardo-'%s'"), $ubicacion);
					$resultado['error'] = $msg;
					trigger_error( 'AlmacenLocal.guardarArchivo: ' . $msg, E_USER_ERROR );
				}
				break;
			case Almacen::F_HTML:
				$guardar = implode( "\n", $contenido );
				$ubicacion = "$ruta$carpeta/$nombre_final.html";
				if ( file_put_contents( $ubicacion, $guardar )>0 ) {
					$resultado['ubicacion'] = $ubicacion;
					$resultado['estado'] = true;
				} else {
					$msg = sprintf(dgettext('me', "No-se-guardo-'%s'"), $ubicacion);
					$resultado['error'] = $msg;
					trigger_error( 'AlmacenLocal.guardarArchivo: ' . $msg, E_USER_ERROR );
				}
				break;
			case Almacen::F_XML:
				foreach ( $contenido as $nombre => $valor ) {
					$xml = new DomDocument( '1.0','UTF-8' );
					$xml->preserveWhiteSpace = false;
					$xml->formatOutput = true;
					$xml->appendChild( $this->_convertirXml( $nombre, $xml, $valor ) );
					$ubicacion = "$ruta$carpeta/$nombre_final.xml";
					if ( $xml->save( $ubicacion ) ) {
						$resultado['ubicacion'] = $ubicacion;
						$resultado['estado'] = true;
					} else {
						$msg = sprintf(dgettext('me', "No-se-guardo-'%s'"), $ubicacion);
						$resultado['error'] = $msg;
						trigger_error( 'AlmacenLocal.guardarArchivo: ' . $msg, E_USER_ERROR );
					}
					unset($xml);
					break;
				}
				break;
			case Almacen::F_PDF:
				$ubicacion = "$ruta$carpeta/$nombre_final.pdf";
				$pdf = $this->_crearDocumentoPdf( $contenido, $ubicacion, $opciones );
				if ( $pdf ) {
					$resultado['ubicacion'] = $ubicacion;
					$resultado['estado'] = true;
				} else {
					$msg = sprintf(dgettext('me', "No-se-guardo-'%s'"), $ubicacion);
					$resultado['error'] = $msg;
					trigger_error( 'AlmacenLocal.guardarArchivo: ' . $msg, E_USER_ERROR );
				}
				break;
			case Almacen::F_JSON:
			default:
				$guardar = json_encode( $contenido );
				if ( strlen($guardar)>0 ) {
					$ubicacion = "$ruta$carpeta/$nombre_final.json";
					if ( file_put_contents( $ubicacion, $guardar )>0 ) {
						$resultado['ubicacion'] = $ubicacion;
						$resultado['estado'] = true;
					} else {
						$msg = sprintf(dgettext('me', "No-se-guardo-'%s'"), $ubicacion);
						$resultado['error'] = $msg;
						trigger_error( 'AlmacenLocal.guardarArchivo: ' . $msg, E_USER_ERROR );
					}
				} else {
					$resultado['error'] = dgettext('me', 'No-hay-datos-para-guardar');
				}
				break;
		}
		return $resultado;
	}

	public function borrarArchivos( $lista ) {
		$resultado = array();
		$resultado['errores'] = array();
		$resultado['estado'] = false;
		if (is_array($lista)) {
			foreach( $lista as $caso ) {
				if ( file_exists( $caso ) && !is_dir( $caso ) ) {
					$resultado['estado'] = unlink( $caso );
				} else {
					$resultado['errores'][] = sprintf(dgettext('me', "El-archivo-'%s'-no-existe"), $caso);
				}
			}
		} else {
			if ( file_exists( $lista ) && !is_dir( $lista ) ) {
				$resultado['estado'] = unlink( $lista );
			} else {
				$resultado['errores'][] = sprintf(dgettext('me', "El-archivo-'%s'-no-existe"), $lista);
			}
		}
		return $resultado;
	}

	public function copiarCarpetas( $origen, $destino ) {
		$resultado = false;
		if ( is_dir($origen) ) {
			if ( !is_dir($destino) ) {
				@mkdir( $destino );
				chmod( $destino, 0755 );
			}
			$dir = dir( $origen );
			while ( false !== ($item = $dir->read() ) ) {
				if ( $item == '.' || $item == '..' ) { continue; }
				$ruta = "$origen/$item";
				if ( is_dir($ruta) ) {
					$this->copiarCarpetas( $ruta, "$destino/$item" );
					continue;
				}
				$resultado = copy( $ruta, "$destino/$item" );
				chmod( "$destino/$item", 0755 );
			}
			$dir->close();
		}
		return $resultado;
	}

	public function crearCarpeta( $ruta ) {
		$resultado = false;
		if ( is_dir($ruta)==false && is_dir(dirname($ruta)) ) {
			@mkdir($ruta);
			if ( is_dir($ruta) ) {
				chmod( $ruta, 0775 );
				$resultado = true;
			}
		}
		return $resultado;
	}

	public function borrarCarpetas( $origen, $quitar = false ) {
		$resultado = false;
		if ( substr($origen, -1) == '/' ) {
			$origen = substr($origen, 0, -1);
		}
		if ( file_exists($origen) && is_dir($origen) && is_readable($origen) ) {
			$dir = opendir( $origen );
			while ( $item = readdir( $dir ) ) {
				if ( $item == '.' || $item == '..' ) { continue; }
				$ruta = "$origen/$item";
				if ( is_dir( $ruta ) ) {
					$resultado = $this->borrarCarpetas( $ruta, $quitar );
					continue;
				} else {
					$resultado = unlink( $ruta );
				}
			}
			closedir( $dir );
			if ( $quitar ) {
				$resultado = ( rmdir( $origen ) );
			}
		}
		return $resultado;
	}

	public function explorarCarpeta( $origen, $carpeta = '', $opciones = array() ) {
		$resultado = array();
		$resultado['contenidos'] = null;
		$resultado['errores'] = array();
		$resultado['estado'] = false;
		$ruta = $this->_seleccionarAlmacen( $origen );
		$carpeta = ( strlen($carpeta)>0 ? '/' . $carpeta : '' );
		$tipos = ( isset($opciones['tipos']) ? $opciones['tipos'] : array() );
		$subcarpetas = ( isset($opciones['subcarpetas']) ? $opciones['subcarpetas'] : false );
		$ubicacion = "$ruta$carpeta";
		if ( file_exists($ubicacion) && is_dir($ubicacion) ) {
			$this->_revisarCarpeta( $ruta, $carpeta, $tipos, $subcarpetas, $resultado['contenidos'] );
		} else {
			$resultado['errores'][] = sprintf(dgettext('me', "La-carpeta-'%s'-no-existe"), $carpeta);
		}
		return $resultado;
	}

	private function _crearDocumentoPdf( $contenido, $ubicacion, $opciones = array() ) {
		$estado = false;
		$paginas = count($contenido);
		$titulo			= ( isset($opciones['titulo']) ? $opciones['titulo'] : '' );
		$autor			= ( isset($opciones['autor']) ? $opciones['autor'] : '' );
		$asunto			= ( isset($opciones['asunto']) ? $opciones['asunto'] : '' );
		$creador		= ( isset($opciones['creador']) ? $opciones['creador'] : '' );
		$encabezado		= ( isset($opciones['encabezado']) ? $opciones['encabezado'] : '' );
		$pie			= ( isset($opciones['pie']) ? $opciones['pie'] : '' );
		$estilos		= ( isset($opciones['estilos']) ? $opciones['estilos'] : '' );
		$indice			= ( isset($opciones['indice']) ? $opciones['indice'] : '' );
		$proteccion		= ( isset($opciones['proteccion']) ? $opciones['proteccion'] : '' );
		$orientacion	= ( isset($opciones['orientacion']) ? $opciones['orientacion'] : '' );
		$papel			= ( isset($opciones['papel']) ? $opciones['papel'] : 'LETTER' );
		$fuente			= ( isset($opciones['fuente']) ? $opciones['fuente'] : 'Arial' );
		$portada		= ( isset($opciones['portada']) ? $opciones['portada'] : false );
		$margen_izq		= ( isset($opciones['margen_izq']) ? $opciones['margen_izq'] : 15 );
		$margen_der		= ( isset($opciones['margen_der']) ? $opciones['margen_der'] : 15 );
		$margen_sup		= ( isset($opciones['margen_sup']) ? $opciones['margen_sup'] : 20 );
		$margen_inf		= ( isset($opciones['margen_inf']) ? $opciones['margen_inf'] : 20 );
		if ( is_array($contenido) && $paginas > 0 ) {
			$estado = true;
			$pdf = new Mpdf([
				'mode' => 'utf-8',
				'format' => "$papel$orientacion",
				'default_font' => $fuente,
				'margin_left' => $margen_izq,
				'margin_right' => $margen_der,
				'margin_top' => $margen_sup,
				'margin_bottom' => $margen_inf
			]);
			$pdf->use_kwt = true;
			$pdf->SetTitle( $titulo );
			$pdf->SetAuthor( $autor );
			$pdf->SetSubject( $asunto );
			$pdf->SetCreator( $creador );
			if ( strlen($proteccion)>0 ) {
				$pdf->SetProtection( array('print'), '', $proteccion );			
			}
			$pdf->SetDisplayMode( 'fullwidth', 'continuous' );
			if ( file_exists($estilos) ) {
				$pdf->WriteHTML( file_get_contents( $estilos ), 1 );
			}
			if ( $portada ) {
				$paginas = $paginas - 1;
				$pag_titulo = '';
				$pag_contenido = '';
				if ( isset($contenido[0]) ) {
					$pag_titulo = ( isset($contenido[0]['titulo']) ? $contenido[0]['titulo'] : '' );
					$pag_contenido = ( isset($contenido[0]['contenido']) ? $contenido[0]['contenido'] : '' );
				}
				$pag_contenido = str_replace('<pagebreak/>', '<pagebreak />', $pag_contenido);
				$pdf->WriteHTML( '<html lang="' . M::E('M_IDIOMA'). '"><bookmark content="'. $pag_titulo .'" />' . $pag_contenido . '</html>', 2 );
				if ( $paginas >0 ) {
					if ( strlen( $encabezado )>0 ) { $pdf->SetHTMLHeader( $encabezado ); }
					$pdf->AddPage( '', '', 2 );
				}
			}
			if ( $paginas > 0 ) {
				if ( strlen($indice)>0 ) {
					$pdf->TOCpagebreak( '','','',1,1,'','','','','','','','','','','','','','','', '<h2>' . $indice . '</h2>', '', $indice );
				}
				if ( strlen($encabezado)>0 ) { $pdf->SetHTMLHeader( $encabezado ); }
				if ( strlen($pie)>0 ) { $pdf->SetHTMLFooter( $pie ); }
				foreach( $contenido as $pos => $pagina ) {
					if ( ($portada && $pos >0 ) || !$portada ) {
						$pag_titulo = ( isset($pagina['titulo']) ? $pagina['titulo'] : '' );
						$pag_contenido = ( isset($pagina['contenido']) ? $pagina['contenido'] : '' );
						$pag_contenido = str_replace('<pagebreak/>', '<pagebreak />', $pag_contenido);
						$pag_nivel = ( isset($pagina['nivel']) ? $pagina['nivel'] : 1 );
						if ( $pos >1 ) {
							$pdf->AddPage( $orientacion );
						}
						$pdf->TOC_Entry( $pag_titulo, ($pag_nivel - 1) );
						$pdf->Bookmark( $pag_titulo, ($pag_nivel - 1) );
						$pdf->WriteHTML( '<html lang="' . M::E('M_IDIOMA'). '">' . $pag_contenido . '</html>', 2 );
					}
				}
			}
			$pdf->Output( $ubicacion, 'F' );
			unset($pdf);
		}
		return $estado;
	}

	private function _seleccionarAlmacen( $opcion ) {
		switch ( $opcion ) {
			case Almacen::PRIVADO: 
				$ruta = $this->rutas['PRIVADO'];
				break;
			case Almacen::PUBLICO: 
				$ruta = $this->rutas['PUBLICO'];
				break;
		}
		return $ruta;
	}

	private function _agregarEnZip( $origen, $pos, &$zip ) {
		$dir = opendir( $origen );
		while( false !== ($item = readdir( $dir )) ) {
			if ( ($item !='.' ) && ($item != '..') ) {
				if ( is_dir( "$origen/$item" ) ) {
					$this->_agregarEnZip( "$origen/$item", $pos, $zip );
				} else {
					$zip->addFile( "$origen/$item", substr("$origen/$item", $pos) ); 
				}
			}
		}
		closedir( $dir );
	}

	private function _convertirXml( $nombre, &$xml, $contenido = array() ) {
		if ( is_numeric($nombre) ) {
			$elemento = $xml->createElement( 'item' );
			$elemento->setAttribute( 'id', $nombre );
		} else {
			$elemento = $xml->createElement( $nombre );
		}
		if ( is_array($contenido) ) {
			if ( isset($contenido['@attributes']) ) {
				foreach( $contenido['@attributes'] as $atributo => $valor ) {
					$elemento->setAttribute( $atributo, $valor );
				}
				unset($contenido['@attributes']);
			}
		}
		if ( is_array($contenido) ) {
			foreach( $contenido as $clave => $valor ) {
				if ( is_array($valor) && is_numeric(key($valor)) ) {
					foreach( $valor as $c => $v ) {
						$elemento->appendChild( $this->_convertirXML( $clave, $v, $xml ) );
					}
				} else {
					$elemento->appendChild( $this->_convertirXML( $clave, $valor, $xml ) );
				}
				unset($contenido[$clave]);
			}
		}
		if ( !is_array($contenido) ) {
			if ( strlen(strval($contenido))>50 ) {
				$elemento->appendChild( $xml->createCDATASection( $contenido ) );
			} else {
				$elemento->appendChild( $xml->createTextNode( $contenido ) );
			}
		}
		return $elemento;
	}

    private function _revisarCarpeta( $ruta, $carpeta, $tipos, $subcarpetas, &$lista ) {
        $resultado = array();
		$filtrar = count($tipos)>0;
        $raiz = scandir( "$ruta$carpeta" );
        foreach( $raiz as $value ) {
            if ( $value === '.' || $value === '..' ) { continue; }
            if ( is_file( "$ruta$carpeta/$value" ) ) {
                if ( !$filtrar || in_array( strtolower( pathinfo( "$ruta$carpeta/$value", PATHINFO_EXTENSION) ), $tipos ) ) {
                    $lista[] = $resultado[] = "$carpeta/$value";
                }
                continue;
            }
            if ( $subcarpetas ) {
                foreach( $this->_revisarCarpeta( $ruta, "$carpeta/$value", $tipos, $subcarpetas, $lista ) as $value2 ) {
                    $lista[] = $resultado[] = $value2;
                }
            }
        }
        return $resultado;
    }
}

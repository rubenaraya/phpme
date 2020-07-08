<?php 
namespace MasExperto\ME\Finales;

use MasExperto\ME\Bases\Ruteador;
use MasExperto\ME\M;
use DateTime;
use function dgettext;

final class RuteadorHttp extends Ruteador 
{
	function __construct( $front = '', $back = '' ) {
        parent::__construct( $front, $back );
		$this->campos = &$_POST;
		$this->estados['200_OK']			= array(200, '');
		$this->estados['201_CREATED']		= array(201, '');
		$this->estados['204_NOCONTENT']		= array(204, '');
		$this->estados['304_NOMODIFIED']	= array(304, '');
		$this->estados['400_BADREQUEST']	= array(400, dgettext('me', 'Peticion-no-valida') );
		$this->estados['401_UNAUTHORIZED']	= array(401, dgettext('me', 'Acceso-denegado') );
		$this->estados['403_FORBIDDEN']		= array(403, dgettext('me', 'Acceso-prohibido') );
		$this->estados['404_NOTFOUND']		= array(404, dgettext('me', 'Recurso-no-encontrado') );
		$this->estados['405_NOTALLOWED']	= array(405, dgettext('me', 'Accion-no-permitida') );
		$this->estados['415_UNSUPPORTED']	= array(415, dgettext('me', 'No-soportado') );
		$this->estados['422_UNPROCESSABLE']	= array(422, dgettext('me', 'No-procesable') );
		$this->estados['500_INTERNALERROR']	= array(500, dgettext('me', 'Error-interno') );
	}

	public function procesarSolicitud() {
		$idiomas = ( isset(M::$entorno['M_IDIOMAS']) && is_array(M::$entorno['M_IDIOMAS']) ? M::$entorno['M_IDIOMAS'] : array( 'es_CL' ) );
		$traduccion = ( isset(M::$entorno['M_TRADUCCION']) ? M::$entorno['M_TRADUCCION'] : 'servicio' );
		M::$entorno['M_SERVIDOR'] = ( $this->_V($_SERVER, 'HTTPS')=='on' ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
        M::$entorno['RUTA']['PUNTOFINAL'] = str_replace( '\\', '/', getcwd() );
        M::$entorno['RUTA']['RAIZ'] = str_replace( '\\', '/', $_SERVER["DOCUMENT_ROOT"] );
		M::$entorno['M_PUNTOFINAL'] = str_replace( M::$entorno['RUTA']['RAIZ'], '', M::$entorno['RUTA']['PUNTOFINAL'] );
		M::$entorno['M_FRONTEND'] = str_replace( M::$entorno['RUTA']['RAIZ'], '', M::$entorno['RUTA']['FRONTEND'] );
		M::$entorno['SOLICITUD']['URL'] = ( $this->_V($_SERVER, 'REDIRECT_URL')!='' ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'] );
		M::$entorno['SOLICITUD']['COMANDO'] = $this->_V($_REQUEST, 'PATH_INFO');
		$url = parse_url( $_SERVER['REQUEST_URI'] );
		if ( isset($url['query']) ) {
			if ( substr_count( $url['query'], '#' )>0 ) {
				$url['query'] = substr( $url['query'], 0, strpos( $url['query'], '#' ) );
			}
			parse_str( $url['query'], $this->parametros );
			$parametros = $this->parametros;
			unset( $parametros['M'] );
			unset( $parametros['M_IDIOMA'] );
			M::$entorno['PARAMETROS'] = $parametros;
			M::$entorno['SOLICITUD']['PARAM'] = http_build_query( $parametros );
		} else {
			M::$entorno['SOLICITUD']['PARAM'] = '';
			M::$entorno['PARAMETROS'] = '';
		}
		$idioma = $this->_elegirIdioma( $idiomas );
		M::$entorno['M_IDIOMA'] = $idioma;
		putenv( "LANG=" . $idioma );
		putenv( "LANGUAGE=" . $idioma );
		if ( defined('LC_MESSAGES') ) {
			setlocale( LC_MESSAGES, $idioma );
		} else {
			setlocale( LC_ALL, $idioma );
		}
		bindtextdomain( 'me', dirname(__DIR__) . '/Locales');
		bind_textdomain_codeset( 'me', 'UTF-8' );
		bindtextdomain( $traduccion, M::$entorno['RUTA']['BACKEND'] . '/locales' );
		bind_textdomain_codeset( $traduccion, 'UTF-8' );
		textdomain( $traduccion );
		M::$entorno['SOLICITUD']['METODO'] = $_SERVER['REQUEST_METHOD'];
		M::$entorno['SOLICITUD']['ACCION'] = M::$entorno['SOLICITUD']['METODO'];
		$atributos = $this->_analizarUrl( M::$entorno['SOLICITUD']['COMANDO'], M::$entorno['SOLICITUD']['ACCION'] );
		$this->_evaluarTipoContenido();
		switch ( M::$entorno['M_ENTRADA'] ) {
			case 'JSON':
				$json = file_get_contents( 'php://input' );
				$json = json_decode( html_entity_decode( $json ), true );
				if ( is_array($json) ) { $this->campos = $this->campos + $json; }
				break;
			case 'MULTIPART':
				foreach($_FILES as $key => $FILES) {
					M::$entorno['ARCHIVOS'] = $this->_ordenarArchivos( $_FILES[$key] );
				}
				break;
			case 'HTML':
			case 'XML':
				break;
		}
		M::$entorno['M_ESTADO'] = 200;
		if ( count($atributos)>0 ) {
			if ( $atributos['SOLICITUD']['ACCION']!='' ) { M::$entorno['SOLICITUD']['ACCION'] = $atributos['SOLICITUD']['ACCION']; }
			M::$entorno['SOLICITUD']['COMANDO'] = $atributos['SOLICITUD']['COMANDO'];
			M::$entorno['RECURSO']['COLECCION'] = $atributos['RECURSO']['COLECCION'];
			M::$entorno['RECURSO']['ELEMENTO'] = $atributos['RECURSO']['ELEMENTO'];
			M::$entorno['ANTECESOR']['COLECCION'] = $atributos['ANTECESOR']['COLECCION'];
			M::$entorno['ANTECESOR']['ELEMENTO'] = $atributos['ANTECESOR']['ELEMENTO'];
			M::$entorno['SOLICITUD']['OPERACION'] = $atributos['SOLICITUD']['OPERACION'];
			$salida = $atributos['M_SALIDA'];
		} else {
			$this->cambiarEstado( $this->estados['400_BADREQUEST'], dgettext('me', 'Peticion-no-valida') );
			$this->Salir( true );
		}
		if ( $salida!='' ) { M::$entorno['M_SALIDA'] = strtoupper($salida); }
		$callback = $this->_V($this->parametros, 'callback');
		if ( strlen( $callback )>0 && M::$entorno['SOLICITUD']['METODO'] == 'GET' ) {
			M::$entorno['CALLBACK'] = $callback;
			M::$entorno['M_SALIDA'] = 'JSON';
		}
		try { $fecha = new DateTime( $this->_V($this->parametros, 'M_FECHA') ); }
		catch ( \Exception $e ) { $fecha = new DateTime(); }
		M::$entorno['M_FECHA'] = $fecha->format('Y-m-d');
		M::$entorno['M_PERIODO'] = $fecha->format('Ym');
		M::$entorno['M_AHORA'] = $fecha->format('YmdHi');
		M::$entorno['M_DIA'] = $fecha->format('d');
		M::$entorno['M_MES'] = $fecha->format('m');
		M::$entorno['M_AÑO'] = $fecha->format('Y');
		M::$entorno['AÑO'] = date('Y');
		M::$entorno['FECHA'] = date('d-m-Y');
		M::$entorno['HORA'] = date('H:i');
		M::$entorno['UID'] = uniqid();
		ob_start();
	}

	public function enviarRespuesta( $contenido = '', $opciones = array() ) {
		$eliminar = ( isset($opciones['eliminar']) ? $opciones['eliminar'] : false );
		if ( function_exists('http_response_code') ) {
			http_response_code( M::E('M_ESTADO') );
		} else {
			header( ':', true, M::E('M_ESTADO') );
		}
		switch ( M::E('M_SALIDA') ) {
			case 'TEXT': 
				header( 'Content-Type: text/plain; charset=utf-8' );
				if ( M::E('M_ESTADO') < 400 ) {
					if ( is_array($contenido) ) { $contenido = print_r( $contenido, true); }
				} else {
					$contenido = '';
					if ( isset(M::$entorno['ERRORES']) ) {
						foreach ( M::$entorno['ERRORES'] as $error ) {
							$contenido = $contenido . htmlspecialchars( $error['mensaje'] ) . chr(10);
						}
					}
				}
				echo $contenido;
				break;
			case 'HTML': 
				header( 'Content-Type: text/html; charset=utf-8' );
				if ( M::E('M_ESTADO') < 400 ) {
					if ( is_array($contenido) ) {
						$contenido = M::convertirMatrizHtml( $contenido, $opciones ); 
					}
				} else {
					$contenido = '';
					if ( isset(M::$entorno['ERRORES']) ) {
						foreach ( M::$entorno['ERRORES'] as $error ) {
							$contenido = $contenido . htmlspecialchars( $error['mensaje'] ) . chr(10);
						}
					}
				}
				echo $contenido;
				break;
			case 'JS': 
				header( 'Content-Type: text/javascript; charset=utf-8' ); 
				echo $contenido;
				break;
			case 'JPG': 
			case 'JPEG': 
			case 'PNG': 
				if ( M::E('M_ESTADO') < 400 ) {
					if ( file_exists($contenido) && !is_dir($contenido) ) {
						$aux = ( M::E('M_SALIDA') == 'PNG' ? 'image/png': 'image/jpeg' );
						header( 'Expires: Sat, 14 May 1966 20:00:00 GMT' );
						header( 'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0' );
						header( 'Pragma: no-cache' );
						header( "Content-Type: $aux" );
						header( 'Content-Length: ' . filesize( $contenido ) );
						readfile( $contenido );
						flush();
						if ( $eliminar ) {
							sleep(1);
							@unlink( $contenido );
						}
					}
				}
				break;
			default: 
				$componente = '\MasExperto\ME\Emisores\Emisor' . ucfirst(strtolower(M::E('M_SALIDA')));
				if ( class_exists( $componente, true ) ) {
					$emisor = new $componente;
					if ( M::E('M_ESTADO') < 400 ) {
						$emisor->Imprimir( $contenido, $opciones );
					} else {
						$emisor->mostrarErrores( M::E('ERRORES') );
					}
					unset($emisor);
				} else {
					$this->cambiarEstado( $this->estados['415_UNSUPPORTED'], dgettext('me', 'Formato-de-salida-no-admitido') );
					$contenido = '';
					if ( isset(M::$entorno['ERRORES']) ) {
						foreach ( M::$entorno['ERRORES'] as $error ) {
							$contenido = $contenido . htmlspecialchars( $error['mensaje'] ) . chr(10);
						}
					}
					header( 'Content-Type: text/plain; charset=utf-8' );
					echo $contenido;
				}
				break;
		}
		ob_end_flush();
	}

	public function cambiarEstado( $datos, $mensaje = '' ) {
		$estado = 0;
		if ( is_array($datos) ) {
			if ( strlen($mensaje)==0 ) { $mensaje = $datos[1]; }
			$estado = $datos[0];
		}
		if ( $estado >= 200 ) { M::$entorno['M_ESTADO'] = $estado; }
		M::$entorno['ERRORES'][] = array( 
			'mensaje' => $mensaje, 
			'estado' => $estado 
		);
	}

	public function guardarCache($minutos = 0 ) {
		if ( M::E('M_ESTADO') < 400 ) {
			if ( $minutos > 0 ) {
				header( 'Cache-Control: public, max-age='. $minutos * 60 .'' );
				header( 'Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT' );
			} else {
				header( 'Expires: Sat, 14 May 1966 20:00:00 GMT' );
				header( 'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0' );
				header( 'Pragma: no-cache' );
			}
		}
	}

	public function Redirigir( $destino ) {
		$destino = str_replace( array('\r\n','\r','\n'), '', $destino);
		$destino = str_replace( ' ', '+', $destino);
		if ( strlen($destino)>0 ) {
            header( 'Location: ' . $destino );
        }
		exit();
	}

	public function revisarCredencial( $canal = '*' ) {
		$token = '';
		if ( $canal == '*' ) {
			if ( isset($_REQUEST['Authorization']) ) { $canal = 'HEADER'; }
			else if ( isset($_COOKIE['M']) ) { $canal = 'COOKIE'; }
			else { $canal = 'URL'; }
		}
		switch ( $canal ) {
			case 'HEADER': 
				$token = ( isset($_REQUEST['Authorization']) ? $_REQUEST['Authorization'] : '' );
				break;
			case 'COOKIE': 
				$token = ( isset($_COOKIE['M']) ? $_COOKIE['M'] : '' );
				break;
			case 'URL': 
				$token = $this->_V($this->parametros, 'M');
				break;
		}
		return $token;
	}

	public function comprobarToken( $token, $tipo = 'sesion' ) {
		$estado = 0;
		$resultado = 'no-valido';
		if ( $token == '' ) {
			$resultado = 'vacio';
		} else {
			$partes = explode( '.', $token );
			if ( count($partes)==3 ) {
				$valor = json_decode( base64_decode($partes[1]), true );
				if ( isset($valor['uid']) && isset($valor['expira']) ) {
					 if ( date('YmdHi') > $valor['expira'] ) {
						$resultado = 'expirado';
					 } else {
						$token2 = M::generarToken( $tipo, $valor['uid'], $valor['expira'] );
						if ( $token == $token2 && $valor['uid'] != '0' ) {
							$resultado = $valor['uid'];
							$estado = 1;
						}
					 }
				}
			}
		}
		return array(
			'estado' => $estado,
			'resultado' => $resultado
		);
	}

	public function autorizarAcceso( $tipo = 'sesion', $canal = '*' ) {
		$autorizado = '';
		$token = $this->revisarCredencial( $canal );
		$comprobacion = $this->comprobarToken( $token, $tipo );
		if ( $comprobacion['estado'] == 1 ) {
			M::$entorno['M_USUARIO'] = $comprobacion['resultado'];
			M::$entorno['M'] = $token;
		} else {
			switch ( $comprobacion['resultado'] ) {
				case 'vacio': 
					$this->cambiarEstado( $this->estados['401_UNAUTHORIZED'], dgettext('me', 'Acceso-denegado-no-tiene-llave') );
					break;
				case 'expirado': 
					$this->cambiarEstado( $this->estados['401_UNAUTHORIZED'], dgettext('me', 'Acceso-denegado-llave-expirada') );
					break;
				case 'no-valido': 
				default:
					$this->cambiarEstado( $this->estados['400_BADREQUEST'], dgettext('me', 'Acceso-denegado-llave-no-valida') );
					break;
			}
			$this->campos = null;
			$this->parametros = null;
			if ( $tipo == 'sesion' && M::$entorno['M_SALIDA'] == 'HTML' && isset($_COOKIE) ) {
				$url = M::E('SOLICITUD/URL');
				if ( $url == M::E('M_PUNTOFINAL') . '/' ) { $url = ''; }
				$parametros = ( strlen($url)>0 ? '?M_URL=' . $url : '' );
				$this->Redirigir( M::E('M_PUNTOFINAL') . '/login.html' . $parametros );
			} else {
				$this->enviarRespuesta();
			}
			exit();
		}
	}

	public function verificarRequisitos( $metodos = array(), $entradas = array() ) {
		$verificado = true;
		if ( is_array($metodos) && count($metodos)>0 ) {
			if ( !in_array( M::$entorno['SOLICITUD']['METODO'], $metodos ) ) { $verificado = false; }
		}
		if ( is_array($entradas) && count($entradas)>0 ) {
			if ( !in_array( M::$entorno['M_ENTRADA'], $entradas ) ) { $verificado = false; }
		}
		if ( !$verificado ) {
			M::$entorno['M_SALIDA'] = 'JSON';
			$this->enviarError( '405_NOTALLOWED' );
		}
	}

	public function Salir( $enviar = false ) {
		$this->campos = null;
		$this->parametros = null;
		if ( $enviar ) {
			$this->enviarRespuesta();
		}
		exit();
	}

	public function enviarError( $tipo = '', $mensaje = '' ) {
		if ( isset($this->estados[$tipo]) ) {
			$datos = $this->estados[$tipo];
		} else {
			$datos = $this->estados['500_INTERNALERROR'];
		}
		$this->cambiarEstado( $datos, $mensaje );
		$this->enviarRespuesta();
		exit();
	}

	private function _V( &$objeto, $clave ) {
		$valor = '';
		if ( isset($objeto[$clave]) ) { $valor = $objeto[$clave]; }
		return $valor;
	}

	private function _elegirIdioma( $disponibles ) {
		$info = '';
		$aux = $this->_V( $this->campos, 'M_IDIOMA' );
		if ( $aux == '' ) {
			$aux = $this->_V( $this->parametros, 'M_IDIOMA' );
		}
		if ( $aux != '' ) {
			$info = $aux;
		} else if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
			$info = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		$preferidos = explode( ',', $info );
		array_walk($preferidos, function (&$aux) { $aux = strtr(strtok($aux, ';'), array('-' => '_')); });
		$comunes = array_intersect( $preferidos, $disponibles );
		if ( count($comunes)>0 ) {
			foreach ( $comunes as $indice => $valor ) {
				$elegido = $valor;
				break;
			}
		} else {
			$elegido = $disponibles[0];
		}
		return $elegido;
	}

	private function _ordenarArchivos( &$file ) {
		$file_ord = array();
		$file_count = 1;
		if ( is_array($file['name']) ) {
			$file_count = count($file['name']);
		}
		$file_keys = array_keys($file);
		if ( $file_count > 1 ) {
			for ($i = 0; $i < $file_count; $i++) {
				foreach ( $file_keys as $key ) {
					$file_ord[$i][$key] = $file[$key][$i];
				}
			}
		} else {
			foreach ( $file_keys as $key ) {
				$file_ord[0][$key] = $file[$key];
			}
		}
		return $file_ord;
	}

	private function _analizarUrl( $comando, $accion = '' ) {
		$atributos = array();
		$atributos['SOLICITUD']['COMANDO'] = $comando;
		$atributos['SOLICITUD']['ACCION'] = $accion;
		$atributos['SOLICITUD']['OPERACION'] = '';
		$atributos['M_SALIDA'] = '';
		$atributos['RECURSO']['COLECCION'] = '';
		$atributos['RECURSO']['ELEMENTO'] = '';
		$atributos['ANTECESOR']['COLECCION'] = '';
		$atributos['ANTECESOR']['ELEMENTO'] = '';
		$ruta = explode( '/', $comando );
		switch ( count($ruta) ) {
			case 1: 
				$aux = explode( '.', $ruta[0] );
				if ( count($aux) == 2 ) { $atributos['M_SALIDA'] = $aux[1]; }
				$aux = explode( '-', $aux[0] );
				if ( count($aux) == 2 ) { $atributos['SOLICITUD']['ACCION'] = $aux[1]; }
				$atributos['RECURSO']['COLECCION'] = $aux[0];
				$atributos['RECURSO']['ELEMENTO'] = '';
				$atributos['ANTECESOR']['COLECCION'] = '';
				$atributos['ANTECESOR']['ELEMENTO'] = '';
				if ( strlen($comando)==0 && strlen($atributos['RECURSO']['COLECCION'])==0 ) {
					$atributos['SOLICITUD']['OPERACION'] = 'BASE-' . strtoupper($atributos['SOLICITUD']['ACCION']);
				} else {
					$atributos['SOLICITUD']['OPERACION'] = strtoupper($atributos['RECURSO']['COLECCION']) . '-' . strtoupper($atributos['SOLICITUD']['ACCION']);
				}
				break;
			case 2: 
				$aux = explode( '.', $ruta[1] );
				if ( count($aux) == 2 ) { $atributos['M_SALIDA'] = $aux[1]; }
				$aux = explode( '-', $aux[0] );
				if ( count($aux) == 2 ) { $atributos['SOLICITUD']['ACCION'] = $aux[1]; }
				$atributos['RECURSO']['COLECCION'] = $ruta[0];
				$atributos['RECURSO']['ELEMENTO'] = $aux[0];
				$atributos['ANTECESOR']['COLECCION'] = '';
				$atributos['ANTECESOR']['ELEMENTO'] = '';
				$atributos['SOLICITUD']['OPERACION'] = strtoupper($atributos['RECURSO']['COLECCION']) . '/ID-' . strtoupper($atributos['SOLICITUD']['ACCION']);
				break;
			case 3: 
				$aux = explode( '.', $ruta[2] );
				if ( count($aux) == 2 ) { $atributos['M_SALIDA'] = $aux[1]; }
				$aux = explode( '-', $aux[0] );
				if ( count($aux) == 2 ) { $atributos['SOLICITUD']['ACCION'] = $aux[1]; }
				$atributos['RECURSO']['COLECCION'] = $aux[0];
				$atributos['RECURSO']['ELEMENTO'] = '';
				$atributos['ANTECESOR']['COLECCION'] = $ruta[0];
				$atributos['ANTECESOR']['ELEMENTO'] = $ruta[1];
				$atributos['SOLICITUD']['OPERACION'] = strtoupper($atributos['ANTECESOR']['COLECCION']) . '/ID/' . strtoupper($atributos['RECURSO']['COLECCION']) . '-' . strtoupper($atributos['SOLICITUD']['ACCION']);
				break;
			case 4: 
				$aux = explode( '.', $ruta[3] );
				if ( count($aux) == 2 ) { $atributos['M_SALIDA'] = $aux[1]; }
				$aux = explode( '-', $aux[0] );
				if ( count($aux) == 2 ) { $atributos['SOLICITUD']['ACCION'] = $aux[1]; }
				$atributos['RECURSO']['COLECCION'] = $ruta[2];
				$atributos['RECURSO']['ELEMENTO'] = $aux[0];
				$atributos['ANTECESOR']['COLECCION'] = $ruta[0];
				$atributos['ANTECESOR']['ELEMENTO'] = $ruta[1];
				$atributos['SOLICITUD']['OPERACION'] = strtoupper($atributos['ANTECESOR']['COLECCION']) . '/ID/' . strtoupper($atributos['RECURSO']['COLECCION']) . '/ID-' . strtoupper($atributos['SOLICITUD']['ACCION']);
				break;
			default:
				$atributos = array();
				break;
		}
		return $atributos;
	}

	private function _evaluarTipoContenido() {
		$tipo = $this->_V($_SERVER, 'CONTENT_TYPE');
		$acepta = $this->_V($_SERVER, 'HTTP_ACCEPT');
		if ( 
			substr_count ( $tipo, 'application/json' )>0 || 
			( substr_count( $tipo, 'x-www-form-urlencoded' )>0 && substr_count( $acepta, 'application/json' )>0 )
			) {
			M::$entorno['M_ENTRADA'] = 'JSON';
			M::$entorno['M_SALIDA'] = 'JSON';
		} elseif ( 
			substr_count( $tipo, 'multipart/form-data' )>0 ) {
			M::$entorno['M_ENTRADA'] = 'MULTIPART';
			M::$entorno['M_SALIDA'] = 'HTML';
		} else if ( 
			substr_count ( $tipo, 'application/xml' )>0 || 
			( $tipo=='' && $acepta=='application/xml' )
			) {
			M::$entorno['M_ENTRADA'] = 'XML';
			M::$entorno['M_SALIDA'] = 'XML';
		} elseif ( 
			substr_count( $tipo, 'x-www-form-urlencoded' )>0 ) {
			M::$entorno['M_ENTRADA'] = 'HTML';
			M::$entorno['M_SALIDA'] = 'HTML';
		} else {
			M::$entorno['M_ENTRADA'] = 'HTML';
			M::$entorno['M_SALIDA'] = 'HTML';
		}
		return M::$entorno['M_ENTRADA'];
	}
}

<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Finales\Dto;
use MasExperto\ME\Interfaces\IControl;
use MasExperto\ME\M;

abstract class Control implements IControl
{
	protected $CONTEXTO = null;
	protected $DTO = null;
	protected $operacion = '';
	protected $esquema = null;

	function __construct() {}
	function __destruct() {
		$this->DTO = null;
		$this->CONTEXTO = null;
		unset($this->DTO);
		unset($this->CONTEXTO);
		unset($this->esquema);
	}

	public function ejecutarOperacion() {}

	public function inyectarContexto( &$ruteador ) {
		$this->CONTEXTO = &$ruteador;
		$this->DTO = new Dto();
		$this->DTO->campos = &$this->CONTEXTO->campos;
		$this->DTO->parametros = &$this->CONTEXTO->parametros;
		$this->operacion = M::E('SOLICITUD/OPERACION');
		return;
	}

	public function comprobarPermisos( $roles, $esquema = '', $operacion = '' ) {
		$comprobacion = true;
		$autorizados = '';
		if ( strlen($roles)==0 ) { $comprobacion = false; }
		if ( strlen($esquema)==0 ) { $esquema = 'Permisos.xml'; }
		if ( strlen($operacion)==0 ) { $operacion = $this->operacion; }
		if ( $comprobacion ) {
			$credenciales = explode( ',', trim($roles, ',') );
			if ( in_array( 'Super', $credenciales ) ) { $comprobacion = true; }
			else {
				$comprobacion = false;
				if ( !is_object( $this->esquema ) ) {
					$doc = M::E('ALMACEN/PRIVADO') . '/' . $esquema;
					if ( !file_exists( $doc ) || is_dir( $doc ) ) {
						$doc = M::E('RUTA/BACKEND') . '/' . $esquema;
					}
					if ( file_exists( $doc ) && !is_dir( $doc ) ) {
						$this->esquema = simplexml_load_file( $doc );
					}
				}
				if ( is_object( $this->esquema ) ) {
					$nodo = $this->esquema->xpath( "//permisos[@operacion='" . $operacion . "']/@roles" );
					if ( isset($nodo[0]) ) {
						$autorizados = $nodo[0];
					}
					unset( $nodo );
				}
				if ( strlen($autorizados)>0 ) {
					while ( substr_count($autorizados, ',,')>0 ) { $autorizados = str_replace(',,', ',', $autorizados); }
					while ( substr_count($roles, ',,')>0 ) { $roles = str_replace(',,', ',', $roles); }
					$requisitos = explode( ',', trim( $autorizados, ',') );
					$comunes = array_intersect( $requisitos, $credenciales );
					if ( $autorizados == '*' || count($comunes) > 0 ) {
						$comprobacion = true;
					}
				}
			}
			$instancia = M::E('USUARIO/instancia');
			if ( strlen($instancia)>0 && $instancia != M::E('M_INSTANCIA') ) {
				$comprobacion = false;
			}
		}
		if ( !$comprobacion ) {
			if ( M::E('M_SALIDA') == 'HTML' && strlen(M::E('URL/LOGIN'))>0 ) {
				$this->CONTEXTO->Redirigir( M::E('URL/LOGIN') );
			} else {
				$this->CONTEXTO->enviarError( '401_UNAUTHORIZED' );
			}
		}
		return;
	}

	public function guardarPerfil( $id, $datos, $etiqueta = '' ) {
		if ( is_numeric($id) && is_array($datos) ) {
			$ruta = M::E('ALMACEN/PRIVADO') . '/usuarios';
			$archivo = 'u_' . $id . '.json';
			if ( !is_dir( $ruta ) ) {
				@mkdir( $ruta );
				chmod( $ruta, 0755 );
			}
			if ( file_exists( "$ruta/$archivo" ) ) {
				$temp = file_get_contents( "$ruta/$archivo" );
				if ( strlen($temp)>0 ) {
					M::$entorno['USUARIO'] = @json_decode( trim( $temp ), true );
				}
			}
			if ( strlen($etiqueta)==0 ) {
				foreach ( $datos as $campo => $valor ) {
					M::$entorno['USUARIO'][$campo] = $valor;
				}
			} else {
				M::$entorno['USUARIO'][$etiqueta] = $datos;
			}
			$temp = json_encode( M::$entorno['USUARIO'] );
			if ( strlen($temp)>0 ) {
				if ( file_put_contents( "$ruta/$archivo", $temp )>0 ) {
					return true;
				}
			}
			return false;
		}
	}

	public function cargarPerfil( $id = '' ) {
		$roles = '';
		if ( strlen($id)==0 ) { $id = M::E('M_USUARIO'); }
		if ( is_numeric($id) ) {
			$ruta = M::E('ALMACEN/PRIVADO') . '/usuarios';
			$archivo = 'u_' . $id . '.json';
			if ( file_exists( "$ruta/$archivo" ) ) {
				$temp = file_get_contents( "$ruta/$archivo" );
				if ( strlen($temp)>0 ) {
					M::$entorno['USUARIO'] = @json_decode( trim( $temp ), true );
				}
				$roles = ( isset(M::$entorno['USUARIO']['roles']) ? M::$entorno['USUARIO']['roles'] : '' );
			}
		}
		return $roles;
	}
}

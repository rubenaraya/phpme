<?php
namespace MasExperto\ME;

use DateInterval;
use DateTime;

final class M
{
	public static $entorno = array();

	public static function E( $nombre ) {
		$valor = '';
		if ( substr_count($nombre, '/' )>0 ) {
			$aux = explode('/', $nombre);
			if ( isset(M::$entorno[$aux[0]][$aux[1]]) ) { $valor = M::$entorno[$aux[0]][$aux[1]]; }
		} else {
			if ( strlen($nombre)>0 ) {
				if ( isset(M::$entorno[$nombre]) ) { $valor = M::$entorno[$nombre]; }
			} else {
				$valor = M::$entorno;
			}
		}
		return $valor;
	}

	public static function generarToken( $tipo, $uid, $expira = '' ) {
		//Adaptado de: https://rbrt.wllr.info/2018/01/29/how-create-json-web-token-php.html
		if ( $tipo == 'sesion' ) {
			if ( $uid == 0 ) {
				$fecha = new DateTime();
				$fecha->sub( new DateInterval( 'P1D' ) );
				$expira = $fecha->format('YmdHi');
			} else if ( strlen($expira)==0 ) {
				if ( !isset(M::$entorno['M_SESION']) ) { M::$entorno['M_SESION'] = '24H'; }
				$fecha = new DateTime(date('Y-m-d H:i'));
				$fecha->add(new DateInterval('PT' . M::$entorno['M_SESION'] ));
				$expira = $fecha->format('YmdHi');
			}
		} else if ( strlen($expira)==0 ) {
			$fecha = new DateTime(date('Y-m-d H:i'));
			$fecha->add(new DateInterval('P7D' ));
			$expira = $fecha->format('YmdHi');
		}
		$header = json_encode( array('typ' => 'JWT', 'alg' => 'HS256') );
		$payload = json_encode( array('uid' => $uid, 'expira' => $expira, 'tipo' => $tipo) );
		$base64UrlHeader = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($header));
		$base64UrlPayload = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($payload));
		$llave = ( $tipo == 'sesion' ? M::E('LLAVE/TOKEN') : M::E('LLAVE/ACCESO') );
		$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $llave, true);
		$base64UrlSignature = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($signature));
		return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
	}

	public static function adquirirDatosMatriz( $matriz, $clave ) { 
		$resultado = array(); 
		if ( is_array($matriz) ) { 
			if ( isset($matriz[$clave] ) && is_array( $matriz[$clave] ) ) { $resultado[] = $matriz; }
			foreach ( $matriz as $elemento ) { $resultado = array_merge( $resultado, M::adquirirDatosMatriz( $elemento, $clave ) ); }
		}
		return $resultado; 
	}

	public static function convertirMatrizHtml( $contenido, $opciones = array() ) {
		if ( !is_array($contenido) || count($contenido)==0 ) { return ''; }
		$encabezado = ( isset($opciones['encabezado']) ? $opciones['encabezado'] : 'S' );
		$aux = M::adquirirDatosMatriz( $contenido, '0' );
		if ( is_array($aux) && count($aux)>0 ) {
			$contenido = $aux[0];
		} else {
			$contenido = '';
		}
		$html = '<table>';
		if ( $encabezado == 'S' ) {
			$html .= '<tr>';
			if ( isset($contenido[0]) ) {
				foreach( $contenido[0] as $key => $value ) {
					$html .= '<th>' . htmlspecialchars( $key ) . '</th>';
				}
			}
			$html .= '</tr>';
		}
		if ( is_array($contenido) ) {
			foreach( $contenido as $key => $value ) {
				if ( is_array($value) ) {
					$html .= '<tr>';
					foreach( $value as $key2 => $value2 ) {
						$html .= '<td>' . htmlspecialchars( $value2 ) . '</td>';
					}
					$html .= '</tr>';
				}
			}
		}
		$html .= '</table>';
		return $html;
	}

	public static function quitarAcentosTexto( $texto ) {
		$reemp = array('a','e','i','o','u','A','E','I','O','U','a','e','i','o','u','A','E','I','O','U');
		$busca = array('á','é','í','ó','ú','Á','É','Í','Ó','Ú','à','è','ì','ò','ù','À','È','Ì','Ò','Ù');
		$texto = str_replace( $busca, $reemp, $texto );
		$busca = array('ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü','â','ê','î','ô','û','Â','Ê','Î','Ô','Û');
		$texto = str_replace( $busca, $reemp, $texto );
		$busca = array('ã','õ','Ã','Õ');
		$reemp = array('a','o','A','O');
		$texto = str_replace( $busca, $reemp, $texto );
		return $texto;
	}

	public static function reemplazarEtiquetas( $texto ) {
		$fecha = new DateTime();
		$texto = str_replace('{{hoy_dma}}', $fecha->format('d-m-Y'), $texto );
		$texto = str_replace('{{hoy_amd}}', $fecha->format('Y/m/d'), $texto );
		$texto = str_replace('{{hoy_mda}}', $fecha->format('m-d-Y'), $texto );
		$texto = str_replace('{{hoy_md}}', $fecha->format('m-d'), $texto );
		$texto = str_replace('{{hoy_hora}}', $fecha->format('H:i'), $texto );
		$texto = str_replace('{{hoy_mes}}', $fecha->format('m'), $texto );
		$texto = str_replace('{{hoy_dia}}', $fecha->format('d'), $texto );
		$texto = str_replace('{{hoy_ano}}', $fecha->format('Y'), $texto );
		$texto = str_replace('{{hoy_per}}', $fecha->format('Ym'), $texto );
		$texto = str_replace('{{m_operacion}}', M::E('SOLICITUD/OPERACION'), $texto );
		$texto = str_replace('{{m_idioma}}', M::E('M_IDIOMA'), $texto );
		$texto = str_replace('{{m_usuario}}', M::E('M_USUARIO'), $texto );
		$texto = str_replace('{{m_servidor}}', M::E('M_SERVIDOR'), $texto );
		$texto = str_replace('{{m_puntofinal}}', M::E('M_PUNTOFINAL'), $texto );
		$texto = str_replace('{{m_frontend}}', M::E('M_FRONTEND'), $texto );
		$texto = str_replace('{{m_instancia}}', M::E('M_INSTANCIA'), $texto );
        $texto = str_replace('{{idantecesor}}', M::E('ANTECESOR/ELEMENTO'), $texto );
        $texto = str_replace('{{idrecurso}}', M::E('RECURSO/ELEMENTO'), $texto );
		$texto = str_replace('{{app_titulo}}', M::E('APP_TITULO'), $texto );
		$texto = str_replace('{{app_id}}', M::E('APP_ID'), $texto );
		if ( isset(M::$entorno['USUARIO']) ) {
			foreach ( M::$entorno['USUARIO'] as $clave => $valor ) {
				$texto = str_replace('{{usu_' . $clave . '}}', $valor, $texto );
			}
		}
		return $texto;
	}

	public static function aplicarGenero( $texto, $genero = 'I' ) {
		if ( strlen( $genero )==0 ) { $genero = 'I'; }
		$reglas['M'] = array(
			"del/la"=>"del", 
			"El/la"=>"El",
			"el/la"=>"el", 
			"or/a"=>"or", 
			"ado/a"=>"ado", 
			"al/la"=>"al", 
			"Don(a)"=>"Don", 
			"o/a"=>"o", 
			"él/ella"=>"él", 
			"ello/ella"=>"ello", 
			"ellos/as"=>"ellos", 
			"en/a"=>"en",
			"es/as"=>"es",
			"os/as"=>"os",
			"un/a"=>"un",
			"los/as"=>"los", 
			"e/a"=>"e" 
		);
		$reglas['F'] = array(
			"del/la"=>"de la", 
			"El/la"=>"La", 
			"el/la"=>"la", 
			"or/a"=>"ora", 
			"ado/a"=>"ada", 
			"al/la"=>"a la", 
			"Don(a)"=>"Doña", 
			"o/a"=>"a", 
			"él/ella"=>"ella", 
			"ello/ella"=>"ella", 
			"ellos/as"=>"ellas", 
			"en/a"=>"ena",
			"os/as"=>"as",
			"es/as"=>"as",
			"un/a"=>"una",
			"los/as"=>"las", 
			"e/a"=>"a" 
		);
		$reglas['I'] = array(
			"del/la"=>"de le", 
			"El/la"=>"Le", 
			"el/la"=>"le", 
			"or/a"=>"er", 
			"ado/a"=>"ade", 
			"al/la"=>"a le", 
			"Don(a)"=>"Done", 
			"o/a"=>"e", 
			"él/ella"=>"elle", 
			"ello/ella"=>"elle", 
			"ellos/as"=>"elles", 
			"en/a"=>"ene",
			"os/as"=>"es",
			"es/as"=>"es",
			"un/a"=>"une",
			"los/as"=>"les", 
			"e/a"=>"e" 
		);
		$reglas['X'] = array(
			"del/la"=>"de lx", 
			"El/la"=>"Lx",
			"el/la"=>"lx", 
			"or/a"=>"xr", 
			"ado/a"=>"adx", 
			"al/la"=>"a lx", 
			"Don(a)"=>"Donx", 
			"o/a"=>"x", 
			"él/ella"=>"ellx", 
			"ello/ella"=>"ellx", 
			"ellos/as"=>"ellxs", 
			"en/a"=>"enx",
			"es/as"=>"xs",
			"os/as"=>"xs",
			"un/a"=>"unx",
			"los/as"=>"lxs", 
			"e/a"=>"x" 
		);
		if ( in_array( $genero, array('F','M','I','X') ) ) {
			foreach ( $reglas[$genero] as $antes => $despues ) {
				$texto = str_replace( $antes, $despues, $texto );
				$texto = str_replace( strtoupper($antes), strtoupper($despues), $texto );
			}
		}
		return $texto;
	}

	public static function numeroEnPalabras( $n, $m = '' ) {
		$p = '';
		$v = '';
		$y = strval($n);
		if ( substr($y, 0, 1)=='-' ) {
			$y = str_replace('-', '', $y);
			$p = 'menos ';
		}
		$r = $p;
		if ( $p=='menos ' ) { $v = '-'; }
		$x = substr('000000000000'.$y, -12);
		$u = substr($x, 11, 1);
		$d = substr($x, 10, 1);
		$c = substr($x, 9, 1);
		$um = substr($x, 8, 1);
		$dm = substr($x, 7, 1);
		$cm = substr($x, 6, 1);
		$ul = substr($x, 5, 1);
		$dl = substr($x, 4, 1);
		$cl = substr($x, 3, 1);
		$uml = substr($x, 2, 1);
		$dml = substr($x, 1, 1);
		$cml = substr($x, 0, 1);

		if ( $cml!='0' ) { //centena de mil millón
			$r = $r.M::digitoPalabra( $cml, $dml, $uml, 'c' );
			$v = $v.$cml;
		}
		if ( $cml!='0' || $dml!='0' ) { //decena de mil millón
			$r = $r.M::digitoPalabra( $dml, $uml, '', 'd' );
			$v = $v.$dml;
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' ) { //unidad de mil millón
			$r = $r.M::digitoPalabra( $uml, $dml, '', 'u' ).'mil ';
			$v = $v.$uml.'.';
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' || $cl!='0' ) { //centena de millón
			$r = $r.M::digitoPalabra( $cl, $dl, $ul, 'c' );
			$v = $v.$cm;
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' || $cl!='0' || $dl!='0' ) { //decena de millón
			$r = $r.M::digitoPalabra( $dl, $ul, '', 'd' );
			$v = $v.$dl;
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' || $cl!='0' || $dl!='0' || $ul!='0' ) { //unidad de millón
			if ( $cml=='0' && $dml=='0' && $uml=='0' && $cl=='0' && $dl=='0' && $ul=='1') {
				$r = $r.'un millón ';
			} else {
				$r = $r.M::digitoPalabra( $ul, $dl, '', 'u' ).' millones ';
			}
			$v = $v.$ul.'.';
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' || $cl!='0' || $dl!='0' || $ul!='0' || $cm!='0' ) { //centena de mil
			$r = $r.M::digitoPalabra( $cm, $dm, $um, 'c' );
			$v = $v.$cm;
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' || $cl!='0' || $dl!='0' || $ul!='0' || $cm!='0' || $dm!='0' ) { //decena de mil
			$r = $r.M::digitoPalabra( $dm, $um, '', 'd' );
			$v = $v.$dm;
		}
		if ( $cml!='0' || $dml!='0' || $uml!='0' || $cl!='0' || $dl!='0' || $ul!='0' || $cm!='0' || $dm!='0' || $um!='0' ) { //unidad de mil
			$r = $r.M::digitoPalabra( $um, $dm, '', 'u' ).'mil ';
			$v = $v.$um.'.';
		}
		$r = $r . M::digitoPalabra( $c, $d, $u, 'c' ) . M::digitoPalabra( $d, $u, '', 'd' ) . M::digitoPalabra( $u, $d, '', 'u' ) . ' ' . $m;
		if ( substr( $r, 0, 7 )=='un mil ' ) {
			$r = substr( $r, 3 );
		}
		$r = str_replace( ' millón un mil ', ' millón mil ', $r );
		$r = str_replace( ' millones un mil ', ' millones mil ', $r );
		return $r;
	}

	public static function Trazar( $texto, $archivo = '', $ubicacion = '' ) {
		if ( strlen($archivo)==0 ) { $archivo = 'trazado'; }
		if ( strlen($ubicacion)==0 ) { $ubicacion = M::$entorno['RUTA']['RAIZ']; }
		$ruta = $ubicacion . '/' . $archivo . '.txt';
		if ( $f = fopen( $ruta, 'a' ) ) {
			if ( is_array($texto) ) { $texto = print_r( $texto, true ); }
			fwrite( $f, $texto . chr(10) );
			fclose( $f );
		}
		unset($f);
	}

	public static function cargarClase( $clase ) {
		$archivo = $clase . '.php';
		$namespace = explode( '\\' , $clase );
		if ( $namespace[0] == 'MasExperto' ) {
			$nombre = str_replace( array('MasExperto\\', $namespace[1].'\\'), '', $clase );
			$nombre = str_replace( '\\', '/', $nombre );
			if ( count($namespace)>1 ) {
				switch ( $namespace[1] ) {
					case 'Servicio':
						$archivo = M::E('RUTA/BACKEND') . '/servicios/' .  $nombre . '.php';
						break;
					case 'Adaptador':
						$archivo = M::E('RUTA/BACKEND') . '/adaptadores/' . $nombre . '.php';
						break;
                    case 'Componente':
                        $archivo = M::E('RUTA/BACKEND') . '/componentes/' . $nombre . '/' . $nombre . '.php';
                        break;
                    case 'Extension':
                        $archivo = __DIR__ . '/Extensiones/' . $nombre . '/' . $nombre . '.php';
                        break;
				}
			}
		}
		if ( is_readable($archivo) ) {
			require( $archivo );
			return true;
		} else {
			return false;
		}
	}

	private static function digitoPalabra( $n, $q, $z, $p ) {
		$r = '';
		switch( $p ) {
			case 'c':
				switch( $n ) {
					case '0': $r = ''; break;
					case '1': if ( $q=='0' && $z=='0' ) { $r = 'cien '; } else { $r = 'ciento '; } break;
					case '2': $r = 'doscientos '; break;
					case '3': $r = 'trescientos '; break;
					case '4': $r = 'cuatrocientos '; break;
					case '5': $r = 'quinientos '; break;
					case '6': $r = 'seiscientos '; break;
					case '7': $r = 'setecientos '; break;
					case '8': $r = 'ochocientos '; break;
					case '9': $r = 'novecientos '; break;
				}
				break;
			case 'd':
				switch( $n ) {
					case '0': $r = ''; break;
					case '1':
						switch( $q ) {
							case '0': $r = 'diez '; break;
							case '1': $r = 'once '; break;
							case '2': $r = 'doce '; break;
							case '3': $r = 'trece '; break;
							case '4': $r = 'catorce '; break;
							case '5': $r = 'quince '; break;
							case '6': $r = 'dieciseis '; break;
							case '7': $r = 'diecisiete '; break;
							case '8': $r = 'dieciocho '; break;
							case '9': $r = 'diecinueve '; break;
						}
						break;
					case '2': if ( $q=='0' ) { $r = 'veinte '; } else { $r = 'veinti'; } break;
					case '3': if ( $q=='0' ) { $r = 'treinta '; } else { $r = 'treinta y '; } break;
					case '4': if ( $q=='0' ) { $r = 'cuarenta '; } else { $r = 'cuarenta y '; } break;
					case '5': if ( $q=='0' ) { $r = 'cincuenta '; } else { $r = 'cincuenta y '; } break;
					case '6': if ( $q=='0' ) { $r = 'sesenta '; } else { $r = 'sesenta y '; } break;
					case '7': if ( $q=='0' ) { $r = 'setenta '; } else { $r = 'setenta y '; } break;
					case '8': if ( $q=='0' ) { $r = 'ochenta '; } else { $r = 'ochenta y '; } break;
					case '9': if ( $q=='0' ) { $r = 'noventa '; } else { $r = 'noventa y '; } break;
				}
				break;
			case 'u':
				if ( $q != '1' ) {
					switch( $n ) {
						case '0': $r = ''; break;
						case '1': $r = 'un '; break;
						case '2': $r = 'dos '; break;
						case '3': $r = 'tres '; break;
						case '4': $r = 'cuatro '; break;
						case '5': $r = 'cinco '; break;
						case '6': $r = 'seis '; break;
						case '7': $r = 'siete '; break;
						case '8': $r = 'ocho '; break;
						case '9': $r = 'nueve '; break;
					}
				}
				break;
		}
		return $r;
	}
}

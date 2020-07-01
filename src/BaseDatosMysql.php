<?php
namespace MasExperto\ME;
use DateInterval;
use DateTime;

final class BaseDatosMysql extends BaseDatos {

	//METODOS PUBLICOS

	/** 
		* @param			
		* @return		*/
	public function Conectar( $credenciales, &$dto ) {
		$this->DTO = &$dto;
		$this->credenciales = $credenciales;
		if ( $this->conexion ) {
			if ( $this->credenciales['DBNAME'] != $credenciales['DBNAME'] ) {
				$this->Cerrar();
			}
		}
		if ( !$this->conexion ) {
			$this->conexion = mysqli_connect( 
				$this->credenciales['DBHOST'], 
				$this->credenciales['USER'], 
				$this->credenciales['PASSW'], 
				$this->credenciales['DBNAME']
			);
			if ( $this->conexion ) {
				$this->conexion->set_charset('utf8');
				$now = new DateTime();
				$mins = $now->getOffset() / 60;
				$sgn = ( $mins < 0 ? -1 : 1 );
				$mins = abs($mins);
				$hrs = floor($mins / 60);
				$mins -= $hrs * 60;
				$timezone = sprintf( '%+d:%02d', $hrs * $sgn, $mins );
				$this->conexion->query( "SET time_zone='" . $timezone . "'" );
				return true;
			} else {
				return false;
			}
		}
		if ( $this->conexion ) { return true; }
		else { return false; }
	}

	/** 
		* @param			
		* @return		*/
	public function Cerrar() {
		if ( !$this->conexion ) { return; }
		mysqli_close( $this->conexion );
		unset( $this->conexion );
		return;
	}

	/** 
		* @param			
		* @return		*/
	public function consultarColeccion( $instruccion, $etiqueta, $envolver = true, $nivel = null ) {
		$total = 0;
		$paginas = 0;
		$primero = 0;
		$ultimo = 0;
		$estado = false;
		$leyenda = '';
		$nav = intval($this->DTO->get('M_NAV', 'parametro', 1));
		$max = intval($this->DTO->get('M_MAX', 'parametro', 25));
		if ( $max <= 0 ) { $max = 50; }
		if ( $nav <= 0 ) { $nav = 1; }
		if ( $this->conexion ) {
			$resultado = mysqli_query( $this->conexion, $instruccion );
			if ( $resultado ) {
				$estado = true;
				$total = mysqli_num_rows( $resultado );
				$paginas = ceil( $total / $max );
				$primero = ( ($nav - 1) * $max ) + 1;
				$ultimo = $primero + ($max - 1);
				if ( $ultimo > $total ) { $ultimo = $total; }
				if ( $primero > $ultimo ) { $primero = $ultimo; }
				if ( $total > intval($max) ) {
					if ( substr($instruccion, -1)==';' ) { $instruccion = substr( $instruccion, 0, strlen($instruccion)-1 ); }
					if ( substr_count( $instruccion, ' LIMIT ' )==0 ) {
						$instruccion = $instruccion . ' LIMIT ' . ($primero - 1) . ', ' . $max . ';';
					}
					$resultado = mysqli_query( $this->conexion, $instruccion );
				}
				if ( $resultado ) {
					if ( is_array($nivel) ) {
						$caso = array();
						$niveles = count($nivel);
						switch ( $niveles ) {
							case 5:
								if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion'][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]][$nivel[4]]; } 
								else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]][$nivel[4]]; }
								break;
							case 4:
								if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion'][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]]; } 
								else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]]; }
								break;
							case 3: 
								if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion'][$nivel[0]][$nivel[1]][$nivel[2]]; } 
								else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]][$nivel[2]]; }
								break;
							case 2: 
								if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion'][$nivel[0]][$nivel[1]]; } 
								else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]]; }
								break;
							case 1: 
								if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion'][$nivel[0]]; } 
								else { $caso = &$this->DTO->resultados[$etiqueta]['coleccion'][$nivel[0]]; }
								break;
							default:
								if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion']; } 
								else { $caso = &$this->DTO->resultados[$etiqueta]; }
								break;
						}
						while ( $fila = $resultado->fetch_array(MYSQLI_ASSOC) ) { 
							$caso[] = $fila;
						}
					} else {
						if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['coleccion']; }
						else { $caso = &$this->DTO->resultados[$etiqueta]; }
						while ( $fila = $resultado->fetch_array(MYSQLI_ASSOC) ) { 
							$caso[] = $fila;
						}
					}
					mysqli_free_result( $resultado );
					unset( $fila, $resultado );
					if ( $total > 0 ) {
						$leyenda = $this->DTO->get( 'leyenda', 'valor' );
						if ( strlen($leyenda)==0 ) { $leyenda = '(total) (etiqueta): lista del (primero) al (ultimo)';}
					} else {
						$leyenda = $this->DTO->get( 'leyenda2', 'valor' );
						if ( strlen($leyenda)==0 ) { $leyenda = 'No se encontraron (etiqueta)';}
					}
					$leyenda = str_replace( array( '(total)', '(etiqueta)', '(primero)', '(ultimo)' ), array( $total, $etiqueta, $primero, $ultimo ), $leyenda );
					$this->DTO->set( 'leyenda', $leyenda, 'valor' );
					if ( $envolver ) {
						$this->DTO->resultados[$etiqueta]['resumen']['coleccion'] = $etiqueta;
						$this->DTO->resultados[$etiqueta]['resumen']['leyenda'] = $leyenda;
						$this->DTO->resultados[$etiqueta]['resumen']['total'] = $total;
						$this->DTO->resultados[$etiqueta]['resumen']['nav'] = $nav;
						$this->DTO->resultados[$etiqueta]['resumen']['max'] = $max;
						$this->DTO->resultados[$etiqueta]['resumen']['paginas'] = $paginas;
						$this->DTO->resultados[$etiqueta]['resumen']['primero'] = $primero;
						$this->DTO->resultados[$etiqueta]['resumen']['ultimo'] = $ultimo;
						if ( $total > $max ) {
							$this->DTO->resultados[$etiqueta]['paginador'][] = 1;
							$desde = ( $nav - 5 > 1 ? $nav - 5 : 2 );
							$hasta = ( $desde + 5 < $paginas ? $desde + 5 : $paginas );
							for ( $i = $desde; $i <= $hasta; $i++ ) {
								if ( $i < $paginas ) {
									if ( $i > 1 ) {
										$this->DTO->resultados[$etiqueta]['paginador'][] = $i;
									}
								} else {
									break;
								}
							}
							if ( $paginas > 1 ) {
								$this->DTO->resultados[$etiqueta]['paginador'][] = $paginas;
							}
						}
					}
				} else {
					$estado = false;
					trigger_error( 'BaseDatosMysql.consultarColeccion: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
				}
			} else {
				$estado = false;
				trigger_error( 'BaseDatosMysql.consultarColeccion: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
			}
		}
		return array(
			'estado' => $estado,
			'total' => $total,
			'leyenda' => $leyenda,
			'nav' => $nav,
			'max' => $max,
			'paginas' => $paginas,
			'primero' => $primero,
			'ultimo' => $ultimo
		);
	}

	/** 
		* @param			
		* @return		*/
	public function consultarElemento( $instruccion, $etiqueta, $envolver = true, $nivel = null ) {
		$total = 0;
		$estado = false;
		if ( $this->conexion ) {
			$resultado = mysqli_query( $this->conexion, $instruccion );
			if ( $resultado ) {
				$estado = true;
				if ( is_array($nivel) ) {
					$caso = array();
					$niveles = count($nivel);
					switch ( $niveles ) {
						case 5:
							if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento'][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]][$nivel[4]]; }
							else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]][$nivel[4]]; }
							break;
						case 4:
							if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento'][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]]; }
							else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]][$nivel[2]][$nivel[3]]; }
							break;
						case 3: 
							if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento'][$nivel[0]][$nivel[1]][$nivel[2]]; }
							else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]][$nivel[2]]; }
							break;
						case 2: 
							if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento'][$nivel[0]][$nivel[1]]; }
							else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]][$nivel[1]]; }
							break;
						case 1: 
							if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento'][$nivel[0]]; }
							else { $caso = &$this->DTO->resultados[$etiqueta][$nivel[0]]; }
							break;
						default:
							if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento']; }
							else { $caso = &$this->DTO->resultados[$etiqueta]; }
							break;
					}
				} else {
					if ( $envolver ) { $caso = &$this->DTO->resultados[$etiqueta]['elemento']; }
					else { $caso = &$this->DTO->resultados[$etiqueta]; }
				}
				while ( $fila = $resultado->fetch_array(MYSQLI_ASSOC) ) { 
					$caso = $fila;
					$total = 1;
					break;
				}
				mysqli_free_result( $resultado );
				unset( $fila, $resultado );
			} else {
				trigger_error( 'BaseDatosMysql.consultarElemento: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
			}
		}
		return array(
			'estado' => $estado,
			'total' => $total,
			'id' => ( isset($caso['id']) ? $caso['id'] : '' )
		);
	}

	/** 
		* @param			
		* @return		*/
	public function consultarValores( $instruccion, $etiqueta = 'valores' ) {
		$total = 0;
		$lista = array();
		if ( $this->conexion ) {
			$resultado = mysqli_query( $this->conexion, $instruccion );
			if ( $resultado ) {
				$total = mysqli_num_rows( $resultado );
				if ( $total == 1 ) {
					while ( $fila = $resultado->fetch_array(MYSQLI_NUM) ) { 
						$lista = $fila;
					}
				} else if ( $total > 1 ) {
					while ( $fila = $resultado->fetch_array(MYSQLI_NUM) ) { 
						$lista[] = $fila[0];
					}
				}
				$this->DTO->resultados[$etiqueta] = array(implode( ',', $lista ));
				mysqli_free_result( $resultado );
				unset( $fila, $resultado );
			} else {
				trigger_error( 'BaseDatosMysql.consultarValores: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
			}
		}
		return $lista;
	}

	/** 
		* @param			
		* @return		*/
	public function editarElementos( $instruccion, $etiqueta ) {
		$estado = false;
		$total = 0;
		if ( $this->conexion ) {
			if ( mysqli_query( $this->conexion, $instruccion ) ) {
				$estado = true;
				$total = mysqli_affected_rows( $this->conexion );
				$this->DTO->resultados[$etiqueta]['estado'] = $estado;
				$this->DTO->resultados[$etiqueta]['total'] = $total;
			} else {
				trigger_error( 'BaseDatosMysql.editarElementos: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
			}
		}
		return array(
			'estado' => $estado,
			'total' => $total
		);
	}

	/** 
		* @param			
		* @return		*/
	public function borrarElementos( $instruccion, $etiqueta ) {
		$estado = false;
		$total = 0;
		if ( $this->conexion ) {
			if ( mysqli_query( $this->conexion, $instruccion ) ) {
				$estado = true;
				$total = mysqli_affected_rows( $this->conexion );
				$this->DTO->resultados[$etiqueta]['estado'] = $estado;
				$this->DTO->resultados[$etiqueta]['total'] = $total;
			} else {
				trigger_error( 'BaseDatosMysql.borrarElementos: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
			}
		}
		return array(
			'estado' => $estado,
			'total' => $total
		);
	}

	/** 
		* @param			
		* @return		*/
	public function agregarElemento( $instruccion, $etiqueta ) {
		$estado = false;
		$total = 0;
		$uid = null;
		if ( $this->conexion ) {
			if ( mysqli_query( $this->conexion, $instruccion ) ) {
				$estado = true;
				$total = mysqli_affected_rows( $this->conexion );
				$uid = mysqli_insert_id( $this->conexion );
				$this->DTO->resultados[$etiqueta]['estado'] = $estado;
				$this->DTO->resultados[$etiqueta]['total'] = $total;
				$this->DTO->resultados[$etiqueta]['uid'] = $uid;
			} else {
				trigger_error( 'BaseDatosMysql.agregarElemento: ' . mysqli_error($this->conexion) . ' - SQL: ' . $instruccion, E_USER_ERROR );
			}
		}
		return array(
			'estado' => $estado,
			'total' => $total,
			'uid' => $uid
		);
	}

	/** 
		* @param			
		* @return		*/
	public function aplicarFiltro( $filtro, $claves, $sql = '', $tabla = '' ) {
		$exp = '';
		if ( is_array($claves) ) {
			$peticion = $claves[0];
			$campo = $peticion;
			if ( count($claves)==2 ) { $campo = $claves[1]; }
		} else {
			$peticion = strval($claves);
			$campo = $peticion;
		}
		$valor = $this->DTO->get($peticion, 'peticion');
		if ( is_array($valor) ) {
			$valor = implode(',', $valor);
		} else {
			$valor = trim($valor);
		}
		if ( strlen($valor)>0 ) {
			while ( substr_count($valor,'  ')>0 ) { $valor = str_replace('  ', ' ', $valor); }
			$valor = $this->_sanitizarValor( trim($valor) );
			switch ($filtro) {
				case BaseDatos::FILTRO_CONTIENE: 
					$exp = $this->_filtroContiene( $campo, $valor );
					break;
				case BaseDatos::FILTRO_INCLUYE: 
					$exp = $this->_filtroIncluye( $campo, $valor );
					break;
				case BaseDatos::FILTRO_COINCIDE: 
					$exp = $this->_filtroCoincide( $campo, $valor );
					break;
				case BaseDatos::FILTRO_PALABRAS:
					$exp = $this->_filtroPalabras( $campo, $valor );
					break;
				case BaseDatos::FILTRO_TEXTO:
					$exp = $this->_filtroTexto( $campo, $valor );
					break;
				case BaseDatos::FILTRO_NUMERO:
					$exp = $this->_filtroNumero( $campo, $valor );
					break;
				case BaseDatos::FILTRO_PERIODO:
					$exp = $this->_filtroPeriodo( $campo, $valor );
					break;
				case BaseDatos::FILTRO_RANGO_FECHAS:
					$exp = $this->_filtroRangoFechas( $campo, $valor );
					break;
				case BaseDatos::FILTRO_RANGO_HORAS:
					$exp = $this->_filtroRangoHoras( $campo, $valor );
					break;
				case BaseDatos::FILTRO_RANGO_NUMEROS:
					$exp = $this->_filtroRangoNumeros( $campo, $valor );
					break;
				case BaseDatos::FILTRO_LISTA_PALABRAS:
					$exp = $this->_filtroListaPalabras( $campo, $valor );
					break;
				case BaseDatos::FILTRO_LISTA_NUMEROS:
					$exp = $this->_filtroListaNumeros( $campo, $valor );
					break;
				case BaseDatos::FILTRO_RANGO_DIAS:
					$exp = $this->_filtroRangoDias( $campo, $valor );
					break;
			}
			if ( strlen($sql)>0 ) {
				if ( strlen($exp)>0 ) {
					$exp = str_replace( ' WHERE 1 ', ' WHERE 1 AND ' . $exp . ' ', $sql );
				} else {
					$exp = $sql;
				}
				if ( strlen($tabla)>0 && strlen($exp)>0 ) {
					$exp = str_replace( ' estado ', ' ' . $tabla . '.estado ', $exp );
					$exp = str_replace( ' clase ', ' ' . $tabla . '.clase ', $exp );
					$exp = str_replace( ' nombre ', ' ' . $tabla . '.nombre ', $exp );
					$exp = str_replace( ' titulo ', ' ' . $tabla . '.titulo ', $exp );
					$exp = str_replace( ' fecha ', ' ' . $tabla . '.fecha ', $exp );
				}
			}
		} else {
			$exp = $sql;
		}
		return $exp;
	}

	/** 
		* @param			
		* @return		*/
	public function generarExpresion( $tipo, $datos, $tabla, $uid = 'id' ) {
		$exp = '';
		switch ($tipo) {
			case BaseDatos::EXPR_SELECT: 
				if ( is_array($datos) ) {
					$detalle = implode(', ', $datos);
				} else {
					$detalle = '*';
				}
				$exp = 'SELECT ' . $detalle . ' FROM ' . $tabla . ' WHERE 1 ORDER BY ' . $uid . ';';
				break;
			case BaseDatos::EXPR_INSERT: 
				if ( !is_array($datos) ) { return ''; }
				$nombres = array();
				$valores = array();
				foreach ( $datos as $nombre => $valor ) {
					if ( $nombre != $uid ) {
						$nombres[] = $nombre;
						$valores[] = "'" . $this->_sanitizarValor($valor) . "'";
					}
				}
				if ( count($nombres)>0 ) {
					$exp = 'INSERT INTO ' . $tabla . ' ( ' . implode(', ', $nombres) . ' ) VALUES ( ' . implode(', ', $valores) . ' );';
				}
				break;
			case BaseDatos::EXPR_UPDATE: 
				if ( !is_array($datos) ) { return ''; }
				$nombres = array();
				$valores = array();
				$detalle = array();
				foreach ( $datos as $nombre => $valor ) {
					$nombres[] = $nombre;
					$valores[] = "'" . $valor . "'";
					if ( $nombre != $uid ) {
						$detalle[] = $nombre . "='" . $this->_sanitizarValor($valor) . "'";
					}
				}
				if ( count($detalle)>0 ) {
					$exp = 'UPDATE ' . $tabla . ' SET ' . implode(', ', $detalle) . ' WHERE ' . $uid .' = ' . $datos[$uid] . ';';
				}
				break;
			case BaseDatos::EXPR_DELETE: 
				if ( !is_array($datos) ) { return ''; }
				if ( isset($datos[$uid]) ) {
					$exp = 'DELETE FROM ' . $tabla . ' WHERE ' . $uid .' = ' . $this->_sanitizarValor($datos[$uid]) . ';';
				}
				break;
		}
		return $exp;
	}

	/** 
		* @param			
		* @return		*/
	public function reemplazarValores( $expresion, $lista = array() ) {
		$exp = trim($expresion);
		if ( strlen($exp)>0 ) {
			if ( is_array($lista) && count($lista)>0 ) {
				foreach( $lista as $nombre => $valor ) {
					$exp = str_replace('{{'.$nombre.'}}', $this->_sanitizarValor($valor), $exp );
				}
			}
			foreach( $this->DTO->peticion as $nombre => $valor ) {
				if ( !is_array($valor) && strlen($valor)==0 && substr( strtolower($nombre), 0, 5 )=='fecha' ) {
					$exp = str_replace("='{{".$nombre."}}'", '=NULL', $exp );
				}
				$exp = str_replace('{{'.$nombre.'}}', $this->_sanitizarValor($valor), $exp );
			}
			$exp = M::reemplazarEtiquetas( $exp );
		}
		$exp = mb_eregi_replace( "'{{(.|\n)+?}}'", 'NULL', $exp );
		return $exp;
	}

	//FUNCIONES PRIVADAS

	private function _filtroContiene( $campo, $valor ) {
		if ( strlen($valor) < 2 ) { return ''; }
		$exp = mb_strtolower( $valor, 'utf-8' );
		$exp = mb_eregi_replace( '[aáàäâ]', '(a|á|à|ä|â|A|Á|À|Ä|Â)', $exp );
		$exp = mb_eregi_replace( '[eéëèê]', '(e|é|è|ë|ê|E|É|È|Ë|Ê)', $exp );
		$exp = mb_eregi_replace( '[iíïìî]', '(i|í|ì|ï|î|I|Í|Ì|Ï|Î)', $exp );
		$exp = mb_eregi_replace( '[oóöòô]', '(o|ó|ò|ö|ô|O|Ó|Ò|Ö|Ô)', $exp );
		$exp = mb_eregi_replace( '[uúüùû]', '(u|ú|ù|ü|û|U|Ú|Ù|Ü|Û)', $exp );
		$exp = mb_eregi_replace( '[ñÑ]', '(n|ñ|N|Ñ)', $exp );
		$exp = mb_eregi_replace( '[çÇ]' ,'(ç|c|Ç|C)', $exp );
		return "( $campo LIKE '%$valor%' OR $campo REGEXP '$exp' )";
	}
	private function _filtroIncluye( $campo, $valor ) {
		if ( strlen($valor) < 2 ) { return ''; }
		$exp = '';
		switch ($valor) {
			case 'F_NULO':
				$exp = "( $campo IS NULL )";
				break;
			case 'F_VACIO':
				$exp = "( $campo = '' )";
				break;
			case 'F_TODO':
				$exp = "( $campo IS NOT NULL )";
				break;
			default:
				$exp = "( CONCAT(',',$campo,',') LIKE '%,$valor,%' )";
				break;
		}
		return $exp;
	}
	private function _filtroCoincide( $campo, $valor ) {
		$exp = '';
		switch ($valor) {
			case 'F_NULO':
				$exp = "( $campo IS NULL )";
				break;
			case 'F_TODO':
				$exp = "( $campo IS NOT NULL )";
				break;
			default:
				$exp = "( $campo = '$valor' )";
				break;
		}
		return $exp;
	}
	private function _filtroTexto( $campo, $valor ) {
		$exp = '';
		switch ($valor) {
			case 'F_NULO':
				$exp = "( $campo IS NULL )";
				break;
			case 'F_VACIO':
				$exp = "( $campo = '' )";
				break;
			case 'F_TODO':
				$exp = "( $campo IS NOT NULL )";
				break;
			default:
				$exp = "( $campo = '$valor' )";
				break;
		}
		return $exp;
	}
	private function _filtroNumero( $campo, $valor ) {
		$exp = '';
		switch ($valor) {
			case 'F_NULO':
			case 'F_VACIO':
				$exp = "( $campo IS NULL )";
				break;
			case 'F_TODO':
				$exp = "( $campo IS NOT NULL )";
				break;
			default:
				if ( is_numeric($valor) ) { 
					$exp = "( $campo = '$valor' )";
				}
				break;
		}
		return $exp;
	}
	private function _filtroRangoFechas( $campo, $valor ) {
		$exp = ''; $desde = ''; $hasta = '';
		if ( is_array($valor) ) {
			$desde = $valor[0];
			if ( count($valor)==2 ) { $hasta = $valor[1]; }
			else { $hasta = ''; }
		} else {
			$aux = explode(',', $valor);
			$desde = $aux[0];
			if ( count($aux)==2 ) { $hasta = $aux[1]; }
			else { $hasta = ''; }
		}
		$mdesde = $this->_evaluarFecha( $desde );
		$mhasta = $this->_evaluarFecha( $hasta );
		if ( strlen($desde)!=0 && strlen($hasta)!=0 && strlen($mdesde)!=0 && strlen($mhasta)!=0 ) {
			$exp = "( $campo BETWEEN '$mdesde' AND '$mhasta' )";
		} else if ( strlen($desde)!=0 && strlen($mdesde)!=0 ) {
			$exp = "( $campo >= '$mdesde' )";
		} else if ( strlen($hasta)!=0 && strlen($mhasta)!=0 ) {
			$exp = "( $campo <= '$mhasta' )";
		}
		return $exp;
	}
	private function _filtroRangoNumeros( $campo, $valor ) {
		$exp = ''; $desde = ''; $hasta = '';
		if ( is_array($valor) ) {
			$desde = $valor[0];
			if ( count($valor)==2 ) { $hasta = $valor[1]; }
			else { $hasta = ''; }
		} else {
			$aux = explode(',', $valor);
			$desde = $aux[0];
			if ( count($aux)==2 ) { $hasta = $aux[1]; }
			else { $hasta = ''; }
		}
		if ( strlen($desde)!=0 && strlen($hasta)!=0 && is_numeric($desde) && is_numeric($hasta) ) {
			$exp = "( $campo BETWEEN '$desde' AND '$hasta' )";
		} else if ( strlen($desde)!=0 && is_numeric($desde) ) {
			$exp = "( $campo >= '$desde' )";
		} else if ( strlen($hasta)!=0 && is_numeric($hasta) ) {
			$exp = "( $campo <= '$hasta' )";
		}
		return $exp;
	}
	private function _filtroPeriodo( $campo, $valor ) {
		$exp = ''; $desde = ''; $hasta = '';
		$fecha = new DateTime(); 
		$hoy = $fecha->format('Y/m/d');
		switch ($valor) {
			case 'F_HOY':
				$exp = "( $campo = '$hoy' )";
				break;
			case 'F_AYER':
				$fecha->sub( new DateInterval( 'P1D' ) );
				$desde = $fecha->format('Y/m/d');
				$exp = "( $campo = '$desde' )";
				break;
			case 'F_ESTA_SEMANA':
				$diasemana = ( $fecha->format('w') != 0 ? $fecha->format('w') : 7 );
				$fecha->sub( new DateInterval( 'P' . ($diasemana - 1) . 'D' ) );
				$desde = $fecha->format('Y/m/d');
				$fecha = new DateTime(); 
				$fecha->add( new DateInterval( 'P' . (7 - $diasemana) . 'D' ) );
				$hasta = $fecha->format('Y/m/d');
				break;
			case 'F_ESTE_MES':
				$mes = $fecha->format('m');
				$ano = $fecha->format('Y');
				$exp = "( MONTH($campo) = '$mes' AND YEAR($campo) = '$ano' )";
				break;
			case 'F_ESTE_ANO':
				$ano = $fecha->format('Y');
				$exp = "( YEAR($campo) = '$ano' )";
				break;
			case 'F_ULT_SEMANA':
				$fecha->sub( new DateInterval( 'P1W' ) );
				$desde = $fecha->format('Y/m/d');
				$hasta = $hoy;
				break;
			case 'F_ULT_MES':
				$fecha->sub( new DateInterval( 'P1M' ) );
				$desde = $fecha->format('Y/m/d');
				$hasta = $hoy;
				break;
			case 'F_ULT_ANO':
				$fecha->sub( new DateInterval( 'P1Y' ) );
				$desde = $fecha->format('Y/m/d');
				$hasta = $hoy;
				break;
			case 'F_SIG_SEMANA':
				$fecha->add( new DateInterval( 'P1W' ) );
				$hasta = $fecha->format('Y/m/d');
				$fecha = new DateTime(); 
				$fecha->add( new DateInterval( 'P1D' ) );
				$desde = $fecha->format('Y/m/d');
				break;
			case 'F_SIG_MES':
				$fecha->add( new DateInterval( 'P1M' ) );
				$hasta = $fecha->format('Y/m/d');
				$fecha = new DateTime(); 
				$fecha->add( new DateInterval( 'P1D' ) );
				$desde = $fecha->format('Y/m/d');
				break;
			case 'F_SIG_ANO':
				$fecha->add( new DateInterval( 'P1Y' ) );
				$hasta = $fecha->format('Y/m/d');
				$fecha = new DateTime(); 
				$fecha->add( new DateInterval( 'P1D' ) );
				$desde = $fecha->format('Y/m/d');
				break;
			case 'F_ANT_SEMANA':
				$fecha->sub( new DateInterval( 'P1W' ) );
				$diasemana = ( $fecha->format('w') != 0 ? $fecha->format('w') : 7 );
				$aux = $fecha->format('Y/m/d');
				$fecha = new DateTime($aux); 
				$fecha->sub( new DateInterval( 'P' . ($diasemana - 1) . 'D' ) );
				$desde = $fecha->format('Y/m/d');
				$fecha = new DateTime($aux); 
				$fecha->add( new DateInterval( 'P' . (7 - $diasemana) . 'D' ) );
				$hasta = $fecha->format('Y/m/d');
				break;
			case 'F_ANT_MES':
				$aux = $fecha->format('Y/m/') . '15';
				$fecha = new DateTime($aux);
				$fecha->sub( new DateInterval( 'P1M' ) );
				$mes = $fecha->format('m');
				$ano = $fecha->format('Y');
				$exp = "( MONTH($campo) = '$mes' AND YEAR($campo) = '$ano' )";
				break;
			case 'F_ANT_ANO':
				$aux = $fecha->format('Y/m/') . '15';
				$fecha = new DateTime($aux);
				$fecha->sub( new DateInterval( 'P1Y' ) );
				$ano = $fecha->format('Y');
				$exp = "( YEAR($campo) = '$ano' )";
				break;
			case 'F_PRO_SEMANA':
				$fecha->add( new DateInterval( 'P1W' ) );
				$diasemana = ( $fecha->format('w') != 0 ? $fecha->format('w') : 7 );
				$aux = $fecha->format('Y/m/d');
				$fecha = new DateTime($aux); 
				$fecha->sub( new DateInterval( 'P' . ($diasemana - 1) . 'D' ) );
				$desde = $fecha->format('Y/m/d');
				$fecha = new DateTime($aux); 
				$fecha->add( new DateInterval( 'P' . (7 - $diasemana) . 'D' ) );
				$hasta = $fecha->format('Y/m/d');
				break;
			case 'F_PRO_MES':
				$aux = $fecha->format('Y/m/') . '15';
				$fecha = new DateTime($aux);
				$fecha->add( new DateInterval( 'P1M' ) );
				$hasta = $fecha->format('Y/m/d');
				$ano = $fecha->format('Y');
				$mes = $fecha->format('m');
				$exp = "( MONTH($campo) = '$mes' AND YEAR($campo) = '$ano' )";
				break;
			case 'F_PRO_ANO':
				$aux = $fecha->format('Y/m/') . '15';
				$fecha = new DateTime($aux);
				$fecha->add( new DateInterval( 'P1Y' ) );
				$ano = $fecha->format('Y');
				$exp = "( YEAR($campo) = '$ano' )";
				break;
			default:
				if ( strlen($valor)==6 ) {
					$ano = substr( $valor, 0, 4);
					$mes = substr( $valor, -2);
					$exp = "( MONTH($campo) = '$mes' AND YEAR($campo) = '$ano' )";
				}
				break;
		}
		if ( strlen($exp)==0 && strlen($desde)>0 && strlen($hasta)>0 ) {
			$exp = "( $campo BETWEEN '$desde' AND '$hasta' )";
		}
		return $exp;
	}
	private function _filtroRangoHoras( $campo, $valor ) {
		$exp = '';
		$exp = ''; $desde = ''; $hasta = '';
		if ( is_array($valor) ) {
			$desde = $valor[0];
			if ( count($valor)==2 ) { $hasta = $valor[1]; }
			else { $hasta = ''; }
		} else {
			$aux = explode(',', $valor);
			$desde = $aux[0];
			if ( count($aux)==2 ) { $hasta = $aux[1]; }
			else { $hasta = ''; }
		}
		if ( strlen($desde) < 3 ) { $desde = $desde . ':00'; }
		if ( strlen($hasta) < 3 ) { $hasta = $hasta . ':00'; }
		$mdesde = $this->_evaluarHora( $desde );
		$mhasta = $this->_evaluarHora( $hasta );
		if ( strlen($desde)!=0 && strlen($hasta)!=0 && strlen($mdesde)!=0 && strlen($mhasta)!=0 ) {
			$exp = "( $campo BETWEEN '$mdesde' AND '$mhasta' )";
		} else if ( strlen($desde)!=0 && strlen($mdesde)!=0 ) {
			$exp = "( $campo >= '$mdesde' )";
		} else if ( strlen($hasta)!=0 && strlen($mhasta)!=0 ) {
			$exp = "( $campo <= '$mhasta' )";
		}
		return $exp;
	}
	private function _filtroRangoDias( $campo, $valor ) {
		$exp = ''; $desde = ''; $hasta = '';
		if ( is_array($valor) ) {
			$desde = $valor[0];
			if ( count($valor)==2 ) { $hasta = $valor[1]; }
			else { $hasta = ''; }
		} else {
			$aux = explode(',', $valor);
			$desde = $aux[0];
			if ( count($aux)==2 ) { $hasta = $aux[1]; }
			else { $hasta = ''; }
		}
		if ( strlen($desde)!=0 && strlen($hasta)!=0 && is_numeric($desde) && is_numeric($hasta) ) {
			$exp = "( DATEDIFF(NOW(), $campo ) BETWEEN '$desde' AND '$hasta' )";
		} else if ( strlen($desde)!=0 && is_numeric($desde) ) {
			$exp = "( DATEDIFF(NOW(), $campo ) >= '$desde' )";
		} else if ( strlen($hasta)!=0 && is_numeric($hasta) ) {
			$exp = "( DATEDIFF(NOW(), $campo ) <= '$hasta' )";
		}
		return $exp;
	}
	private function _filtroListaNumeros( $campo, $valor ) {
		$exp = '';
		while ( substr_count( $valor, chr(10) )>0 ) { $valor = str_replace(chr(10), chr(13), $valor); }
		while ( substr_count( $valor, chr(13))>0 ) { $valor = str_replace(chr(13), ',', $valor); }
		while ( substr_count( $valor, ' ')>0 ) { $valor = str_replace(' ', ',', $valor); }
		while ( substr_count( $valor, ',,')>0 ) { $valor = str_replace(',,', ',', $valor); }
		$lista = explode( ',', $valor );
		$final = array();
		foreach ( $lista as $numero ) {
			if ( strlen(trim($numero))>0 && is_numeric($numero) ) { $final[] = $numero; }
		}
		$valor = implode( ', ', $final );
		if ( strlen($valor)>0 ) {
			$exp = "( $campo IN ( $valor ) )";
		}
		return $exp;
	}
	private function _filtroListaPalabras( $campo, $valor ) {
		$exp = '';
		while ( substr_count( $valor, chr(10) )>0 ) { $valor = str_replace(chr(10), chr(13), $valor); }
		while ( substr_count( $valor, chr(13))>0 ) { $valor = str_replace(chr(13), ',', $valor); }
		while ( substr_count( $valor, ' ')>0 ) { $valor = str_replace(' ', ',', $valor); }
		while ( substr_count( $valor, ',,')>0 ) { $valor = str_replace(',,', ',', $valor); }
		$lista = explode( ',', $valor );
		$final = array();
		foreach ( $lista as $palabra ) {
			if ( strlen(trim($palabra))>0 ) { $final[] = "'" . $palabra . "'"; }
		}
		$valor = implode( ', ', $final );
		if ( strlen($valor)>0 ) {
			$exp = "( $campo IN ( $valor ) )";
		}
		return $exp;
	}
	private function _filtroPalabras( $campo, $valor ) {
		$exp = '';
		$valor = str_replace(chr(39), '', $valor);
		$valor = str_replace(chr(34), '', $valor);
		$valor = str_replace('.', '', $valor);
		while ( substr_count($valor,'  ')>0 ) { $valor = str_replace('  ', ' ', $valor); }
		$valor = trim($valor);
		if ( strlen($valor) < 2 ) { return ''; }
		$lista = explode( ' ', $valor );
		foreach ( $lista as $palabra ) {
			$aux = mb_strtolower( $palabra, 'utf-8' );
			$aux = mb_eregi_replace( '[aáàäâ]', '(a|á|à|ä|â|A|Á|À|Ä|Â)', $aux );
			$aux = mb_eregi_replace( '[eéëèê]', '(e|é|è|ë|ê|E|É|È|Ë|Ê)', $aux );
			$aux = mb_eregi_replace( '[iíïìî]', '(i|í|ì|ï|î|I|Í|Ì|Ï|Î)', $aux );
			$aux = mb_eregi_replace( '[oóöòô]', '(o|ó|ò|ö|ô|O|Ó|Ò|Ö|Ô)', $aux );
			$aux = mb_eregi_replace( '[uúüùû]', '(u|ú|ù|ü|û|U|Ú|Ù|Ü|Û)', $aux );
			$aux = mb_eregi_replace( '[ñÑ]', '(n|ñ|N|Ñ)', $aux );
			$aux = mb_eregi_replace( '[çÇ]', '(ç|c|Ç|C)', $aux );
			$exp .= "( $campo LIKE '%$palabra%' OR $campo REGEXP '$aux' ) AND ";
		} 
		if ( substr($exp, -6) == ') AND ' ) { $exp = substr($exp, 0, strlen($exp) - 5); }
		return $exp;
	}
	private function _sanitizarValor( $valor ) {
		if ( is_array($valor) ) { $valor = implode( ',', $valor ); }
		return mb_ereg_replace( '[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $valor );
	}
	private function _evaluarFecha( $fecha, $formato = 'Y/m/d' ) {
		$evaluacion = '';
		if ( strlen($fecha) < 3 ) { return $evaluacion; }
		try { 
			$f = new DateTime($fecha);
			$evaluacion = $f->format($formato);
		} catch ( \Exception $e ) {}
		return $evaluacion;
	}
	private function _evaluarHora( $hora, $formato = 'H:i' ) {
		$evaluacion = '';
		if ( strlen($hora) == 0 ) { return $evaluacion; }
		try { 
			$f = new DateTime($hora);
			$evaluacion = $f->format($formato);
		} catch ( \Exception $e ) {}
		return $evaluacion;
	}
}
?>
<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Finales\Dto;
use MasExperto\ME\Interfaces\IModelo;
use MasExperto\ME\M;

abstract class Modelo implements IModelo
{
	protected $temp = array();
	protected $entidad = '';
	protected $tabla = '';
    public $dto = null;
	public $bd = null;
	public $almacen = null;
	public $T = array();
	public $A = array();
	public $D = array();
	public $I = array();
	public $F = array();
	public $R = array();
	public $sql = array();

	function __construct( &$dto ) {
		$bd = M::E('CONECTOR/BD');
		$alm = M::E('CONECTOR/ALMACEN');
		$conector = ( strlen($bd)>0 ? $bd : '\MasExperto\ME\Finales\BaseDatosMysql');
		$this->bd = new $conector;
		$conector = ( strlen($alm)>0 ? $alm : '\MasExperto\ME\Finales\AlmacenLocal');
		$this->almacen = new $conector;
		$this->almacen->Conectar( M::E('ALMACEN') );
        $this->dto = &$dto;
	}
	function __destruct() {
		unset($this->bd);
        unset($this->dto);
		unset($this->almacen);
		unset($this->temp);
		unset($this->sql);
		unset($this->T);
		unset($this->A);
		unset($this->D);
		unset($this->I);
		unset($this->F);
		unset($this->R);
	}

    public function Cotejar( $caso = '' ) {
        $estado = 0;
        $mensaje = '';
        if ( strlen($caso)>0 ) {
            $estado = 1;
            foreach ( $this->A as $nombre => $valor ) {
                if ( substr_count( ','.$valor['validar'].',', ','.$caso.',' )>0 ) {
                    $validacion = $this->Validar( $nombre, $this->dto->get($nombre) );
                    $mensaje .= $validacion['mensaje'];
                    $estado = ( $validacion['estado'] == 0 ? 0 : $estado );
                    if ( $estado ) {
                        $this->dto->getset( $nombre );
                    } else {
                        break;
                    }
                }
            }
        }
        return array(
            'estado'=> $estado,
            'mensaje'=> $mensaje
        );
    }
	public function Validar( $nombre, $valor ) {
		$mensaje = '';
		$estado = 0;
		if ( is_array($valor) ) {
			$evaluar = trim( implode(',', $valor) );
		} else {
			$evaluar = trim( strval($valor) );
		}
		$min = intval($this->A[$nombre]['minimo']);
		$max = intval($this->A[$nombre]['maximo']);
		$tipo = $this->A[$nombre]['tipo'];
		switch ( $tipo ) {
            case 'fecha':
            case 'texto':
				if ( $max > 0 && strlen($evaluar) >= $min && strlen($evaluar) <= $max ) { $estado = 1; }
				else if ( $max == 0 && strlen($evaluar) >= $min ) { $estado = 1; }
				break;
			case 'entero':
				if ( is_numeric($evaluar) ) {
					if ( $max > 0 && intval($evaluar) >= $min && intval($evaluar) <= $max ) { $estado = 1; }
					else if ( $max == 0 && intval($evaluar) >= $min ) { $estado = 1; }
				}
				break;
			case 'decimal':
				if ( is_numeric($evaluar) ) {
					if ( $max > 0 && floatval($evaluar) >= $min && floatval($evaluar) <= $max ) { $estado = 1; }
					else if ( $max == 0 && floatval($evaluar) >= $min ) { $estado = 1; }
				}
				break;
			case 'rut':
				$rut = explode( '-', $evaluar );
				if ( $min == 0 && strlen($evaluar) == 0 ) {
					$estado = 1;
				} else if ( count($rut)==2 && strlen($rut[1])==1 && is_numeric($rut[0]) ) {
					$s = 1;
					for( $i=0; $rut[0]!=0; $rut[0]/=10 ) { $s = ( $s + $rut[0]%10 * (9 - $i++%6 ) )%11; }
					$dv = chr( $s ? $s + 47 : 75 );
					if ( strtolower($dv) == strtolower($rut[1]) && strlen($evaluar) >= $min ) { $estado = 1; }
				}
				break;
			case 'opciones':
				$total = 0;
				if ( is_array($valor) ) {
					$total = count( $valor );
				} else if ( strlen(strval($valor))>0 ) {
					$total = 1;
				}
				if ( $max > 0 && $total >= $min && $total <= $max ) { $estado = 1; }
				else if ( $max == 0 && $total >= $min ) { $estado = 1; }
				break;
            case 'archivo':
				if ( strlen($evaluar) >= $min ) { $estado = 1; }
				break;
		}
		if ( $estado ) {
			$exp = $this->A[$nombre]['regla'];
			if ( strlen($exp)>0 && strlen($evaluar)>0 && $tipo!='archivo' ) {
				$expresion = '/^'.$exp.'$/';
				if ( !filter_var( $evaluar, FILTER_VALIDATE_REGEXP, array('options'=> array('regexp'=>$expresion)) ) ) {
					$estado = 0;
				}
			}		
		}
		if ( !$estado ) {
			if ( strlen( $this->A[$nombre]['etiqueta'] )>0 ) { $campo = $this->A[$nombre]['etiqueta'];} 
			else { $campo = $nombre; }
			$msg = $campo . ' ' . $this->A[$nombre]['error'] . '. ';
			$mensaje = str_replace( array('(min)', '(max)'), array(strval($min), strval($max)), $msg );
		}
		return array(
			'estado' => $estado,
			'mensaje' => $mensaje
		);
	}
	public function Atributos( $nombre = '' ) {
		$clase = $this->dto->get('clase', 'parametro');
		$componente = '\MasExperto\Adaptador\\' . $clase;
		if ( class_exists( $componente, true ) ) {
			$adaptador = new $componente;
			if ( $nombre == '' && strlen($adaptador->objeto)>0 ) {
				$adaptador->combinarMetadatos( '', $this, true );
				$nombre = $adaptador->objeto;
			} else {
				$adaptador->combinarMetadatos( '', $this, false );
			}
		}
		if ( strlen($nombre)>0 ) { $nombre = $nombre . '.'; }
		return 'M.' . $nombre . 'A = ' . json_encode($this->A) . '; M.' . $nombre . 'I = ' . json_encode($this->I);
	}
	public function Nuevo() {
		$estado = 1;
		$mensaje = '';
		$clase = $this->dto->get('clase', 'parametro');
		$base = $this->dto->get( 'base', 'parametro' );
		$componente = '\MasExperto\Adaptador\\' . $clase;
		if ( class_exists( $componente, true ) ) {
			$adaptador = new $componente;
			$adaptador->combinarMetadatos( '', $this );
		}
		$this->bd->Conectar( M::E('BD/1'), $this->dto );
		if ( isset($this->sql['lista_casos']) ) {
			$this->dto->set('M_MAX', 100, 'parametro');
			$this->dto->set('M_NAV', 1, 'parametro');
			$this->bd->consultarColeccion( $this->sql['lista_casos'], 'lista_casos', false );
		}
		if ( isset($this->sql['caso_agregar']) && strlen($base) > 0 ) {
			$sql = str_replace( '{{base}}', $base, $this->sql['caso_agregar'] );
			$this->bd->consultarElemento( $sql, 'caso', false );
		}
		unset( $adaptador );
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function Borrar() {
		$estado = 0;
		$this->dto->set( 'id', M::E('RECURSO/ELEMENTO') );
		if ( isset($this->sql['borrar']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['borrar'] );
			$respuesta = $this->bd->borrarElementos( $sql, $this->entidad );
			$estado = $respuesta['estado'];
		}
		if ( $estado == 1 ) {
			$mensaje = $this->T['caso-borrado'];
			if ( isset($this->sql['items_borrar']) ) {
				$sql = $this->bd->reemplazarValores( $this->sql['items_borrar'] );
				$this->bd->borrarElementos( $sql, 'items_borrar' );
			}
			if ( isset($this->sql['casos_borrar']) ) {
				$sql = $this->bd->reemplazarValores( $this->sql['casos_borrar'] );
				$this->bd->borrarElementos( $sql, 'casos_borrar' );
			}
		} else {
			$mensaje = $this->T['caso-no-borrado'];
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function Registrar() {
		$estado = 0;
		$this->dto->set( 'id', M::E('RECURSO/ELEMENTO') );
		$valor = $this->dto->getset( 'valor' );
		if ( isset($this->sql['registrar']) && strlen($valor)>0 ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['registrar'] );
			$respuesta = $this->bd->editarElementos( $sql, $this->entidad );
			$estado = $respuesta['estado'];
		}
		if ( $estado == 1 ) {
			$mensaje = $this->T['caso-actualizado'];
		} else {
			$mensaje = $this->T['caso-no-actualizado'];
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function Agregar() {
		$estado = 0;
		$uid = '';
		if ( isset($this->sql['agregar']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['agregar'] );
			$resultado = $this->bd->agregarElemento($sql, $this->entidad );
			$uid = $resultado['uid'];
			$estado = ( $resultado['total']==1 && $resultado['estado']==1 && is_numeric( $uid ) ? 1 : 0 );
		}
		if ( $estado == 1 ) {
			$datos = array( 'id'=>$uid );
			$sql = $this->bd->reemplazarValores( $this->sql['abrir'], $datos );
			$this->bd->consultarElemento( $sql, 'caso', false );
			$clase = $this->dto->resultados['caso']['clase'];
			$componente = '\MasExperto\Adaptador\\' . $clase;
			if ( class_exists( $componente, true ) ) {
				$adaptador = new $componente;
				$adaptador->combinarMetadatos( $uid, $this );
				if ( is_dir($adaptador->ruta['xml']) ) {
					$this->dto->set('esquema', $adaptador->esquema, 'valor');
					$this->dto->set('rutaxml', $adaptador->ruta['xml'], 'valor');
				}
			}
			$mensaje = $this->T['caso-guardado'];
		} else {
			$mensaje = $this->T['caso-no-guardado'];
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje,
			'uid'=> $uid
		);
	}
	public function Editar() {
		$estado = 0;
		$this->dto->set( 'id', M::E('RECURSO/ELEMENTO') );
		if ( isset($this->sql['editar']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['editar'] );
			$respuesta = $this->bd->editarElementos( $sql, $this->entidad );
			$estado = $respuesta['estado'];
		}
		if ( $estado == 1 ) {
			$mensaje = $this->T['caso-actualizado'];
		} else {
			$mensaje = $this->T['caso-no-actualizado'];
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function Cambiar() {
		$estado = 0;
		$tipo = $this->dto->campos['tipo'];
		$valor = $this->dto->campos['value'];
		$this->dto->set( 'id', M::E('RECURSO/ELEMENTO') );
		$this->dto->set( 'valor', $valor );
		if ( $tipo == 'num' ) {
			$valor2 = str_replace( '.', '', $valor );
			$valor2 = str_replace( ',', '.', $valor2 );
			$this->dto->set( 'valor', $valor2 );
		}
		if ( isset($this->sql['cambiar']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$datos = array( 'campo'=>$this->dto->campos['campo'] );
			$sql = $this->bd->reemplazarValores( $this->sql['cambiar'], $datos );
			$respuesta = $this->bd->editarElementos( $sql, $this->entidad );
			$estado = $respuesta['estado'];
		}
		return array(
			'estado'=> $estado,
			'valor'=> $valor
		);
	}
	public function Abrir() {
		$estado = 0;
		$mensaje = '';
		$this->dto->set( 'id', M::E('RECURSO/ELEMENTO') );
		if ( isset($this->sql['abrir']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['abrir'] );
			$respuesta = $this->bd->consultarElemento( $sql, 'caso', false );
			$estado = ( $respuesta['estado']==1 && $respuesta['total']==1 ? 1 : 0 );
		}
		if ( $estado == 0 ) {
			$mensaje = $this->T['caso-no-existe'];
		} else {
			if ( isset($this->dto->resultados['caso']['clase']) ) {
				$clase = $this->dto->resultados['caso']['clase'];
				$componente = '\MasExperto\Adaptador\\' . $clase;
				if ( class_exists( $componente, true ) ) {
					$adaptador = new $componente;
					$adaptador->combinarMetadatos( M::E('RECURSO/ELEMENTO'), $this );
					if ( is_dir($adaptador->ruta['xml']) ) {
						$this->dto->set('esquema', $adaptador->esquema, 'valor');
						$this->dto->set('rutaxml', $adaptador->ruta['xml'], 'valor');
					}
				}
			}
			$this->dto->set('M_MAX', 100, 'parametro');
			$this->dto->set('M_NAV', 1, 'parametro');
			if ( isset($this->sql['lista_casos']) ) {
				$this->bd->consultarColeccion( $this->sql['lista_casos'], 'lista_casos', false );
			}
			if ( isset($this->sql['lista_items']) ) {
				$sql = $this->bd->reemplazarValores( $this->sql['lista_items'] );
				$this->bd->consultarColeccion( $sql, 'lista_items', false );
			}
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function Consultar() {
		$estado = 0;
		$mensaje = '';
		$this->dto->traspasarPeticion(Dto::T_PARAMETROS);
		$this->dto->set( 'leyenda', $this->T['leyenda-lista'], 'valor' );
		$this->dto->set( 'leyenda2', $this->T['leyenda-vacia'], 'valor' );
		if ( isset($this->sql['consultar']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->sql['consultar'];
			foreach( $this->F as $campo => $tipo ) {
				$sql = $this->bd->aplicarFiltro( $tipo['codigo'], $campo, $sql, $this->tabla );
			}
			$sql = $this->bd->reemplazarValores( $sql );
			$respuesta = $this->bd->consultarColeccion( $sql, $this->entidad, true );
			$estado = $respuesta['estado'];
		}
		if ( $estado == 0 ) {
			$mensaje = $this->T['error-lista'];
		} else {
			$this->dto->set('M_MAX', 100, 'parametro');
			$this->dto->set('M_NAV', 1, 'parametro');
			if ( isset($this->sql['lista_casos']) ) {
				$sql = $this->bd->reemplazarValores( $this->sql['lista_casos'] );
				$this->bd->consultarColeccion( $sql, 'lista_casos', false );
			}
			if ( isset($this->sql['lista_clases']) ) {
				$sql = $this->bd->reemplazarValores( $this->sql['lista_clases'] );
				$this->bd->consultarColeccion( $sql, 'lista_clases', false );
			}
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function Ver() {
		$estado = 0;
		$mensaje = '';
		$nombre = '';
		$estilos = '';
		$uid = M::E('RECURSO/ELEMENTO');
		$this->dto->set( 'id', $uid );
		$info = $this->dto->getset( 'info' );
		if ( isset($this->sql['abrir']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['abrir'] );
			$respuesta = $this->bd->consultarElemento( $sql, 'caso', false );
			$estado = ( $respuesta['estado']==1 && $respuesta['total']==1 ? 1 : 0);
		}
		if ( $estado == 1 ) {
			$clase = $this->dto->get('clase', 'parametro');
			$componente = '\MasExperto\Adaptador\\' . $clase;
			if ( class_exists( $componente, true ) ) {
				$adaptador = new $componente;
				if ( is_dir($adaptador->ruta['xml']) ) {
					$this->dto->set('esquema', $adaptador->esquema, 'valor');
					$this->dto->set('rutaxml', $adaptador->ruta['xml'], 'valor');
				}
			}
			if ( isset($this->dto->resultados['caso']['extension']) ) {
				$extension = $this->dto->resultados['caso']['extension'];
				$componente1 = '\MasExperto\Extension\\' . $extension;
				$componente2 = '\MasExperto\Componente\\' . $extension;
                $adaptador = null;
				if ( class_exists( $componente1, true ) ) {
					$adaptador = new $componente1;
				} else if ( class_exists( $componente2, true ) ) {
					$adaptador = new $componente2;
				}
				if ( $adaptador !== null ) {
                    $adaptador->combinarMetadatos( $uid, $this );
                    $resultado = $adaptador->consultarInformacion( $info );
                    $this->dto->resultados['caso']['contenido'] = $resultado['contenido'];
                    $nombre = $resultado['nombre'];
                    $estilos = $resultado['estilos'];
                }
			}
		}
		if ( $estado == 0 ) {
			$mensaje = $this->T['caso-no-existe'];
		}
		return array(
			'estado'=> $estado,
			'nombre'=> $nombre,
			'estilos'=> $estilos,
			'mensaje'=> $mensaje
		);
	}
	public function Ejecutar() {
        $estado = 0;
        $lista = implode( ',', $this->dto->get( 'caso' ) );
        $funcion = $this->dto->get( 'funcion' );
        $valor = $this->dto->get( $funcion );
        if ( isset($this->sql[$funcion]) && strlen($lista)>0 ) {
            $this->bd->Conectar( M::E('BD/1'), $this->dto );
            $sql = $this->sql[$funcion];
            $sql = str_replace( '{{lista}}', $lista, $sql );
            $sql = str_replace( '{{valor}}', $valor, $sql );
            $sql = $this->bd->reemplazarValores( $sql );
            $respuesta = $this->bd->editarElementos( $sql, $funcion );
            $estado = $respuesta['estado'];
        }
        if ( $estado == 1 ) {
            $mensaje = $this->T['casos-actualizados'];
        } else {
            $mensaje = $this->T['casos-no-actualizados'];
        }
        return array(
            'estado'=> $estado,
            'mensaje'=> $mensaje
        );
    }
    public function Adjuntar( $opciones = array() ) {
        $mensaje = '';
        $archivo = '';
        $tipo = '';
        $peso = '';
        $original = '';
        $nombre = '';
        if ( !isset($opciones['tipos']) ) { $opciones['tipos'] = array( 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx' ); }
        if ( !isset($opciones['peso']) ) { $opciones['peso'] = '10 MB'; }
        if ( !isset($opciones['nombre']) ) { $opciones['nombre'] = '{{uniqid}}'; }
        if ( !isset($opciones['carpeta']) ) { $opciones['carpeta'] = strval(date('Y')); }
        if ( !isset($opciones['almacenar']) ) { $opciones['almacenar'] = false; }
        $carga = $this->almacen->cargarArchivos( Almacen::PRIVADO, $opciones );
        $estado = $carga['estado'];
        if ( $estado ) {
            $archivo = $carga['contenidos'][0]['relativa'];
            $tipo = $carga['contenidos'][0]['tipo'];
            $peso = $carga['contenidos'][0]['peso'];
            $original = $carga['contenidos'][0]['original'];
            $nombre = pathinfo( $original, PATHINFO_FILENAME );
            if ( $opciones['almacenar'] == true && isset($this->sql['archivo_almacenar']) ) {
                $sql = str_replace( '{{archivo}}', $archivo, $this->sql['archivo_almacenar'] );
                $sql = str_replace( '{{id}}', M::E('RECURSO/ELEMENTO'), $sql );
                $this->bd->Conectar( M::E('BD/1'), $this->dto );
                $resultado = $this->bd->editarElementos( $sql, 'archivo' );
                $estado = $resultado['estado'];
            }
        } else {
            $mensaje = implode( '. ', $carga['errores'] );
        }
        if ( $estado ) {
            $mensaje = $this->T['archivo-cargado'];
        } else {
            if ( strlen($mensaje)==0 ) {
                $mensaje = $this->T['archivo-no-cargado'];
            }
        }
        return array(
            'estado'=> $estado,
            'archivo'=> $archivo,
            'tipo'=> $tipo,
            'peso'=> $peso,
            'original'=> $original,
            'nombre'=> $nombre,
            'mensaje'=> $mensaje
        );
    }
}

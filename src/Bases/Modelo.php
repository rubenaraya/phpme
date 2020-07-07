<?php
namespace MasExperto\ME\Bases;

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
				} else if ( strlen($valor)>0 ) {
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
			$adaptador->combinarMetadatos( '', $this );
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
		$mensaje = '';
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
		$mensaje = '';
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
		$mensaje = '';
		$uid = '';
		if ( isset($this->sql['agregar']) ) {
			$this->bd->Conectar( M::E('BD/1'), $this->dto );
			$sql = $this->bd->reemplazarValores( $this->sql['agregar'] );
			$resultado = $this->bd->agregarElemento($sql, $this->entidad );
			$uid = $resultado['uid'];
			$estado = ( $resultado['total']==1 && $resultado['estado']==1 && is_numeric( $uid ) ? 1 : 0 );
		}
		if ( $estado == 1 ) {
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
		$mensaje = '';
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
		$this->bd->Conectar( M::E('BD/1'), $this->dto );
		$this->dto->set( 'id', M::E('RECURSO/ELEMENTO') );
		if ( isset($this->sql['abrir']) ) {
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
					if ( is_dir($adaptador->ruta['xsl']) ) {
						$this->dto->set('vista', $adaptador->vista, 'valor');
						$this->dto->set('rutaxsl', $adaptador->ruta['xsl'], 'valor');
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
		$this->dto->traspasarPeticion(1);
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
			if ( isset($this->sql['lista_casos']) ) {
				$this->dto->set('M_MAX', 100, 'parametro');
				$this->dto->set('M_NAV', 1, 'parametro');
				$this->bd->consultarColeccion( $this->sql['lista_casos'], 'lista_casos', false );
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
				if ( is_dir($adaptador->ruta['xsl']) ) {
					$this->dto->set('vista', $adaptador->vista, 'valor');
					$this->dto->set('rutaxsl', $adaptador->ruta['xsl'], 'valor');
				}
			}
			if ( isset($this->dto->resultados['caso']['extension']) ) {
				$extension = $this->dto->resultados['caso']['extension'];
				$componente1 = '\MasExperto\Extension\\' . $extension;
				$componente2 = '\MasExperto\Componente\\' . $extension;
				if ( class_exists( $componente1, true ) ) {
					$adaptador = new $componente1;
					$adaptador->combinarMetadatos( $uid, $this );
					$resultado = $adaptador->consultarInformacion( $info );
					$this->dto->resultados['caso']['contenido'] = $resultado['contenido'];
					$nombre = $resultado['nombre'];
					$estilos = $resultado['estilos'];
				} else if ( class_exists( $componente2, true ) ) {
					$adaptador = new $componente2;
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
}

<?php 
namespace MasExperto\ME;
use MasExperto\ME\Interfaces\IInstructor;

abstract class Instructor extends Adaptador implements IInstructor
{
	//PROPIEDADES
		public $colores = array();
		public $indice = 0;
		protected $documento = null;
		protected $temp = array();
		protected $carpeta;

	function __construct() {
		$this->clase = str_replace( array('SER\\', 'EXT\\'), '', static::class );
	}
	function __destruct() {
		$this->documento = null;
		unset($this->modelo, $this->dto, $this->sql, $this->T, $this->I, $this->D, $this->A, $this->R, $this->temp, $this->colores, $this->documento);
	}
	public function consultarInformacion( $info = '' ) {}
	public function aplicarCalculos( $opciones ) {}

	public function abrirContenido() {
		/* Consumido por: ModeloActividades->Realizar */
		//TODO: Falta que lea el contenido existente en la respuesta, para mostrar sus valores
		$estado = 0;
		$mensaje = '';
		$opc['info'] = 'realizar';
		$opc['incluir'] = RUTA_ME . '/Instructor.xsl';
		$presentador = new PresentadorXml();
		$presentador->crearVista( $this->esquema, $this->ruta['xml'] );
		$presentador->anexarDatos( $this->dto );
		$presentador->anexarMatriz( $this->modelo->D );
		$presentador->anexarMatriz( $this->modelo->A, 'a' );
		$contenido = $presentador->Transformar( $this->vista, $this->ruta['xsl'], $opc );
		if ( strlen($contenido)>0 ) {
			$estado = 1;
		} else {
			$mensaje = $this->modelo->T['info-no-encontrada'];
			$contenido = $mensaje;
		}
		unset( $presentador );
		return array(
			'contenido'=> $contenido,
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function guardarRespuestas( $seccion = '' ) {
		/* Consumido por: ModeloActividades->Guardar */
		$estado = 0;
		$mensaje = '';
		$filtro = '';
		$this->dto->traspasarPeticion(0);
		$this->documento = simplexml_load_file( $this->ruta['xml'] . '/' . $this->esquema );
		if ( strlen($seccion)>0 ) { $filtro = "[@id=' " . $seccion . "']"; }
		$items = $this->documento->xpath( "//instructor//seccion" . $filtro . "/grupo/item" );
		$sql = '';
		foreach( $items as $item ) {
			$campo = (string) $item['id'];
			if ( strlen($campo)>0 ) {
				$sql = $sql . $campo . "='{{" . $campo . "}}', ";
				$subitems = $item->xpath( "opcion[@vincular]" );
				foreach( $subitems as $subitem ) {
					$vincular = (string) $subitem['vincular'];
					if ( strlen($vincular)>0 ) {
						$sql = $sql . $vincular . "='{{" . $vincular . "}}', ";
					}
				}
			}
		}
		$datos = array( 'app_id'=>M::E('APP_ID'), 'idusuario'=>M::E('M_USUARIO'), 'uid'=>$this->uid );
		$sql = str_replace( '{{expresion}}', $sql, $this->sql['guardar_respuestas'] );
		$sql = $this->modelo->bd->reemplazarValores( $sql, $datos );
		$resultado = $this->modelo->bd->editarElementos( $sql, 'respuestas' );
		$estado = $resultado['estado'];
		if ( $estado == 1 ) {
			$mensaje = $this->modelo->T['respuestas-guardadas'];
		} else {
			$mensaje = $this->modelo->T['respuestas-no-guardadas'];
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function crearRegistro( $usuario ) {
		/* Consumido por: ModeloActividades->Agregar */
		$estado = 0;
		$datos = array( 'app_id'=>M::E('APP_ID'), 'idusuario'=>$usuario, 'uid'=>$this->uid, 'clase'=>$this->clase );
		$sql = $this->modelo->bd->reemplazarValores( $this->sql['respuestas_agregar'], $datos );
		$resultado = $this->modelo->bd->agregarElemento( $sql, 'respuestas' );
		$estado = $resultado['estado'];
		$total = $resultado['total'];
		return array(
			'estado'=> $estado,
			'total'=> $total
		);
	}

	public function exportarRespuestas( $opciones = array() ) {
		//TODO: Revisar y corregir completo
		//Consumido por: ModeloEncuestas (Convertir)
		$estado = 0;
		$mensaje = '';
		$contenido = array('');
		$this->documento = simplexml_load_file( $this->ruta . '/' . $this->clase . '.xml' );
		$items = $this->documento->xpath( "//cuestionario//seccion/grupo/item[@convertir]" );
		$sql = "SELECT id AS ID, idgrupo AS Grupo";
		foreach( $items as $item ) {
			$campo = (string) $item['id'];
			$convertir = (string) $item['convertir'];
			if ( strlen($campo)>0 && strlen($convertir)>0 ) {
				$sql = $sql . ', ' . $campo . " AS '" . $convertir . "'";
				$subitems = $this->documento->xpath( "//cuestionario//item[@id='". $campo ."']/opcion[@vincular]" );
				foreach( $subitems as $subitem ) {
					$vincular = (string) $subitem['vincular'];
					$etiqueta = (string) $subitem['etiqueta'];
					if ( strlen($vincular)>0 && strlen($etiqueta)>0 ) {
						$sql = $sql . ', ' . $vincular . " AS '" . $etiqueta . "'";
					}
				}
			}
		}
		$items = $this->documento->xpath( "//cuestionario/categoria" );
		foreach( $items as $item ) {
			$campo = (string) $item['id'];
			$convertir = (string) $item['etiqueta'];
			if ( strlen($campo)>0 && strlen($convertir)>0 ) {
				$sql = $sql . ', ' . $campo . " AS '(" . $convertir . ")'";
			}
		}
		$sql = $sql . " FROM respuestas WHERE idencuesta='{{id}}' AND estado=3";
		$sql = str_replace( '{{id}}', $this->uid, $sql );
		$this->dto->set('M_MAX', 5000, 'parametro');
		$this->dto->set('M_NAV', 1, 'parametro');
		$resultado = $this->modelo->bd->consultarColeccion( $sql, 'convertidos', false );
		$estado = $resultado['estado'];
		if ( $estado == 1 ) {
			$contenido = $this->dto->extraerResultado( 'convertidos' );
		} else {
			$mensaje = $this->T['error-lista'];
		}
		return array(
			'contenido'=> $contenido,
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function procesarResultados( $opciones = array() ) {
		//TODO: Revisar y corregir completo
		//Consumido por: ModeloEncuestas (Registrar | estado: 3=Procesada)
		$estado = 0;
		$mensaje = '';
		$filtro = '';
		$filtrar = '';
		$procedimiento = array();
		$aux = array();
		$catalogar = false;
		$calcular = false;
		$this->documento = simplexml_load_file( $this->ruta . '/' . $this->clase . '.xml' );
		$campos = '';
		$recuentos = "INSERT INTO recuentos (idencuesta, item, opcion, {{campos}} total, porcentaje) SELECT idencuesta, '{{item}}' AS item, {{item}} AS opcion, ";
		$sql = "UPDATE respuestas SET ";
		$nodos = $this->documento->xpath( "//cuestionario/categoria" );
		foreach( $nodos as $nodo ) {
			$categoria = (string) $nodo['id'];
			$origen = (string) $nodo['origen'];
			if ( strlen($categoria)>0 && strlen($origen)>0 ) {
				$sql = $sql . $categoria."=(CASE ";
				$subnodos = $this->documento->xpath( "//cuestionario/categoria[@id='". $categoria ."']/segmento" );
				foreach( $subnodos as $subnodo ) {
					$valor = (string) $subnodo['valor'];
					$sid = (string) $subnodo['id'];
					$cuenta = 'c'. $sid;
					$porcentaje = 'p'. $sid;;
					$etiqueta = (string) $subnodo['etiqueta'];
					$desde = (string) $subnodo['desde'];
					$hasta = (string) $subnodo['hasta'];
					$igual = (string) $subnodo['igual'];
					$lista = (string) $subnodo['lista'];
					$matriz = explode(',', $lista);
					foreach ( $matriz as &$elemento ) {
						$elemento = "'" . str_replace("'", '', $elemento) . "'";
					}
					$lista = implode(',', $matriz);
					$exp = '';
					if ( strlen($igual)>0 ) {
						$exp = "WHEN " . $origen . "='" . $igual . "' THEN '" . $valor . "' ";
					} elseif ( strlen($desde)>0 && strlen($hasta)>0 ) {
						$exp = "WHEN " . $origen . " BETWEEN '" . $desde . "' AND '" . $hasta . "' THEN '" . $valor . "' ";
					} elseif ( strlen($lista)>0 ) {
						$exp = "WHEN " . $origen . " IN (" . $lista . ") THEN '" . $valor . "' ";
					}
					$sql = $sql . $exp;
					$recuentos = $recuentos . "SUM(IF(" . $categoria . "='" . $valor . "',1,0)) AS '" . $cuenta . "', ROUND(SUM(IF(" . $categoria . "='" . $valor . "',1,0))/COUNT(*)*100,1) AS '" . $porcentaje . "', ";
					$campos = $campos . $cuenta . ', ' . $porcentaje . ', ';
					$catalogar = true;
				}
				$sql = $sql . "END), ";
			}
		}
		if ( strlen($filtro)>0 ) { $filtrar = 'AND idgrupo IN (' . $filtro . ')'; }
		$recuentos = $recuentos . "COUNT(*) AS total, ROUND(COUNT(*) / (SELECT COUNT(*) FROM respuestas WHERE estado=3 AND idencuesta={{id}} AND {{item}} IS NOT NULL) * 100, 1) AS porcentaje FROM respuestas WHERE estado=3 AND idencuesta={{id}} {{filtro}} AND {{item}} IS NOT NULL GROUP BY {{item}}";
		$recuentos = str_replace( array('{{campos}}','{{filtro}}'), array($campos, $filtrar), $recuentos );
		$sql = $sql . "WHERE idencuesta='{{id}}' AND estado=3";
		$sql = str_replace( ' END), WHERE ', ' END) WHERE ', $sql );
		$sql = str_replace( '{{id}}', $this->uid, $sql );
		if ( $catalogar ) {
			$resultado = $this->modelo->bd->editarElementos( $sql, 'categoria' );
			$estado = $resultado['estado'];
		}
		if ( $estado == 1 ) {
			$sql = str_replace( '{{id}}', $this->uid, $this->sql['recuentos_borrar'] );
			$respuesta = $this->modelo->bd->borrarElementos( $sql, 'recuentos' );
			$items = $this->documento->xpath( "//cuestionario/categoria" );
			foreach( $items as $item ) {
				$campo = (string) $item['id'];
				$procedimiento[] = str_replace( array('{{item}}', '{{id}}'), array($campo, $this->uid), $recuentos );
			}
			$procedimiento[] = str_replace( array('{{item}}', '{{id}}'), array('idgrupo', $this->uid), $recuentos );
			$items = $this->documento->xpath( "//cuestionario//seccion/grupo/item[@recuento!='']" );
			foreach( $items as $item ) {
				$campo = (string) $item['id'];
				$procedimiento[] = str_replace( array('{{item}}', '{{id}}'), array($campo, $this->uid), $recuentos );
			}
		}
		if ( $estado == 1 ) {
			$campos = '';
			$recalculos = "INSERT INTO recalculos (idencuesta, elemento, {{campos}} cuenta, promedio) SELECT idencuesta, '{{indicador}}' AS elemento, ";
			$sql = "UPDATE respuestas SET ";
			$nodos = $this->documento->xpath( "//cuestionario/indicador" );
			foreach( $nodos as $nodo ) {
				$indicador = (string) $nodo['id'];
				$origen = (string) $nodo['origen'];
				$compo = (string) $nodo['componentes'];
				if ( strlen($indicador)>0 && strlen($origen)>0 && strlen($compo)>0 ) {
					$componentes = explode(',', $compo);
					$numerador = '';
					$denominador = '';
					foreach ($componentes as $componente) {
						$numerador = $numerador . 'IF(' . $componente . ' IS NOT NULL, ' . $componente . ', 0) + ';
						$denominador = $denominador . 'IF(' . $componente . ' IS NOT NULL, 1, 0) + ';
					}
					$sql = $sql . $origen . '=ROUND((' . $numerador . ') / (' . $denominador . '), 2), ';
					$calcular = true;
				}
			}
			$categorias = $this->documento->xpath( "//cuestionario/categoria" );
			foreach( $categorias as $nodo ) {
				$categoria = (string) $nodo['id'];
				$subnodos = $this->documento->xpath( "//cuestionario/categoria[@id='". $categoria ."']/segmento" );
				foreach( $subnodos as $subnodo ) {
					$valor = (string) $subnodo['valor'];
					$sid = (string) $subnodo['id'];
					$cuenta = 'c'. $sid;
					$promedio = 'r'. $sid;;
					$recalculos = $recalculos . "SUM(IF(" . $categoria . "='" . $valor . "',1,0)) AS '" . $cuenta . "', ROUND(AVG(IF(" . $categoria . "='" . $valor . "', {{item}}, NULL)), 2) AS '" . $promedio . "', ";
					$campos = $campos . $cuenta . ', ' . $promedio . ', ';
				}
			}
			if ( strlen($filtro)>0 ) { $filtrar = 'AND idgrupo IN (' . $filtro . ')'; }
			$recalculos = $recalculos . "COUNT(*) AS cuenta, ROUND(AVG({{item}}), 2) AS promedio FROM respuestas WHERE estado=3 AND idencuesta={{id}} {{filtro}} AND {{item}} IS NOT NULL";
			$recalculos = str_replace( array('{{campos}}','{{filtro}}'), array($campos, $filtrar), $recalculos );
			$sql = $sql . "WHERE idencuesta='{{id}}' AND estado=3";
			$sql = str_replace( array(', WHERE ', ') + )', '{{id}}'), array(' WHERE ', '))', $this->uid), $sql );
			if ( $calcular ) { $this->modelo->bd->editarElementos( $sql, 'indicadores' ); }
			$sql = str_replace( '{{id}}', $this->uid, $this->sql['recalculos_borrar'] );
			$respuesta = $this->modelo->bd->borrarElementos( $sql, 'recalculos' );
			$items = $this->documento->xpath( "//cuestionario/indicador" );
			foreach( $items as $item ) {
				$campo = (string) $item['id'];
				$origen = (string) $item['origen'];
				$procedimiento[] = str_replace( array('{{indicador}}', '{{item}}', '{{id}}'), array($campo, $origen, $this->uid), $recalculos );
			}
			$items = $this->documento->xpath( "//cuestionario//seccion/grupo/item[@calculo!='']" );
			foreach( $items as $item ) {
				$campo = (string) $item['id'];
				$procedimiento[] = str_replace( array('{{indicador}}', '{{item}}', '{{id}}'), array($campo, $campo, $this->uid), $recalculos );
			}
		}
		if ( $estado == 1 ) {
			foreach( $procedimiento as $instruccion ) {
				$resultado = $this->modelo->bd->editarElementos( $instruccion, 'procesar' );
			}
			$mensaje = $this->T['cuestionario-procesado'];
		} else {
			$mensaje = $this->T['error-lista'];
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function generarInforme( $opciones = array() ) {
		//TODO: Revisar y corregir completo
		//Consumido por: ModeloEncuestas (Registrar | estado: 4=Finalizada)
		$estado = 0;
		$mensaje = '';
		$contenido = array();
		$usar_recuentos = ( isset($cfg['recuentos']) ? $cfg['recuentos'] : true );
		$usar_recalculos = ( isset($cfg['recalculos']) ? $cfg['recalculos'] : true );
		$usar_respuestas = ( isset($cfg['respuestas']) ? $cfg['respuestas'] : false );
		$usar_participantes = ( isset($cfg['participantes']) ? $cfg['participantes'] : false );
		$this->dto->set('M_MAX', 1000, 'parametro');
		$this->dto->set('M_NAV', 1, 'parametro');
		if ( $usar_recuentos && isset($this->sql['recuentos_consultar']) ) {
			$sql = str_replace( '{{id}}', $this->uid, $this->sql['recuentos_consultar'] );
			$resultado = $this->modelo->bd->consultarColeccion( $sql, 'recuentos', false );
			$estado = $resultado['estado'];
		}
		if ( $usar_recalculos && isset($this->sql['recalculos_consultar']) ) {
			$sql = str_replace( '{{id}}', $this->uid, $this->sql['recalculos_consultar'] );
			$resultado = $this->modelo->bd->consultarColeccion( $sql, 'recalculos', false );
			$estado = $resultado['estado'];
		}
		if ( $usar_respuestas && isset($this->sql['respuestas_consultar']) ) {
			$sql = str_replace( '{{id}}', $this->uid, $this->sql['respuestas_consultar'] );
			$resultado = $this->modelo->bd->consultarColeccion( $sql, 'respuestas', false );
			$estado = $resultado['estado'];
		}
		if ( $estado == 1 ) {
			$presentador = new PresentadorXml();
			$presentador->crearVista( $this->clase.'.xml', $this->ruta );
			$presentador->anexarDatos( $this->dto );
			$this->documento = $presentador->documento;
			$colores = $this->documento->xpath( "//cuestionario/colores" );
			if ( count($colores)>0 ) {
				$col = (string) $colores[0];
				$this->colores = explode(',', $col);
			}
			if ( $usar_participantes && isset($this->sql['participantes_listar']) ) {
				$sql = str_replace( '{{id}}', $this->uid, $this->sql['participantes_listar'] );
				$resultado = $this->modelo->bd->consultarColeccion( $sql, 'participantes', false );
				if ( isset($this->dto->resultados['participantes']) && count($this->dto->resultados['participantes'])>0 ) {
					foreach ( $this->dto->resultados['participantes'] as $caso ) {
						$cod = $caso['id'];
						$etiqueta = $caso['etiqueta'];
						$xml = simplexml_load_string( '<participante cod="'. $cod . '" etiqueta="' . $etiqueta .'" corta="' . $etiqueta .'" color="" />' );
						$origen = dom_import_simplexml( $xml );
						$destino = dom_import_simplexml( $this->documento );
						$destino->appendChild( $destino->ownerDocument->importNode( $origen, true ) );
					}
				}
			}
			$paginas = $this->documento->xpath( "//cuestionario/pagina" );
			$cfg['info'] = 'informe';
			foreach ( $paginas as $pagina ) {
				$cfg['pagina'] = (string) $pagina['id'];
				$cfg['vista'] = (string) $pagina['plantilla'];
				$titulo = (string) $pagina['titulo'];
				$nivel = (string) $pagina['nivel'];
				$grafico = (string) $pagina['grafico'];
				if ( strlen($grafico)>0 ) {
					//$this->crearGrafico( $grafico );
				}
				$texto = $presentador->Transformar( $this->clase.'.xsl', $this->ruta, $cfg );
				$contenido[] = array( 'titulo'=>$titulo, 'contenido'=>$texto, 'nivel'=>$nivel );
			}
			$cfg['carpeta'] = $this->carpeta;
			//$cfg['formato'] = \Almacen::F_PDF;
			$cfg['nombre'] = $this->uid;
			$cfg['papel'] = 'LETTER';
			$cfg['fuente'] = 'Arial';
			$cfg['margen_izq'] = 15;
			$cfg['margen_der'] = 15;
			$cfg['margen_sup'] = 20;
			$cfg['margen_inf'] = 20;
			$cfg['portada'] = true;
			$cfg['estilos'] = M::E('PUNTOFINAL/RUTA').'/pdf.css';
			$guardar = $this->modelo->almacen->guardarArchivo( \Almacen::PRIVADO, $contenido, $cfg );
			$estado = $guardar['estado'];
			if ( M::E('M_MODO')=='PRUEBA' ) {
				$this->documento->asXML( M::E('ALMACEN/PUBLICO').'/temp/prueba-'.$this->uid . '.xml' );
			} else {
				$this->modelo->almacen->borrarArchivos( $this->temp );
			}
		}
		if ( $estado == 1 ) {
			$mensaje = $this->T['informe-impreso'];
		} else {
			$mensaje = $this->T['informe-no-impreso'];
		}
		unset( $presentador );
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}

	protected function docXpath( $ruta = '' ) {
		$valor = '';
		if ( strlen($ruta)==0 ) { return $valor; }
		$nodo = $this->documento->xpath( $ruta );
		if ( count($nodo)>0 ) {
			$valor = (string) $nodo[0];
		}
		return $valor;
	}
}
?>
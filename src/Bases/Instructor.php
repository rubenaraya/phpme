<?php 
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IInstructor;
use MasExperto\ME\Finales\PresentadorXml;
use MasExperto\ME\M;

abstract class Instructor extends Adaptador implements IInstructor
{
	public $colores = array();
	public $indice = 0;
	protected $documento = null;
	protected $temp = array();
	protected $carpeta;

	function __construct() {
        parent::__construct();
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
		$opc['incluir'] = __DIR__ . '/Recursos/Instructor.xsl';
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
					$porcentaje = 'p'. $sid;
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
					$promedio = 'r'. $sid;
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
					$this->crearGrafico( $grafico );
				}
				$texto = $presentador->Transformar( $this->clase.'.xsl', $this->ruta, $cfg );
				$contenido[] = array( 'titulo'=>$titulo, 'contenido'=>$texto, 'nivel'=>$nivel );
			}
			$cfg['carpeta'] = $this->carpeta;
			$cfg['formato'] = Almacen::F_PDF;
			$cfg['nombre'] = $this->uid;
			$cfg['papel'] = 'LETTER';
			$cfg['fuente'] = 'Arial';
			$cfg['margen_izq'] = 15;
			$cfg['margen_der'] = 15;
			$cfg['margen_sup'] = 20;
			$cfg['margen_inf'] = 20;
			$cfg['portada'] = true;
			$cfg['estilos'] = M::E('RUTA/SERVICIO').'/pdf.css';
			$guardar = $this->modelo->almacen->guardarArchivo( Almacen::PRIVADO, $contenido, $cfg );
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
	protected function crearGrafico( $gid ) {
		//TODO: Revisar y depurar
		$graficos = $this->documento->xpath( "//cuestionario/grafico[@id='" . $gid . "']" );
		foreach ( $graficos as $grafico ) {
			$datos = array();
			$cfg = array();
			$columnas = array();
			$cfg['seleccion'] = array();
			if ( isset($grafico['seleccion']) ) {
				$seleccion = (string) $grafico['seleccion'];
				if ( strlen($seleccion)>0 ) {
					$cfg['seleccion'] = explode(',', $seleccion);
				}
			}
			$cfg['ruta'] = M::E('ALMACEN/PUBLICO') . '/temp';
			$cfg['imagen'] = strval($this->uid).'-'.$gid.'.jpg';
			$cfg['ancho'] = (string) $grafico['ancho'];
			$cfg['alto'] = (string) $grafico['alto'];
			$cfg['margenes'] = (string) $grafico['margenes'];
			$cfg['textox'] = (string) $grafico['textox'];
			$cfg['textoy'] = (string) $grafico['textoy'];
			$cfg['marcasx'] = (string) $grafico['marcasx'];
			$cfg['marcasy'] = (string) $grafico['marcasy'];
			$cfg['escalay'] = (string) $grafico['escalay'];
			$cfg['leyenda'] = (string) $grafico['leyenda'];
			$cfg['guiasx'] = (string) $grafico['guiasx'];
			$cfg['guiasy'] = (string) $grafico['guiasy'];
			$cfg['angulox'] = (string) $grafico['angulox'];
			$cfg['distanciax'] = (string) $grafico['distanciax'];
			$cfg['distanciay'] = (string) $grafico['distanciay'];
			$cfg['tipo'] = (string) $grafico['tipo'];
			$cfg['variable'] = (string) $grafico['variable'];
			$cfg['media'] = (string) $grafico['media'];
			$config = (string) $grafico['config'];
			$cruce = (string) $grafico['cruce'];
			$indicador = $this->documento->xpath( "//cuestionario/indicador[@id='".$cfg['variable']."']" );
			if ( strlen($cruce)>0 && substr_count($cfg['tipo'], 'SECCIONES')==0 ) {
				$columnas = $this->documento->xpath( "//cuestionario/categoria[@id='".$cruce."']/segmento" );
			}
			if ( $config == 'categoria' ) {
				$segmentos = $this->documento->xpath( "//cuestionario/categoria[@id='".$cfg['variable']."']/segmento" );
				$this->recuentoItem( $datos, $cfg, $segmentos, $columnas );
			} elseif ( $config == 'item' ) {
				$segmentos = $this->documento->xpath( "//cuestionario//seccion/grupo/item[@id='".$cfg['variable']."']/opcion" );
				$this->recuentoItem( $datos, $cfg, $segmentos, $columnas );
			} elseif ( $config == 'conjunto' ) {
				$segmentos = $this->documento->xpath( "//cuestionario//seccion/grupo[@id='".$cfg['variable']."']/item" );
				$this->recuentoConjunto( $datos, $cfg, $segmentos, $columnas );
			} elseif ( $config == 'combinacion' ) {
				$segmentos = $this->documento->xpath( "//cuestionario//seccion/grupo[@id='".$cfg['variable']."']/item" );
				$columnas = $this->documento->xpath( "//cuestionario/escala[@id='".$cruce."']/opcion" );
				$this->recuentoCombinacion( $datos, $cfg, $segmentos, $columnas );
			} elseif ( $config == 'analisis' ) {
				$this->recalculoAnalisis( $datos, $cfg, $indicador, $columnas );
			} elseif ( $config == 'resumen' ) {
				$this->recalculoResumen( $datos, $cfg, $indicador, $columnas );
			} elseif ( $config == 'sintesis' ) {
				$this->recalculoSintesis( $datos, $cfg, $indicador, $columnas );
			}
			$this->graficarDatos( $datos, $cfg );
		}
	}
	protected function recuentoCombinacion( &$datos, &$cfg, $segmentos, $columnas ) {
		//TODO: Revisar y depurar
		$colores = array();
		$leyendas = array();
		$etiquetas = array();
		$this->indice = 0;
		if ( is_array($columnas) && count($columnas)>0 && is_array($segmentos) && count($segmentos)>0 ) {
			foreach ( $columnas as $columna ) {
				$cod = (string) $columna['cod'];
				$color = (string) $columna['color'];
				if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
				if ( strlen($color)==0 ) { $color = 'black'; }
				if ( count($cfg['seleccion'])==0 || (count($cfg['seleccion'])>0 && in_array($cod, $cfg['seleccion']))) {
					$serie = array();
					$colores[] = $color;
					$leyendas[] = (string) $columna['etiqueta'];
					foreach ( $segmentos as $segmento ) {
						$variable = (string) $segmento['id'];
						$buscar = $this->documento->xpath( "//resultados[@grupo='recuentos']/elemento[item='" . $variable . "'][opcion='" . $cod . "']" );
						if ( count($buscar)>0 ) {
							$cuenta = $buscar[0]->xpath('total');
							$serie[] = (int) $cuenta[0];
						} else {
							$serie[] = 0;
						}
					}
					$datos[] = $serie;
				}
			}
			foreach ( $segmentos as $segmento ) {
				$etiquetas[] = (string) $segmento['enunciado'];
			}
			$cfg['etiquetasx'] = $etiquetas;
			$datos = array_reverse($datos);
			$cfg['colores'] = array_reverse($colores);
			$cfg['leyendas'] = array_reverse($leyendas);
		}
	}
	protected function recuentoConjunto( &$datos, &$cfg, $segmentos, $columnas ) {
		//TODO: Revisar y depurar
		$colores = array();
		$leyendas = array();
		$etiquetas = array();
		$marcador = array();
		$this->indice = 0;
		if ( is_array($columnas) && count($columnas)>0 ) {
			foreach ( $columnas as $columna ) {
				$campo = 'c' . (string) $columna['id'];
				$valor = (string) $columna['valor'];
				if ( count($cfg['seleccion'])==0 || (count($cfg['seleccion'])>0 && in_array($valor, $cfg['seleccion']))) {
					$serie = array();
					$marca = false;
					foreach ( $segmentos as $segmento ) {
						$variable = (string) $segmento['id'];
						$buscar = $this->documento->xpath( "//resultados[@grupo='recuentos']/elemento[item='" . $variable . "'][opcion='1']" );
						if ( count($buscar)>0 ) {
							$cuenta = $buscar[0]->xpath($campo);
							$serie[] = (int) $cuenta[0];
							$marca = true;
							$marcador[$variable] = true;
						}
					}
					if ( $marca ) {
						$datos[] = $serie;
						$color = (string) $columna['color'];
						if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
						if ( strlen($color)==0 ) { $color = 'black'; }
						$colores[] = $color;
						$leyendas[] = (string) $columna['etiqueta'];
					}
				}
			}
			foreach ( $segmentos as $segmento ) {
				$variable = (string) $segmento['id'];
				if ( isset($marcador[$variable]) && $marcador[$variable]==true ) {
					$etiqueta = (string) $segmento['recuento'];
					if ( strlen($etiqueta)==0 ) {
						$etiqueta = (string) $segmento['enunciado'];
					}
					$etiquetas[] = $etiqueta;
				}
			}
			$datos = array_reverse($datos);
			$cfg['colores'] = array_reverse($colores);
			$cfg['leyendas'] = array_reverse($leyendas);
			$cfg['etiquetasx'] = $etiquetas;
		} else {
			$serie = array();
			foreach ( $segmentos as $segmento ) {
				$variable = (string) $segmento['id'];
				$buscar = $this->documento->xpath( "//resultados[@grupo='recuentos']/elemento[item='" . $variable . "'][opcion='1']" );
				if ( count($buscar)>0 ) {
					$cuenta = $buscar[0]->xpath('total');
					$serie[] = (int) $cuenta[0];
					$etiqueta = (string) $segmento['recuento'];
					if ( strlen($etiqueta)==0 ) {
						$etiqueta = (string) $segmento['enunciado'];
					}
					$leyendas[] = $etiqueta;
					$etiquetas[] = $etiqueta;
					$color = (string) $segmento['color'];
					if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
					if ( strlen($color)==0 ) { $color = 'black'; }
					$colores[] = $color;
				}
			}
			$datos[] = $serie;
			$cfg['colores'] = $colores;
			$cfg['leyendas'] = $leyendas;
			$cfg['etiquetasx'] = $etiquetas;
		}
	}
	protected function recuentoItem( &$datos, &$cfg, $segmentos, $columnas ) {
		//TODO: Revisar y depurar
		$colores = array();
		$leyendas = array();
		$etiquetas = array();
		$marcador = array();
		$this->indice = 0;
		if ( !is_array($segmentos) || count($segmentos)==0 ) {
			if ( $cfg['variable']=='idgrupo' ) {
				$segmentos = $this->documento->xpath( "//cuestionario/participante" );
			} else {
				$elemento = $this->documento->xpath( "//cuestionario//seccion/grupo/item[@id='".$cfg['variable']."']" );
				if ( count($elemento)>0 ) {
					$forma = $elemento[0]['forma'];
					$segmentos = $this->documento->xpath( "//cuestionario/escala[@id='".$forma."']/opcion" );
					if ( !is_array($segmentos) || count($segmentos)==0 ) {
						$elemento = $this->documento->xpath( "//resultados[@grupo='recuentos']/elemento[item='" . $cfg['variable'] . "']" );
						foreach ( $elemento as $caso ) {
							$cod = $caso->opcion;
							$xml = simplexml_load_string( '<recuento item="' . $cfg['variable'] . '" cod="'. $cod . '" etiqueta="' . $cod .'" corta="' . $cod .'" color="" />' );
							$origen = dom_import_simplexml( $xml );
							$destino = dom_import_simplexml( $this->documento );
							$destino->appendChild( $destino->ownerDocument->importNode( $origen, true ) );
						}
						$segmentos = $this->documento->xpath( "//cuestionario/recuento[@item='" . $cfg['variable'] . "']" );
					}
				}
			}
		}
		if ( is_array($columnas) && count($columnas)>0 ) {
			foreach ( $columnas as $columna ) {
				$campo = 'c' . (string) $columna['id'];
				$valor = (string) $columna['valor'];
				if ( count($cfg['seleccion'])==0 || (count($cfg['seleccion'])>0 && in_array($valor, $cfg['seleccion']))) {
					$serie = array();
					$marca = false;
					foreach ( $segmentos as $segmento ) {
						$valor = (string) $segmento['cod'];
						if ( strlen($valor)==0 ) { $valor = (string) $segmento['valor']; }
						$buscar = $this->documento->xpath( "//resultados[@grupo='recuentos']/elemento[item='" . $cfg['variable'] . "'][opcion='" . $valor . "']" );
						if ( count($buscar)>0 ) {
							$cuenta = $buscar[0]->xpath($campo);
							$serie[] = (int) $cuenta[0];
							$marca = true;
							$marcador[$valor] = true;
						}
					}
					if ( $marca ) {
						$datos[] = $serie;
						$color = (string) $columna['color'];
						if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
						if ( strlen($color)==0 ) { $color = 'black'; }
						$colores[] = $color;
						$leyendas[] = (string) $columna['etiqueta'];
					}
				}
			}
			foreach ( $segmentos as $segmento ) {
				$valor = (string) $segmento['cod'];
				if ( strlen($valor)==0 ) { $valor = (string) $segmento['valor']; }
				if ( isset($marcador[$valor]) && $marcador[$valor]==true ) {
					$etiqueta = (string) $segmento['corta'];
					if ( strlen($etiqueta)==0 ) {
						$etiqueta = (string) $segmento['etiqueta'];
					}
					$etiquetas[] = $etiqueta;
				}
			}
			$datos = array_reverse($datos);
			$cfg['colores'] = array_reverse($colores);
			$cfg['leyendas'] = array_reverse($leyendas);
			$cfg['etiquetasx'] = $etiquetas;
		} else {
			$serie = array();
			foreach ( $segmentos as $segmento ) {
				$valor = (string) $segmento['cod'];
				if ( strlen($valor)==0 ) { $valor = (string) $segmento['valor']; }
				$buscar = $this->documento->xpath( "//resultados[@grupo='recuentos']/elemento[item='" . $cfg['variable'] . "'][opcion='" . $valor . "']" );
				if ( count($buscar)>0 ) {
					$cuenta = $buscar[0]->xpath('total');
					$serie[] = (int) $cuenta[0];
					$etiqueta = (string) $segmento['corta'];
					if ( strlen($etiqueta)==0 ) {
						$etiqueta = (string) $segmento['etiqueta'];
					}
					$leyendas[] = $etiqueta;
					$etiquetas[] = $etiqueta;
					$color = (string) $segmento['color'];
					if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
					if ( strlen($color)==0 ) { $color = 'black'; }
					$colores[] = $color;
				}
			}
			$datos[] = $serie;
			$cfg['colores'] = $colores;
			$cfg['leyendas'] = $leyendas;
			$cfg['etiquetasx'] = $etiquetas;
		}
	}
	protected function recalculoAnalisis( &$datos, &$cfg, $indicador, $columnas ) {
		//TODO: Revisar y depurar
		$colores = array();
		$leyendas = array();
		$etiquetasx = array();
		$etiquetasy = array();
		$marcador = array();
		$this->indice = 0;
		$id = (string) $indicador[0]['id'];
		$componentes = (string) $indicador[0]['componentes'];
		$escala = (string) $indicador[0]['escala'];
		$cfg['puntos'] = (string) $indicador[0]['puntos'];
		if ( strlen($componentes)>0 ) {
			$segmentos = explode(',', $componentes);
			if ( is_array($columnas) && count($columnas)>0 ) {
				foreach ( $columnas as $columna ) {
					$campo = 'r' . (string) $columna['id'];
					$valor = (string) $columna['valor'];
					if ( count($cfg['seleccion'])==0 || (count($cfg['seleccion'])>0 && in_array($valor, $cfg['seleccion']))) {
						$serie = array();
						$marca = false;
						foreach ($segmentos as $segmento) {
							$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $segmento . "']" );
							if ( count($buscar)>0 ) {
								$promedio = $buscar[0]->xpath($campo);
								$serie[] = (float) $promedio[0];
								$marca = true;
								$marcador[$segmento] = true;
							}
						}
						if ( $marca ) {
							$datos[] = $serie;
							$color = '';
							if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
							if ( strlen($color)==0 ) { $color = 'black'; }
							$colores[] = $color;
							$leyendas[] = (string) $columna['etiqueta'];
						}
					}
				}
				foreach ($segmentos as $segmento) {
					if ( isset($marcador[$segmento]) && $marcador[$segmento]==true ) {
						$leer = $this->documento->xpath( "//seccion/grupo/item[@id='" . $segmento . "']" );
						if ( count($leer)>0 ) {
							$etiqueta = (string) $leer[0]['calculo'];
						} else {
							$etiqueta = $segmento;
						}
						$etiquetasx[] = $etiqueta;
					}
				}
			} else {
				$serie = array();
				foreach ($segmentos as $segmento) {
					$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $segmento . "']" );
					if ( count($buscar)>0 ) {
						$promedio = $buscar[0]->xpath('promedio');
						$serie[] = (float) $promedio[0];
						$leer = $this->documento->xpath( "//seccion/grupo/item[@id='" . $segmento . "']" );
						if ( count($leer)>0 ) {
							$etiqueta = (string) $leer[0]['calculo'];
						} else {
							$etiqueta = $segmento;
						}
						$color = '';
						if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
						if ( strlen($color)==0 ) { $color = 'black'; }
						$leyendas[] = $etiqueta;
						$etiquetasx[] = $etiqueta;
						$colores[] = $color;
					}
				}
			}
			$buscar = $this->documento->xpath( "//cuestionario/escala[@id='".$escala."']/opcion" );
			if ( count($buscar)>0 ) {
				foreach ( $buscar as $elemento ) {
					if ( isset($elemento['corto']) ) {
						$etiquetasy[] = (string) $elemento['corto'];
					} else {
						$etiquetasy[] = (string) $elemento['etiqueta'];
					}
				}
			}
			$buscar = $this->documento->xpath( "//cuestionario/indicador[@id='".$id."']/semaforo" );
			if ( count($buscar)>0 ) {
				foreach ( $buscar as $elemento ) {
					$cid = (string) $elemento['id'];
					$cfg[$cid]['color'] = (string) $elemento['color'];
					$cfg[$cid]['desde'] = (string) $elemento['desde'];
					$cfg[$cid]['hasta'] = (string) $elemento['hasta'];
				}
			}
			if ( !is_array($columnas) || count($columnas)==0 ) {
				$datos[] = $serie;
			}
			$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $id . "']/promedio" );
			if ( count($buscar)>0 ) {
				$cfg['promedio'] = (float) $buscar[0];
			}
			$cfg['colores'] = $colores;
			$cfg['leyendas'] = $leyendas;
			$cfg['etiquetasx'] = $etiquetasx;
			$cfg['etiquetasy'] = array_reverse($etiquetasy);
		}
	}
	protected function recalculoResumen( &$datos, &$cfg, $indicador, $columnas ) {
		//TODO: Revisar y depurar
		$colores = array();
		$etiquetasx = array();
		$etiquetasy = array();
		$this->indice = 0;
		$id = (string) $indicador[0]['id'];
		$escala = (string) $indicador[0]['escala'];
		$cfg['puntos'] = (string) $indicador[0]['puntos'];
		if ( is_array($columnas) && count($columnas)>0 ) {
			$serie = array();
			foreach ( $columnas as $columna ) {
				$campo = 'r' . (string) $columna['id'];
				$valor = (string) $columna['valor'];
				if ( count($cfg['seleccion'])==0 || (count($cfg['seleccion'])>0 && in_array($valor, $cfg['seleccion']))) {
					$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $id . "']" );
					if ( count($buscar)>0 ) {
						$promedio = $buscar[0]->xpath($campo);
						$serie[] = (float) $promedio[0];
						$color = (string) $columna['color'];
						if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
						if ( strlen($color)==0 ) { $color = 'black'; }
						$colores[] = $color;
						$etiquetasx[] = (string) $columna['etiqueta'];
					}
				}
			}
			$buscar = $this->documento->xpath( "//cuestionario/escala[@id='".$escala."']/opcion" );
			if ( count($buscar)>0 ) {
				foreach ( $buscar as $elemento ) {
					if ( isset($elemento['corto']) ) {
						$etiquetasy[] = (string) $elemento['corto'];
					} else {
						$etiquetasy[] = (string) $elemento['etiqueta'];
					}
				}
			}
			$buscar = $this->documento->xpath( "//cuestionario/indicador[@id='".$id."']/semaforo" );
			if ( count($buscar)>0 ) {
				foreach ( $buscar as $elemento ) {
					$cid = (string) $elemento['id'];
					$cfg[$cid]['color'] = (string) $elemento['color'];
					$cfg[$cid]['desde'] = (string) $elemento['desde'];
					$cfg[$cid]['hasta'] = (string) $elemento['hasta'];
				}
			}
			$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $id . "']/promedio" );
			if ( count($buscar)>0 ) {
				$cfg['promedio'] = (float) $buscar[0];
			}
			$datos[] = $serie;
			$cfg['colores'] = $colores;
			$cfg['etiquetasx'] = $etiquetasx;
			$cfg['etiquetasy'] = array_reverse($etiquetasy);
		}
	}
	protected function recalculoSintesis( &$datos, &$cfg, $indicador, $columnas ) {
		//TODO: Revisar y depurar
		$colores = array();
		$leyendas = array();
		$etiquetasx = array();
		$etiquetasy = array();
		$marcador = array();
		$this->indice = 0;
		$id = (string) $indicador[0]['id'];
		$escala = (string) $indicador[0]['escala'];
		$cfg['puntos'] = (string) $indicador[0]['puntos'];
		$segmentos = $this->documento->xpath( "//cuestionario/indicador[@antes='".$id."']" );
		if ( is_array($columnas) && count($columnas)>0 ) {
			foreach ( $columnas as $columna ) {
				$campo = 'r' . (string) $columna['id'];
				$valor = (string) $columna['valor'];
				if ( count($cfg['seleccion'])==0 || (count($cfg['seleccion'])>0 && in_array($valor, $cfg['seleccion']))) {
					$serie = array();
					$marca = false;
					foreach ($segmentos as $segmento) {
						$sid = (string) $segmento['id'];
						$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $sid . "']" );
						if ( count($buscar)>0 ) {
							$promedio = $buscar[0]->xpath($campo);
							$serie[] = (float) $promedio[0];
							$marca = true;
							$marcador[$sid] = true;
						}
					}
					if ( $marca ) {
						$datos[] = $serie;
						$color = (string) $segmento['color'];
						if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
						if ( strlen($color)==0 ) { $color = 'black'; }
						$colores[] = $color;
						$leyendas[] = (string) $columna['etiqueta'];
					}
				}
			}
			foreach ($segmentos as $segmento) {
				$sid = (string) $segmento['id'];
				if ( isset($marcador[$sid]) && $marcador[$sid]==true ) {
					$etiquetasx[] = (string) $segmento['nombre'];
				}
			}
		} else {
			$serie = array();
			foreach ($segmentos as $segmento) {
				$sid = (string) $segmento['id'];
				$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $sid . "']" );
				if ( count($buscar)>0 ) {
					$promedio = $buscar[0]->xpath('promedio');
					$serie[] = (float) $promedio[0];
					$color = (string) $segmento['color'];
					if ( strlen($color)==0 ) { $color = $this->colores[$this->indice]; $this->indice++; }
					if ( strlen($color)==0 ) { $color = 'black'; }
					$colores[] = $color;
					$etiquetasx[] = (string) $segmento['nombre'];
					$leyendas[] = (string) $segmento['nombre'];
				}
			}
		}
		$buscar = $this->documento->xpath( "//cuestionario/escala[@id='".$escala."']/opcion" );
		if ( count($buscar)>0 ) {
			foreach ( $buscar as $elemento ) {
				if ( isset($elemento['corto']) ) {
					$etiquetasy[] = (string) $elemento['corto'];
				} else {
					$etiquetasy[] = (string) $elemento['etiqueta'];
				}
			}
		}
		$buscar = $this->documento->xpath( "//cuestionario/indicador[@id='".$id."']/semaforo" );
		if ( count($buscar)>0 ) {
			foreach ( $buscar as $elemento ) {
				$cid = (string) $elemento['id'];
				$cfg[$cid]['color'] = (string) $elemento['color'];
				$cfg[$cid]['desde'] = (string) $elemento['desde'];
				$cfg[$cid]['hasta'] = (string) $elemento['hasta'];
			}
		}
		$buscar = $this->documento->xpath( "//resultados[@grupo='recalculos']/elemento[elemento='" . $id . "']/promedio" );
		if ( count($buscar)>0 ) {
			$cfg['promedio'] = (float) $buscar[0];
		}
		if ( !is_array($columnas) || count($columnas)==0 ) {
			$datos[] = $serie;
		}
		$cfg['colores'] = $colores;
		$cfg['leyendas'] = $leyendas;
		$cfg['etiquetasx'] = $etiquetasx;
		$cfg['etiquetasy'] = array_reverse($etiquetasy);
	}
	protected function graficarDatos( &$datos, $cfg ) {
		//TODO: Revisar y depurar
		$ruta = ( isset($cfg['ruta']) ? $cfg['ruta'] : '' );
		$imagen = ( isset($cfg['imagen']) ? $cfg['imagen'] : '' );
		$leyendas = ( isset($cfg['leyendas']) ? $cfg['leyendas'] : array() );
		$colores = ( isset($cfg['colores']) ? $cfg['colores'] : array() );
		$etiquetasx = ( isset($cfg['etiquetasx']) ? $cfg['etiquetasx'] : array() );
		$etiquetasy = ( isset($cfg['etiquetasy']) ? $cfg['etiquetasy'] : array() );
		$ancho = ( isset($cfg['ancho']) ? $cfg['ancho'] : 640 );
		$alto = ( isset($cfg['alto']) ? $cfg['alto'] : 480 );
		$textox = ( isset($cfg['textox']) ? $cfg['textox'] : '' );
		$textoy = ( isset($cfg['textoy']) ? $cfg['textoy'] : '' );
		$marcasx = ( isset($cfg['marcasx']) ? $cfg['marcasx'] : '' );
		$marcasy = ( isset($cfg['marcasy']) ? $cfg['marcasy'] : '' );
		$escalay = ( isset($cfg['escalay']) ? $cfg['escalay'] : '' );
		$leyenda = ( isset($cfg['leyenda']) ? $cfg['leyenda'] : '' );
		$guiasx = ( isset($cfg['guiasx']) ? $cfg['guiasx'] : '' );
		$guiasy = ( isset($cfg['guiasy']) ? $cfg['guiasy'] : '' );
		$angulox = ( isset($cfg['angulox']) ? $cfg['angulox'] : 0 );
		$distanciax = ( isset($cfg['distanciax']) ? $cfg['distanciax'] : 20 );
		$distanciay = ( isset($cfg['distanciay']) ? $cfg['distanciay'] : 30 );
		$margenes = ( isset($cfg['margenes']) ? $cfg['margenes'] : '10,10,10,10' );
		$puntos = ( isset($cfg['puntos']) ? $cfg['puntos'] : '' );
		$media = ( isset($cfg['media']) ? $cfg['media'] : '' );
		$promedio = ( isset($cfg['promedio']) ? $cfg['promedio'] : 0 );
		$valores = array();
		$ejex = true;
		$ejey = true;
		if ( $marcasx == '-' ) {
			$marcasx = '';
			$ejex = false;
		}
		if ( $marcasy == '-' ) {
			$marcasy = '';
			$ejey = false;
		}
		if ( strlen($escalay)>0 ) {
			$valores = explode(',', strval($escalay));
		}
		$gra = M::E('CONECTOR/GRAFICOS');
		$conector = ( strlen($gra)>0 ? $gra : '\MasExperto\ME\Finales\GraficadorJp');
		$graficador = new $conector;
		$graficador->cambiarFuente('arial');
		$matriz = explode(',', strval($margenes));
		if ( count($matriz)==4 ) {
			$graficador->grafico['margenes']['izq'] = intval($matriz[0]);
			$graficador->grafico['margenes']['sup'] = intval($matriz[1]);
			$graficador->grafico['margenes']['der'] = intval($matriz[2]);
			$graficador->grafico['margenes']['inf'] = intval($matriz[3]);
		}
		$graficador->grafico['ancho'] = intval($ancho);
		$graficador->grafico['alto'] = intval($alto);
		$graficador->series['colores'] = $colores;
		$graficador->series['leyendas'] = $leyendas;
		$graficador->ejeX['etiquetas'] = $etiquetasx;
		$graficador->ejeY['etiquetas'] = $etiquetasy;
		$graficador->grafico['ruta'] = $ruta;
		$graficador->grafico['imagen'] = $imagen;
		$graficador->ejeX['titulo']['texto'] = $textox;
		$graficador->ejeY['titulo']['texto'] = $textoy;
		$graficador->ejeX['marcas'] = $marcasx;
		$graficador->ejeY['marcas'] = $marcasy;
		$graficador->ejeX['cuadricula'] = $guiasx;
		$graficador->ejeY['cuadricula'] = $guiasy;
		$graficador->ejeX['angulox'] = $angulox;
		$graficador->leyenda['posicion'] = $leyenda;
		$graficador->ejeX['titulo']['margen'] = intval($distanciax);
		$graficador->ejeY['titulo']['margen'] = intval($distanciay);
		$graficador->ejeY['linea'] = $ejey;
		$graficador->ejeX['linea'] = $ejex;
		$this->temp[] = $graficador->grafico['ruta'] . '/' . $imagen;
		if ( is_array($valores) && count($valores) >0 ) {
			$graficador->ejeY['valores'] = $valores;
			$graficador->ejeY['max'] = max($valores);
		} else {
			$graficador->ejeY['min'] = 0;
			$graficador->ejeY['max'] = 0;
		}
		switch ( $cfg['tipo'] ) {
			case 'SECCIONES': 
			case 'SECCIONES-3D': 
				$graficador->grafico['suavizado'] = true;
				$graficador->grafico['guias'] = true;
				$graficador->grafico['sombra'] = true;
				$graficador->grafico['separar'] = 'todos';
				$graficador->series['puntos']['formato'] = '%s: %.0f%%';
				if ( $cfg['tipo']=='SECCIONES-3D' ) { $graficador->grafico['3D'] = true; }
				$graficador->graficarSecciones( $datos );
				break;
			case 'BARRAS': 
			case 'BARRAS-EV': 
			case 'BARRAS-AC': 
				$graficador->leyenda['linea'] = 1;
				if ( strlen($puntos)>0 ) {
					$graficador->series['puntos']['visible'] = true;
					$graficador->series['puntos']['formato'] = $puntos;
					$graficador->series['puntos']['margen'] = 20;
					$graficador->series['puntos']['tamaño'] = 11;
				}
				if ( $cfg['tipo']=='BARRAS-AC' ) { $graficador->grafico['AC'] = true; }
				if ( $cfg['tipo']=='BARRAS-EV' ) {
					if ( isset($cfg['R']) && isset($cfg['A']) && isset($cfg['V']) ) {
						$graficador->series['semaforo'] = array( 
							$cfg['R']['color'] => array( floatval($cfg['R']['desde']), floatval($cfg['R']['hasta'].'9999'), $cfg['R']['desde'] . ' - ' . $cfg['R']['hasta'] ), 
							$cfg['A']['color'] => array( floatval($cfg['A']['desde']), floatval($cfg['A']['hasta'].'9999'), $cfg['A']['desde'] . ' - ' . $cfg['A']['hasta'] ), 
							$cfg['V']['color'] => array( floatval($cfg['V']['desde']), floatval($cfg['V']['hasta'].'9999'), $cfg['V']['desde'] . ' - ' . $cfg['V']['hasta'] ) 
						);
					}
				}
				if ( strlen($media)>0 ) {
					$matriz = explode(',', $media);
					if ( count($matriz)==2 ) {
						$graficador->series['linea']['color'] = $matriz[1];
						$graficador->series['linea']['leyenda'] = $matriz[0];
						$graficador->agregarValores( array(floatval($promedio)), $graficador->series['linea']['valores'] );
					}
				}
				$graficador->graficarBarras( $datos );
				break;
			case 'LINEAS':
				$graficador->graficarLineas( $datos );
				break;
		}
		unset( $graficador );
	}
}
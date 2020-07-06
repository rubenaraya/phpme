<?php 
namespace MasExperto\ME\Finales;

use MasExperto\ME\Bases\Graficador;
use MasExperto\ME\M;
use AccBarPlot;
use BarPlot;
use Graph;
use GroupBarPlot;
use LinePlot;
use PieGraph;
use PiePlot;
use PiePlot3D;
use ScatterPlot;

final class GraficadorJp extends Graficador {

	public function Graficar( $tipo, &$datos ) {
		switch ( $tipo ) {
			case Graficador::G_BARRAS:
				return $this->graficarBarras( $datos );
				break;
			case Graficador::G_UBICACIONES:
				return $this->graficarUbicaciones( $datos );
				break;
			case Graficador::G_SECCIONES:
				return $this->graficarSecciones( $datos );
				break;
			case Graficador::G_LINEAS:
				return $this->graficarLineas( $datos );
				break;
		}
	}

	public function graficarBarras( &$datos ) {
		$resultado = array();
		$resultado['imagen'] = null;
		$resultado['error'] = null;
		$resultado['estado'] = false;
		$serie = null;
		$colores = array();
		if ( !is_array($datos) || count($datos)==0 ) { 
			$resultado['error'] = dgettext('me', 'No-hay-datos-para-graficar');
			return $resultado; 
		}
		$semaforo = is_array($this->series['semaforo']) && count($this->series['semaforo'])>0;
		$leyenda = strlen($this->leyenda['posicion'])>0;
		$imagen = $this->_establecerImagen();
		$grafico = new Graph( $this->grafico['ancho'], $this->grafico['alto'] );
		$this->_configurarGrafico( $grafico, Graficador::G_BARRAS );
		foreach ( $datos as $key => $value ) {
			if ( is_numeric($key) ) {
				$serie[$key] = new BarPlot( $value );
				if ( $semaforo && is_array($value) ) {
					foreach ( $value as $pos => $valor ) {
						if ( is_numeric($valor) ) {
							foreach ( $this->series['semaforo'] as $color => $rango ) {
								if ( 
									round(floatval($valor),1) >= floatval($rango[0]) && 
									round(floatval($valor),1) < floatval($rango[1]) 
								) {
									$colores[$key][$pos] = $color; 
									break;
								}
							}
						}
					}
				} else {
					if ( $leyenda && isset( $this->series['leyendas'][$key] ) ) {
						$serie[$key]->SetLegend( $this->series['leyendas'][$key] );
					}
				}
			}
		}
		if ( !$semaforo ) { $colores = $this->series['colores']; }
		if ( is_array($datos) && is_array($colores) && ( count($datos) == count($colores) || count($datos)==1 ) ) {
			if ( $leyenda && $semaforo ) {
				foreach ( $this->series['semaforo'] as $color => $rango ) {
					$aux = new BarPlot( array(0) );
					$grafico->Add( $aux );
					$aux->SetFillColor( $color );
					if ( count($rango)==3 ) {
						$aux->SetLegend( $rango[2] );
					}
				}		
			}
			if ( $this->grafico['AC'] ) {
				$grafico->Add( new AccBarPlot( $serie ) );
			} else {
				$grafico->Add( new GroupBarPlot( $serie ) );
			}
			foreach ( $datos as $key => $value ) {
				if ( strlen($this->series['borde'])>0 ) {
					$serie[$key]->SetColor( $this->series['borde'] );
				} else {
					$serie[$key]->SetColor( $colores[$key] );
				}
				if ( count($datos)==1 && count($colores)>1 ) {
					$serie[$key]->SetFillColor( $colores );
				} else {
					$serie[$key]->SetFillColor( $colores[$key] );
				}
				if ( $this->series['puntos']['visible'] ) {
					$serie[$key]->value->Show(); 
				}
				$serie[$key]->value->SetColor( $this->series['puntos']['color'] );
				if ( strlen($this->series['puntos']['formato'])>0 ) {
					$serie[$key]->value->SetFormat( $this->series['puntos']['formato'] );
				}
				$serie[$key]->value->SetFont( 
					$this->_asignarFuente( $this->series['puntos']['fuente'] ), 
					$this->_asignarAspecto( $this->series['puntos']['aspecto'] ), 
					$this->series['puntos']['tamaño'] 
				);
				$serie[$key]->value->SetMargin( $this->series['puntos']['margen'] );
			}

			if ( is_array($this->series['linea']['valores']) && count($this->series['linea']['valores'])>0 ) {
				$lista = ( count($this->series['linea']['valores']) != count( $datos[0] ) ? 
					array_fill( 0, count( $datos[0] ), $this->series['linea']['valores'][0] ) : 
					$this->series['linea']['valores'] 
				);
				$trazo = new LinePlot( $lista );
				$grafico->Add( $trazo );
				if ( $leyenda && strlen($this->series['linea']['leyenda'])>0 ) { 
					$trazo->SetLegend( $this->series['linea']['leyenda'] ); 
				}
				$trazo->SetBarCenter();
				$trazo->SetStyle( $this->series['linea']['estilo'] );
				$trazo->SetColor( $this->series['linea']['color'] );
			}
			$grafico->Stroke( $imagen );
			$resultado['imagen'] = $imagen;
			$resultado['estado'] = true;
		} else {
			$resultado['error'] = dgettext('me', 'Los-parametros-del-grafico-no-son-correctos');
		}
		unset( $serie, $grafico, $trazo );
		return $resultado;
	}

	public function graficarLineas( &$datos ) {
		$resultado = array();
		$resultado['imagen'] = null;
		$resultado['error'] = null;
		$resultado['estado'] = false;
		$serie = null;
		if ( !is_array($datos) || count($datos)==0 ) { 
			$resultado['error'] = dgettext('me', 'No-hay-datos-para-graficar');
			return $resultado; 
		}
		$leyenda = strlen($this->leyenda['posicion'])>0;
		$imagen = $this->_establecerImagen();
		$grafico = new Graph( $this->grafico['ancho'], $this->grafico['alto'] );
		$this->_configurarGrafico( $grafico, Graficador::G_LINEAS );
		foreach ( $datos as $key => $value ) {
			if ( is_numeric($key) && is_array($value) ) {
				$serie[$key] = new LinePlot( $value );
				$grafico->Add( $serie[$key] );
				$serie[$key]->SetCenter();
				$serie[$key]->SetWeight( $this->series['grosor'] );
				$serie[$key]->mark->SetWidth( $this->series['marcas']['ancho'] );
				$serie[$key]->mark->SetType( $this->_asignarMarca( $this->series['marcas']['figura'] ) );
				if ( isset($this->series['colores'][$key]) ) {
					if ( strlen( $this->series['colores'][$key] )==0 ) {
						$this->series['colores'][$key] = 'white@1.0';
					}
					$serie[$key]->SetColor( $this->series['colores'][$key] );
					$color = ( strlen($this->series['marcas']['borde'])>0 ? 
						$this->series['marcas']['borde'] :
						$this->series['colores'][$key]
					);
					$relleno = ( strlen($this->series['marcas']['relleno'])>0 ? 
						$this->series['marcas']['relleno'] :
						$this->series['colores'][$key]
					);
					$serie[$key]->mark->SetColor( $color );
					$serie[$key]->mark->SetFillColor( $relleno );
				}
				$serie[$key]->value->SetColor( $this->series['puntos']['color'] );
				if ( strlen($this->series['puntos']['formato'])>0 ) {
					$serie[$key]->value->SetFormat( $this->series['puntos']['formato'] );
				}
				$serie[$key]->value->SetFont( 
					$this->_asignarFuente( $this->series['puntos']['fuente'] ), 
					$this->_asignarAspecto( $this->series['puntos']['aspecto'] ), 
					$this->series['puntos']['tamaño'] 
				);
				$serie[$key]->value->SetMargin( $this->series['puntos']['margen'] );
				if ( $this->series['puntos']['visible'] ) {
					$serie[$key]->value->Show(); 
				}
				if ( $leyenda && isset( $this->series['leyendas'][$key] ) ) {
					$serie[$key]->SetLegend( $this->series['leyendas'][$key] );
				}
			}
		}
		if ( is_array($serie) && is_array($this->series['colores']) && count($serie) == count($this->series['colores']) ) {
			$grafico->Stroke( $imagen );
			$resultado['imagen'] = $imagen;
			$resultado['estado'] = true;
		} else {
			$resultado['error'] = dgettext('me', 'Los-parametros-del-grafico-no-son-correctos');
		}
		unset( $serie, $grafico );
		return $resultado;
	}

	public function graficarUbicaciones( &$datos ) {
		$resultado = array();
		$resultado['imagen'] = null;
		$resultado['error'] = null;
		$resultado['estado'] = false;
		$serie = null;
		if ( !is_array($datos) || count($datos)!=2 ) { 
			$resultado['error'] = dgettext('me', 'No-hay-datos-para-graficar');
			return $resultado; 
		}
		$imagen = $this->_establecerImagen();
		$grafico = new Graph( $this->grafico['ancho'], $this->grafico['alto'] );
		$this->_configurarGrafico( $grafico, Graficador::G_UBICACIONES );
		$x = $datos[0];
		$y = $datos[1];
		$etiquetas = count($this->series['puntos']['etiquetas']);
		if ( is_array($x) && is_array($y) && count($x) == count($y) && $etiquetas > 0 ) {
			$fondos = $this->series['puntos']['fondos'];
			$letras = $this->series['puntos']['letras'];
			$angulos = $this->series['puntos']['angulos'];
			for ( $i = 0; $i < $etiquetas; $i++ ) {
				if ( count($fondos)>0 && count($letras)>0 && count($angulos)>0 ) {
					$angulo = ( count($angulos) >= ($i + 1) ? $angulos[$i] : 0 );
					$fondo = ( count($fondos) >= ($i + 1) ? $fondos[$i] : $fondos[0] );
					$letra = ( count($letras) >= ($i + 1) ? $letras[$i] : $letras[0] );
					$this->_crearEtiqueta( $x[$i], $y[$i], utf8_decode($this->series['puntos']['etiquetas'][$i]), $fondo, $letra, $angulo ); 
				} else {
					$this->_crearEtiqueta( $x[$i], $y[$i], utf8_decode($this->series['puntos']['etiquetas'][$i]) ); 
				}
			}
			$dibujo = new ScatterPlot( $y, $x );
			$dibujo->mark->SetType( MARK_IMG, M::E('RUTA/ME') . '/Recursos/nada.gif' );
			$dibujo->mark->SetCallbackYX( '\MasExperto\ME\GraficadorJp::mostrarEtiqueta' );
			$grafico->Add( $dibujo );
			$grafico->Stroke( $imagen );
			$resultado['imagen'] = $imagen;
			$resultado['estado'] = true;
			$this->borrarTemporales();
		} else {
			$resultado['error'] = dgettext('me', 'Los-parametros-del-grafico-no-son-correctos');
		}
		unset( $grafico, $dibujo );
		return $resultado;
	}

	public function graficarSecciones( &$datos ) {
		$resultado = array();
		$resultado['imagen'] = null;
		$resultado['error'] = null;
		$resultado['estado'] = false;
		if ( !is_array($datos) || count($datos)==0 ) { 
			$resultado['error'] = dgettext('me', 'No-hay-datos-para-graficar');
			return $resultado; 
		}
		//$leyenda = strlen($this->leyenda['posicion'])>0;
		$imagen = $this->_establecerImagen();
		$grafico = new PieGraph( $this->grafico['ancho'], $this->grafico['alto'] );
		$this->_configurarGrafico( $grafico, Graficador::G_SECCIONES );
		if ( is_array($datos[0]) && is_array($this->series['colores']) && count($datos[0])==count($this->series['colores']) ) {
			if ( $this->grafico['3D'] ) { $dibujo = new PiePlot3D( $datos[0] ); } 
			else { $dibujo = new PiePlot( $datos[0] ); }
			$dibujo->SetSize(0.3);
			if ( $this->grafico['sombra'] ) { $dibujo->SetShadow(); }
			if ( $this->grafico['guias'] && !$this->grafico['3D'] ) {
				$dibujo->SetGuideLines( true, true, true );
			}
			$dibujo->SetSliceColors( $this->series['colores'] );
			$dibujo->value->SetColor( $this->series['puntos']['color'] ); 
			$dibujo->value->SetFont( 
				$this->_asignarFuente( $this->series['puntos']['fuente'] ), 
				$this->_asignarAspecto( $this->series['puntos']['aspecto'] ), 
				$this->series['puntos']['tamaño'] 
			);
			$dibujo->SetLabelType( PIE_VALUE_ADJPER );
			if ( isset($this->series['leyendas']) && count($this->series['leyendas'])>0 ) { $this->ejeX['etiquetas'] = $this->series['leyendas']; }
			for ( $i = 0; $i < count($this->ejeX['etiquetas']); $i++ ) {
				$formato = ( strlen($this->series['puntos']['formato'])>0 ? $this->series['puntos']['formato'] : '' );
				if ( strlen($formato)>0 ) {
					$this->ejeX['etiquetas'][$i] = str_replace('%s', $this->ejeX['etiquetas'][$i], $formato);
				}
			}
			$dibujo->SetLabels( $this->ejeX['etiquetas'], 1 );
			if ( strlen($this->grafico['separar'])>0 ) {
				switch ( $this->grafico['separar'] ) {
					case 'mayor': 
						$indice = array_search( max( $datos[0] ), $datos[0] );
						$dibujo->ExplodeSlice( $indice );
						break;
					case 'todos': 
						$dibujo->ExplodeAll( 10 );
						break;
				}
			}
			$grafico->Add( $dibujo );
			$grafico->Stroke( $imagen );
			$resultado['imagen'] = $imagen;
			$resultado['estado'] = true;
		} else {
			$resultado['error'] = dgettext('me', 'Los-parametros-del-grafico-no-son-correctos');
		}
		unset( $dibujo, $grafico );
		return $resultado;
	}

	public function cambiarFuente( $fuente ) {
		if ( !in_array( $fuente, array('arial','verdana','times') ) ) { return false; }
		$this->ejeY['fuente'] = $fuente;
		$this->ejeY['titulo']['fuente'] = $fuente;
		$this->ejeX['fuente'] = $fuente;
		$this->ejeX['titulo']['fuente'] = $fuente;
		$this->leyenda['fuente'] = $fuente;
		$this->series['puntos']['fuente'] = $fuente;
		return true;
	}

	public function agregarValores( $valores, &$matriz ) {
		if ( !is_array($valores) || !is_array($matriz) ) { return false; }
		foreach ( $valores as $valor ) {
			array_push( $matriz, $valor );
		}
		return true;
	}

	public function borrarTemporales() {
		foreach ( $this->temporales as $archivo ) {
			if ( file_exists($archivo) && !is_dir($archivo) ) {
				@unlink( $archivo );
			}
		}
		return;
	}

	public static function mostrarEtiqueta( $y, $x ) {
		$imagen = M::E('ALMACEN/PRIVADO') . '/temp/im_' . M::E('M_USUARIO') . '_' . strval($x) . '_' . strval($y) . '.png';
		if ( file_exists( $imagen ) ) { return array( false, false, false, $imagen, 1 ); }
		return false;
	}

	private function _configurarGrafico( &$grafico, $tipo ) {
		if ( file_exists( $this->grafico['fondo'] ) && !is_dir( $this->grafico['fondo'] ) ) { 
			$grafico->SetBackgroundImage( $this->grafico['fondo'], BGIMG_COPY );
		}
		$grafico->ClearTheme();
		$grafico->SetFrame(false);
		$grafico->img->SetAntiAliasing( $this->grafico['suavizado'] );
		switch ( $tipo ) {
			case Graficador::G_BARRAS:
			case Graficador::G_LINEAS:
			case Graficador::G_SECCIONES:
			case Graficador::G_AREAS:
			case Graficador::G_COLUMNAS:
			case Graficador::G_RADAR:
				$grafico->SetScale( $this->ejeX['escala'].$this->ejeY['escala'], $this->ejeY['min'], $this->ejeY['max'] );
				break;
			case Graficador::G_UBICACIONES:
			case Graficador::G_DISPERSION:
				$grafico->SetScale( $this->ejeX['escala'].$this->ejeY['escala'], $this->ejeY['min'], $this->ejeY['max'], $this->ejeX['min'], $this->ejeX['max'] );
				break;
		}
		$grafico->SetMargin( 
			$this->grafico['margenes']['izq'], 
			$this->grafico['margenes']['der'], 
			$this->grafico['margenes']['sup'], 
			$this->grafico['margenes']['inf'] 
		);
		$grafico->SetBox( $this->grafico['marco'] ); 
		$grafico->xaxis->HideLine( !$this->ejeX['linea'] );
		$grafico->yaxis->HideLine( !$this->ejeY['linea'] );
		$grafico->xaxis->SetColor( $this->ejeX['color'] );
		$grafico->yaxis->SetColor( $this->ejeY['color'] );
		$grafico->xaxis->SetLabelMargin( $this->ejeX['margen'] );
		$grafico->yaxis->SetLabelMargin( $this->ejeY['margen'] );
		$grafico->xaxis->SetFont( 
			$this->_asignarFuente( $this->ejeX['fuente'] ), 
			$this->_asignarAspecto( $this->ejeX['aspecto'] ), 
			$this->ejeX['tamaño'] 
		);
		$grafico->yaxis->SetFont( 
			$this->_asignarFuente($this->ejeY['fuente']), 
			$this->_asignarAspecto( $this->ejeY['aspecto'] ), 
			$this->ejeY['tamaño'] 
		);
		if ( strlen($this->ejeX['angulox']) >0 ) {
			$grafico->xaxis->SetLabelAngle(intval($this->ejeX['angulox']));
		}
		if ( strlen($this->ejeX['cuadricula']) >0 ) {
			$grafico->xgrid->Show( true );
			$grafico->xgrid->SetFill( false );
			$grafico->xgrid->SetColor = $this->ejeX['cuadricula'];
		} else {
			$grafico->xgrid->Show( false );
		}
		if ( strlen($this->ejeY['cuadricula']) >0 ) {
			$grafico->ygrid->Show( true );
			$grafico->ygrid->SetFill( false );
			$grafico->ygrid->SetColor = $this->ejeY['cuadricula'];
		} else {
			$grafico->ygrid->Show( false );
		}
		if ( strlen($this->ejeX['marcas']) >0 ) {
			$grafico->xaxis->HideTicks( true, false );
			if ( $this->ejeX['marcas'] == 'sup' ) {
				$grafico->xaxis->SetTickSide( SIDE_TOP );
			} else {
				$grafico->xaxis->SetTickSide( SIDE_BOTTOM );
			}
		} else {
			$grafico->xaxis->HideTicks( true, true );
		}
		if ( strlen($this->ejeY['marcas']) >0 ) {
			$grafico->yaxis->HideTicks( true, false );
			if ( $this->ejeY['marcas'] == 'izq' ) {
				$grafico->yaxis->SetTickSide( SIDE_LEFT );
			} else {
				$grafico->yaxis->SetTickSide( SIDE_RIGHT );
			}
		} else {
			$grafico->yaxis->HideTicks( true, true );
		}
		if ( $this->ejeX['visible'] == false ) { $grafico->xaxis->Hide(); }
		if ( $this->ejeY['visible'] == false ) { $grafico->yaxis->Hide(); }
		if ( count($this->ejeX['valores'])>0 ) {
			$grafico->xaxis->SetTickPositions( $this->ejeX['valores'] ); 
		}
		if ( is_array($this->ejeX['etiquetas']) && count($this->ejeX['etiquetas']) > 0 ) {
			$grafico->xaxis->SetTickLabels( $this->ejeX['etiquetas'] );
		}
		if ( is_array($this->ejeY['etiquetas']) && count($this->ejeY['etiquetas']) > 0 ) {
			$grafico->yaxis->SetTickLabels( $this->ejeY['etiquetas'] );
		}
		if ( count($this->ejeY['valores'])>0 ) {
			$grafico->yaxis->SetTickPositions( $this->ejeY['valores'] );
		}
		if ( strlen($this->ejeY['titulo']['texto'])>0 ) { 
			$grafico->yaxis->title->Set( $this->ejeY['titulo']['texto'] );
			$grafico->yaxis->title->SetColor( $this->ejeY['titulo']['color'] );
			$grafico->yaxis->title->SetMargin( $this->ejeY['titulo']['margen'] );
			$grafico->yaxis->title->SetFont( 
				$this->_asignarFuente( $this->ejeY['titulo']['fuente'] ), 
				$this->_asignarAspecto( $this->ejeY['titulo']['aspecto'] ), 
				$this->ejeY['titulo']['tamaño'] 
			);
		} else {
			$grafico->yaxis->title->Set( '' );
		}
		if ( strlen($this->ejeX['titulo']['texto'])>0 ) { 
			$grafico->xaxis->SetTitle( $this->ejeX['titulo']['texto'], 'center' );
			$grafico->xaxis->title->Set( $this->ejeX['titulo']['texto'] );
			$grafico->xaxis->title->SetColor( $this->ejeX['titulo']['color'] );
			$grafico->xaxis->title->SetMargin( $this->ejeX['titulo']['margen'] );
			$grafico->xaxis->title->SetFont( 
				$this->_asignarFuente( $this->ejeX['titulo']['fuente'] ), 
				$this->_asignarAspecto( $this->ejeX['titulo']['aspecto'] ), 
				$this->ejeX['titulo']['tamaño'] 
			);
		} else {
			$grafico->xaxis->title->Set( '' );
		}
		$fuente = $this->_asignarFuente( $this->leyenda['fuente'] );
		$aspecto = $this->_asignarAspecto( $this->leyenda['aspecto'] ); 
		$grafico->legend->SetFrameWeight( $this->leyenda['linea'] );
		$grafico->legend->SetFillColor( $this->leyenda['relleno'] );
		$grafico->legend->SetShadow( $this->leyenda['sombra'] );
		$grafico->legend->SetMarkAbsSize( $this->leyenda['marca'] );
		$grafico->legend->SetFont( $fuente, $aspecto, $this->leyenda['tamaño'] );
		$grafico->legend->SetColor( $this->leyenda['color'], $this->leyenda['borde'] );
		switch ( $this->leyenda['posicion'] ) {
			case 'izq_sup': 
				$grafico->legend->Pos( 0, 0, 'left', 'top' );
				$grafico->legend->SetColumns( 6 );
				break;
			case 'izq_inf': 
				$grafico->legend->Pos( 0, 0.99999, 'left', 'bottom' );
				$grafico->legend->SetColumns( 6 );
				break;
			case 'izq_med': 
				$grafico->legend->Pos( 0, 0.4, 'left', 'top' );
				$grafico->legend->SetColumns( 1 );
				break;
			case 'der_sup': 
				$grafico->legend->Pos( 0.001, 0, 'right', 'top' );
				$grafico->legend->SetColumns( 6 );
				break;
			case 'der_inf': 
				$grafico->legend->Pos( 0.001, 0.99999, 'right', 'bottom' );
				$grafico->legend->SetColumns( 6 );
				break;
			case 'der_med': 
				$grafico->legend->Pos( 0.001, 0.4, 'right', 'top' );
				$grafico->legend->SetColumns( 1 );
				break;
			case 'cen_inf': 
				$grafico->legend->Pos( 0.5, 0.99999, 'center', 'bottom' );
				$grafico->legend->SetColumns( 6 );
				break;
			case 'cen_sup': 
				$grafico->legend->Pos( 0.5, 0, 'center', 'top' );
				$grafico->legend->SetColumns( 6 );
				break;
			default:
				$grafico->legend->Hide();
		}
		return $grafico;	
	}

	private function _crearEtiqueta( $x, $y, $texto, $fondo = 'silver', $letra = 'black', $angulo = 0 ) {
		$tamano	= ( isset($this->series['puntos']['tamaño']) ? $this->series['puntos']['tamaño'] : 10 );
		$fuente = M::E('RUTA/ME') . '/Recursos/' . strtolower( $this->series['puntos']['fuente'] ) . '.ttf';
		if ( strlen($letra)==0 ) { $letra = $this->series['puntos']['color']; }
		$saltos = substr_count( $texto, chr(10) );
		if ( $saltos >0 ) {
			$etiq_ancho = ( ( strlen($texto) * $tamano ) - 20 ) / ( $saltos + 1 );
			$etiq_alto = ( $tamano + 8 ) * ( $saltos + 1 );
		} else {
			$etiq_ancho = ( strlen($texto) * $tamano / 1.3 ) + 6;
			$etiq_alto = $tamano + 8;
		}
		$marca = substr($texto, 0, 1)=='*';
		if ( $marca ) {
			$tamano = $tamano + 6;
			$etiq_alto = $etiq_alto + 4;
			$etiq_ancho = ( strlen($texto) * $tamano / 1.3 ) + 8;
			$texto = ltrim( $texto, '*' );
		}
		$img = @imagecreatetruecolor( $etiq_ancho, $etiq_alto );
		imagesavealpha( $img, true );
		imagefill( $img, 0, 0, $this->_asignarColor( $fondo, $img ) );
		$caja = imagettfbbox( $tamano, 0, $fuente, trim($texto) );
		imagettftext(
			$img, 
			$tamano, //(letra)
			0, //(angulo)
			((imagesx($img) - $caja[2]) / 2), //(x)
			$tamano + 3, //(y)
			$this->_asignarColor( $letra, $img ),
			$fuente,
			$texto
		);
		if ( $angulo != 0 ) {
			$img = imagerotate( $img, $angulo, imagecolorallocatealpha( $img, 255, 255, 255, 127 ) );
			imagealphablending( $img, false );
			imagesavealpha( $img, true );
		}
		$imagen = M::E('ALMACEN/PRIVADO') . '/temp/im_' . M::E('M_USUARIO') . '_' . strval($x) . '_' . strval($y) . '.png';
		$this->temporales[] = $imagen;
		imagepng( $img, $imagen );
		imagedestroy( $img );
	}

	private function _establecerImagen() {
		if ( strlen($this->grafico['imagen'])==0 ) { $this->grafico['imagen'] = 'g_' . uniqid(); }
		if ( strlen($this->grafico['ruta'])==0 ) { $this->grafico['ruta'] = M::E('ALMACEN/PRIVADO') . '/temp'; }
		if ( substr($this->grafico['imagen'], -4) != '.jpg' ) { $this->grafico['imagen'] .= '.jpg'; }
		$imagen = $this->grafico['ruta'] . '/' . $this->grafico['imagen'];
		if ( file_exists( $imagen ) && !is_dir( $imagen ) ) { 
			unlink( $imagen );
		}
		return $imagen;
	}

	private function _asignarColor( $color, $img ) {
		switch ( $color ) {
			case '':
			case 'transparent':
				return imagecolorallocatealpha( $img, 255, 255, 255, 127 );
				break;
			case 'black':
				return imagecolorallocatealpha( $img, 0, 0, 0, 0 );
				break;
			case 'white':
				return imagecolorallocatealpha( $img, 255, 255, 255, 0 );
				break;
			case 'gray':
				return imagecolorallocatealpha( $img, 128, 128, 128, 0 );
				break;
			case 'silver':
				return imagecolorallocatealpha( $img, 192, 192, 192, 0 );
				break;
			case 'red':
				return imagecolorallocatealpha( $img, 255, 0, 0, 0 );
				break;
			case 'blue':
				return imagecolorallocatealpha( $img, 0, 0, 255, 0 );
				break;
			case 'lime':
			case 'green':
				return imagecolorallocatealpha( $img, 0, 255, 0, 0 );
				break;
			case 'darkgreen':
				return imagecolorallocatealpha( $img, 0, 100, 0, 0 );
				break;
			case 'yellow':
				return imagecolorallocatealpha( $img, 255, 255, 0, 0 );
				break;
			case 'purple':
				return imagecolorallocatealpha( $img, 128, 0, 128, 0 );
				break;
			case 'orange':
				return imagecolorallocatealpha( $img, 255, 165, 0, 0 );
				break;
			case 'darkolivegreen':
				return imagecolorallocatealpha( $img, 85, 107, 47, 0 );
				break;
			case 'aqua':
				return imagecolorallocatealpha( $img, 0, 255, 255, 0 );
				break;
			case 'olive':
				return imagecolorallocatealpha( $img, 128, 128, 0, 0 );
				break;
			case 'magenta':
				return imagecolorallocatealpha( $img, 255, 0, 255, 0 );
				break;
			case 'maroon':
				return imagecolorallocatealpha( $img, 128, 0, 0, 0 );
				break;
			case 'navy':
				return imagecolorallocatealpha( $img, 0, 0, 128, 0 );
				break;
			case 'teal':
				return imagecolorallocatealpha( $img, 0, 128, 128, 0 );
				break;
			case 'pink':
				return imagecolorallocatealpha( $img, 255, 192, 203, 0 );
				break;
			case 'skyblue':
				return imagecolorallocatealpha( $img, 135, 206, 235, 0 );
				break;
			case 'yellowgreen':
				return imagecolorallocatealpha( $img, 154, 205, 50, 0 );
				break;
			case 'gold':
				return imagecolorallocatealpha( $img, 255, 215, 0, 0 );
				break;
			case 'saddlebrown':
				return imagecolorallocatealpha( $img, 139, 69, 19, 0 );
				break;
			case 'violet':
				return imagecolorallocatealpha( $img, 238, 130, 238, 0 );
				break;
			case 'lightgreen':
				return imagecolorallocatealpha( $img, 144, 238, 144, 0 );
				break;
			case 'moccasin':
				return imagecolorallocatealpha( $img, 255, 228, 181, 0 );
				break;
			case 'orangered':
				return imagecolorallocatealpha( $img, 255, 69, 0, 0 );
				break;
			case 'greenyellow':
				return imagecolorallocatealpha( $img, 173, 255, 47, 0 );
				break;
			case 'blueviolet':
				return imagecolorallocatealpha( $img, 138, 43, 226, 0 );
				break;
			case 'crimson':
				return imagecolorallocatealpha( $img, 220, 20, 60, 0 );
				break;
			case 'aquamarine':
				return imagecolorallocatealpha( $img, 127, 255, 212, 0 );
				break;
			case 'rosybrown':
				return imagecolorallocatealpha( $img, 188, 143, 143, 0 );
				break;
			case 'chocolate':
				return imagecolorallocatealpha( $img, 210, 105, 30, 0 );
				break;
			case 'lightcyan':
				return imagecolorallocatealpha( $img, 224, 255, 255, 0 );
				break;
			case 'deeppink':
				return imagecolorallocatealpha( $img, 255, 20, 147, 0 );
				break;
			case 'indigo':
				return imagecolorallocatealpha( $img, 75, 0, 130, 0 );
				break;
			case 'salmon':
				return imagecolorallocatealpha( $img, 250,128,114, 0 );
				break;
			case 'seagreen':
				return imagecolorallocatealpha( $img, 46, 139, 87, 0 );
				break;
			case 'khaki':
				return imagecolorallocatealpha( $img, 240, 230, 140, 0 );
				break;
			case 'indianred':
				return imagecolorallocatealpha( $img, 205, 92, 92, 0 );
				break;
			case 'dodgerblue':
				return imagecolorallocatealpha( $img, 30, 144, 255, 0 );
				break;
			case 'gainsboro':
				return imagecolorallocatealpha( $img, 220, 220, 220, 0 );
				break;
			case 'steelblue':
				return imagecolorallocatealpha( $img, 70, 130, 180, 0 );
				break;
			case 'A':
				return imagecolorallocatealpha( $img, 153, 255, 255, 0 );
				break;
			case 'M':
				return imagecolorallocatealpha( $img, 187, 255, 187, 0 );
				break;
			case 'B':
				return imagecolorallocatealpha( $img, 255, 255, 153, 0 );
				break;
			case 'N':
				return imagecolorallocatealpha( $img, 255, 221, 221, 0 );
				break;
			default:
				$color = ltrim($color, '#');
				$r = hexdec( substr($color, 0, 2) );
				$g = hexdec( substr($color, 2, 2) );
				$b = hexdec( substr($color, 4, 2) );
				return imagecolorallocatealpha( $img, $r, $g, $b, 0 );
		}
	}

	private function _asignarFuente( $fuente ) {
		switch ( $fuente ) {
			case 'verdana':
				return FF_VERDANA;
				break;
			case 'times':
				return FF_TIMES;
				break;
			case 'arial':
			default:
				return FF_ARIAL;
				break;
		}
	}

	private function _asignarAspecto( $aspecto ) {
		switch ( $aspecto ) {
			case 'bold':
				return FS_BOLD;
				break;
			case 'italic':
				return FS_ITALIC;
				break;
			case 'bolditalic':
				return FS_BOLDITALIC;
				break;
			case 'normal':
			default:
				return FS_NORMAL;
				break;
		}
	}

	private function _asignarMarca ( $marca ) {
		switch ( $marca ) {
			case 'circunsferencia':
				return MARK_CIRCLE;
				break;
			case 'circulo':
				return MARK_FILLEDCIRCLE;
				break;
			case 'cruz':
				return MARK_CROSS;
				break;
			case 'estrella':
				return MARK_STAR;
				break;
			case 'x':
				return MARK_X;
				break;
			case 'diamante':
				return MARK_DIAMOND;
				break;
			case 'triangulo':
				return MARK_UTRIANGLE;
				break;
			case 'cuadrado':
			default:
				return MARK_SQUARE;
				break;
		}
	}
}

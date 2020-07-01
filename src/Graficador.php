<?php
namespace MasExperto\ME;
use MasExperto\ME\Interfaces\IGraficador;

abstract class Graficador implements IGraficador
{
	//CONSTANTES
		const G_BARRAS		= 101;
		const G_LINEAS		= 102;
		const G_SECCIONES	= 103;
		const G_UBICACIONES	= 104;
		const G_AREAS		= 105;
		const G_COLUMNAS	= 106;
		const G_RADAR		= 107;
		const G_DISPERSION	= 108;

	//PROPIEDADES
		protected $temporales = array();
		public $ejeX = array();
		public $ejeY = array();
		public $leyenda = array();
		public $grafico = array();
		public $series = array();

	//CONSTRUCTOR
	function __construct() {
		$this->grafico = array( 
			'ancho'=>640, 'alto'=>480, 'marco'=>false, 'suavizado'=>false, 'fondo'=>'', 'ruta'=>'', 'imagen'=>'', 
			'margenes'=> array( 'izq'=>10, 'sup'=>10, 'der'=>10, 'inf'=>10 ),
			'3D'=>false, 'AC'=>false, 'sombra'=>false, 'guias'=>false, 'separar'=>''
		);
		$this->ejeY = array(
			'escala'=>'int', 'min'=>0, 'max'=>100, 'color'=>'black', 'visible'=>true, 'linea'=>true,
			'fuente'=>'arial', 'tamaño'=>11, 'aspecto'=>'normal', 'margen'=>12,
			'marcas'=>'', 'cuadricula'=>'', 'valores'=>array(), 'etiquetas'=>array()
		);
		$this->ejeY['titulo'] = array( 'fuente'=>'arial', 'tamaño'=>12, 'color'=>'black', 'aspecto'=>'normal', 'margen'=>12, 'texto'=>'' );
		$this->ejeX = array( 
			'escala'=>'text', 'min'=>0, 'max'=>10, 'color'=>'black', 'visible'=>true, 'linea'=>true,
			'fuente'=>'arial', 'tamaño'=>11, 'aspecto'=>'normal', 'margen'=>12,
			'marcas'=>'', 'cuadricula'=>'', 'valores'=>array(), 'etiquetas'=>array(), 'angulox'=>0
		);
		$this->ejeX['titulo'] = array( 'fuente'=>'arial', 'tamaño'=>12, 'color'=>'black', 'aspecto'=>'normal', 'margen'=>12, 'texto'=>'' );
		$this->leyenda = array( 'posicion'=>'der_med', 'fuente'=>'arial', 'tamaño'=>10, 'color'=>'black', 'aspecto'=>'normal', 'relleno'=>'white', 'borde'=>'black', 'sombra'=>false, 'linea'=>0, 'marca'=>8 );
		$this->series = array( 'borde'=>'', 'grosor'=>4 );
		$this->series['linea'] = array( 'estilo'=>'dashed', 'color'=>'blue', 'leyenda'=>'', 'valores'=>array() );
		$this->series['puntos'] = array( 
			'visible'=>false, 'formato'=>'', 'fuente'=>'arial', 'tamaño'=>11, 'color'=>'black', 'aspecto'=>'normal', 'margen'=>15,
			'etiquetas'=> array(), 
			'fondos'=> array(), 
			'letras'=> array(), 
			'angulos'=> array( 0 ) 
		);
		$this->series['marcas'] = array( 'borde'=>'', 'relleno'=>'', 'ancho'=>8, 'figura'=>'circulo' );
		$this->series['semaforo'] = array();
		$this->series['colores'] = array();
		$this->series['leyendas'] = array();
	}
	function __destruct() {}
}
?>
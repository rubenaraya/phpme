<?php
namespace MasExperto\ME;
use MasExperto\ME\Interfaces\IModelo;

abstract class Modelo implements IModelo
{
	//PROPIEDADES
		protected $temp = array();
		protected $entidad = '';
		protected $tabla = '';
		public $bd = null;
		public $almacen = null;
		public $T = array();
		public $A = array();
		public $D = array();
		public $I = array();
		public $F = array();
		public $R = array();
		public $sql = array();

	//CONSTRUCTOR
	function __construct() {
		$conector = M::E('CONECTOR/BD');
		$this->bd = new $conector;
		$conector = M::E('CONECTOR/ALMACEN');
		$this->almacen = new $conector;
		$this->almacen->Conectar( M::E('ALMACEN') );
	}
	function __destruct() {
		unset($this->bd);
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

	//METODOS PUBLICOS

	/** 
		* @param			
		* @return		*/
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
			case 'fecha':
				if ( $max > 0 && strlen($evaluar) >= $min && strlen($evaluar) <= $max ) { $estado = 1; }
				else if ( $max == 0 && strlen($evaluar) >= $min ) { $estado = 1; }
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
		if ( strlen($nombre)>0 ) { $nombre = $nombre . '.'; }
		return 'M.' . $nombre . 'A = ' . json_encode($this->A) . '; M.' . $nombre . 'I = ' . json_encode($this->I);
	}
	public function Consultar( &$dto ) {}
	public function Nuevo( &$dto ) {}
	public function Agregar( &$dto ) {}
	public function Abrir( &$dto ) {}
	public function Editar( &$dto ) {}
	public function Borrar( &$dto ) {}
	public function Cambiar( &$dto ) {}
	public function Imagen( &$dto ) {}
	public function Archivo( &$dto ) {}
	public function Refrescar( &$dto ) {}
	public function Registrar( &$dto ) {}
	public function Ejecutar( &$dto ) {}
	public function Ver( &$dto ) {}
	public function Exportar( &$dto ) {}
	public function Descargar( &$dto ) {}
}
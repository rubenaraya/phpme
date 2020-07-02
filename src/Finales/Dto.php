<?php
namespace MasExperto\ME\Finales;

use MasExperto\ME\Interfaces\IDto;

final class Dto implements IDto
{
	public $parametros = array();
	public $campos = array();
	public $peticion = array();
	public $resultados = array();
	public $valores = array();

	function __destruct() {
		unset($this->parametros);
		unset($this->campos);
		unset($this->resultados);
		unset($this->valores);
		unset($this->peticion);
	}

	public function get( $nombre, $objeto = 'campo', $predeterminado = '' ) {
		$valor = '';
		switch( $objeto ) {
			case 'peticion': 
				if ( isset( $this->peticion[$nombre] ) ) { $valor = $this->peticion[$nombre]; }
				break;
			case 'parametro': 
				if ( isset( $this->parametros[$nombre] ) ) { $valor = $this->parametros[$nombre]; }
				break;
			case 'campo': 
				if ( isset( $this->campos[$nombre] ) ) { $valor = $this->campos[$nombre]; }
				break;
			case 'resultado': 
				if ( isset( $this->resultados[$nombre] ) ) { $valor = $this->resultados[$nombre]; }
				break;
			case 'valor': 
				if ( isset( $this->valores[$nombre] ) ) { $valor = $this->valores[$nombre]; }
				break;
		}
		if ( !is_array($valor) && strlen($valor)==0 && strlen($predeterminado)>0 ) {
			$valor = $predeterminado;
			$this->set( $nombre, $valor, $objeto );
		}
		return $valor;
	}

	public function set( $nombre, $valor, $objeto = 'peticion' ) {
		switch( $objeto ) {
			case 'peticion': 
				$this->peticion[$nombre] = $valor;
				break;
			case 'parametro': 
				$this->parametros[$nombre] = $valor;
				break;
			case 'campo': 
				$this->campos[$nombre] = $valor;
				break;
			case 'resultado': 
				$this->resultados[$nombre] = $valor;
				break;
			case 'valor': 
				$this->valores[$nombre] = $valor;
				break;
		}
		return $valor;
	}

	public function getset( $nombre ) {
		$valor = '';
		if ( isset( $this->campos[$nombre] ) ) { 
			$valor = $this->campos[$nombre];
			$this->peticion[$nombre] = $valor;
		} else if ( isset( $this->parametros[$nombre] ) ) {
			$valor = $this->parametros[$nombre];
			$this->peticion[$nombre] = $valor;
		}
		return $valor;
	}

	public function extraerResultado( $etiqueta = '' ) {
		if ( strlen($etiqueta)==0 ) {
			return $this->resultados;
		} elseif ( isset($this->resultados[$etiqueta]) ) {
			return $this->resultados[$etiqueta];
		} else {
			return null;
		}
	}

	public function traspasarPeticion( $opcion = 0 ) {
		if ( $opcion == 1 ) {
			$this->peticion = $this->parametros;
		} else if ( $opcion == 2 ) {
			$this->peticion = $this->parametros + $this->campos;
		} else {
			$this->peticion = $this->campos;
		}
		return;
	}

	public function Vaciar( $todo = false ) {
		$this->peticion = array();
		$this->resultados = array();
		$this->valores = array();
		if ( $todo ) {
			$this->parametros = array();
			$this->campos = array();
		}
		return;
	}
}

<?php 
namespace MasExperto\ME;

use PHPExcel_Reader_IReadFilter;

class FiltroExcel implements PHPExcel_Reader_IReadFilter
{ 
	public function __construct( $filas = 5000, $columnas = array() ) {
		$this->columnas = $columnas;
		$this->filas = $filas;
		$this->total = count($columnas);
	}
	public function readCell($column, $row, $worksheetName = '') {
		if ( $row >= 1 && $row <= $this->filas ) {
			if ( $this->total==0 ) {
				return true;
			} else {
				if ( in_array( $column, $this->columnas ) ) {
					return true;
				}
			}
		}
		return false;
	}
}

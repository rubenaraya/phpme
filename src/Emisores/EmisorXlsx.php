<?php
namespace MasExperto\ME\Emisores;

use MasExperto\ME\Bases\Emisor;
use MasExperto\ME\M;
use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class EmisorXlsx extends Emisor {

	public function Imprimir( $contenido, $opciones = array() ) {
		$opciones['nombre'] = ( isset($opciones['nombre']) ? $opciones['nombre'] : date('Ymd_His') . '.xlsx' );
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8' );
		header( 'Content-Disposition: attachment;filename="' . $opciones['nombre'] . '";' );
		if ( is_array($contenido) ) {
			$aux = M::adquirirDatosMatriz( $contenido, '0' );
			if ( is_array($aux) && count($aux)>0 ) {
				$contenido = $aux[0];
			} else {
				$contenido = '';
			}
		}
		$this->_exportarExcel($contenido);
		flush();
		exit;
	}

	public function mostrarErrores( $errores ) {
		$texto = '';
		foreach ( $errores as $error ) {
			$texto = $texto . '<p>' . htmlspecialchars( $error['mensaje'] ) . '</p>';
		}
		header( 'Content-Type: text/html; charset=utf-8' );
		echo $texto;
		flush();
		exit;
	}

	private function _exportarExcel($contenido) {
        $libro = new Spreadsheet();
		$libro->removeSheetByIndex(0);
		$libro->getDefaultStyle()->getFont()->setName( 'Arial' );
		$libro->getDefaultStyle()->getFont()->setSize(10);
		$hoja = $libro->createSheet();
		$hoja->setTitle( 'Hoja1' );
		$libro->setActiveSheetIndex(0);
		if ( is_array($contenido) && count($contenido)>0 ) {
			$col = 0; $fila = 2;
			foreach( $contenido[0] as $key => $value ) {
				if ( isset($contenido[0]) ) {
					$libro->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $key);
					$col++;
				}
			}
			foreach( $contenido as $key => $value ) {
				if ( is_array($value) ) {
					$col = 0;
					foreach( $value as $key2 => $value2 ) {
						if ( substr_count($value2,'-')==2 || substr_count($value2,'/')==2 ) {
							$value2 = str_replace( '/', '-', $value2 );
							$matriz = explode( '-', $value2 );
							if ( is_numeric($matriz[0]) && is_numeric($matriz[1]) && is_numeric($matriz[2]) ) {
								try { $fecha = new DateTime($value2); }
								catch ( \Exception $e ) { $fecha = false ; }
							} else {
								$fecha = false ;
							}
							if ( $fecha != false ) {
								$va = floor( Date::PHPToExcel( $fecha ) );
								$libro->getActiveSheet()->getCell( Coordinate::stringFromColumnIndex($col).$fila )->setValueExplicit( $va, DataType::TYPE_NUMERIC );
								$libro->getActiveSheet()->setCellValueByColumnAndRow( $col, $fila, $va );
								$libro->getActiveSheet()->getStyle( Coordinate::stringFromColumnIndex($col).$fila )->getNumberFormat()->setFormatCode( 'dd-mm-yyyy' );
							} else {
								$libro->getActiveSheet()->setCellValueByColumnAndRow( $col, $fila, $value2 );
							}
						} elseif ( substr($value2, 0, 1)=="'" ) {
							$va = trim( $value2, chr(39) );
							$libro->getActiveSheet()->getCell( Coordinate::stringFromColumnIndex($col).$fila )->setValueExplicit($va, DataType::TYPE_STRING );
							$libro->getActiveSheet()->getStyle( Coordinate::stringFromColumnIndex($col).$fila )->getNumberFormat()->setFormatCode( '@' );
						} elseif ( is_numeric($value2) ) {
							$va = floatval($value2);
							$libro->getActiveSheet()->getCell( Coordinate::stringFromColumnIndex($col).$fila )->setValueExplicit( $va, DataType::TYPE_NUMERIC );
							if ( substr_count($value2,'.')==0 ) {
								$libro->getActiveSheet()->getStyle( Coordinate::stringFromColumnIndex($col).$fila )->getNumberFormat()->setFormatCode( '#,##0' );
							}
						} else {
							$libro->getActiveSheet()->setCellValueByColumnAndRow( $col, $fila, $value2 );
						}
						$col++;
					}
				}
				$fila++;
			}
			$libro->getActiveSheet()->setShowGridlines( false );
			$estilos = array(
				'borders' => array( 'allborders' => 
					array( 'style' => Border::BORDER_THIN, 'color' => array( 'argb' => 'FF000000' ),),
				), 'font' => array( 'size' => 10 )
			);
			$libro->getActiveSheet()->getStyle( 
				'A1:' . $libro->getActiveSheet()->getHighestColumn() . ($fila - 1) 
			)->applyFromArray( $estilos );
			$libro->getActiveSheet()->getStyle('A1:A1')->applyFromArray( $estilos );
			$libro->setActiveSheetIndex(0);
		}
        $guardar = new Xlsx($libro);
		$guardar->setPreCalculateFormulas( false );
		$guardar->save( 'php://output' );
		unset($guardar); unset($libro); unset($hoja);
	}
}

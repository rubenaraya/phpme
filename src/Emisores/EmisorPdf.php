<?php
namespace MasExperto\ME\Emisores;
use MasExperto\ME\Emisor;
use MasExperto\ME\M;
use Mpdf\Mpdf;

final class EmisorPdf extends Emisor {

	//METODOS PUBLICOS

	public function Imprimir( $contenido, $opciones = array() ) {
		$nombre			= ( isset($opciones['nombre']) ? $opciones['nombre'] : date('Ymd_His') . '.pdf' );
		$destino		= ( isset($opciones['destino']) ? $opciones['destino'] : 'I' );
		$titulo			= ( isset($opciones['titulo']) ? $opciones['titulo'] : '' );
		$autor			= ( isset($opciones['autor']) ? $opciones['autor'] : '' );
		$pie			= ( isset($opciones['pie']) ? $opciones['pie'] : '' );
		$estilos		= ( isset($opciones['estilos']) ? $opciones['estilos'] : '' );
		$orientacion	= ( isset($opciones['orientacion']) ? $opciones['orientacion'] : '' );
		$papel			= ( isset($opciones['papel']) ? $opciones['papel'] : 'LETTER' );
		$fuente			= ( isset($opciones['fuente']) ? $opciones['fuente'] : 'Arial' );
		$margen_izq		= ( isset($opciones['margen_izq']) ? $opciones['margen_izq'] : 15 );
		$margen_der		= ( isset($opciones['margen_der']) ? $opciones['margen_der'] : 15 );
		$margen_sup		= ( isset($opciones['margen_sup']) ? $opciones['margen_sup'] : 15 );
		$margen_inf		= ( isset($opciones['margen_inf']) ? $opciones['margen_inf'] : 15 );

		if ( is_array($contenido) ) {
			$contenido = M::convertirMatrizHtml( $contenido, $opciones ); 
		}
		$pdf = new Mpdf([
			'mode' => 'utf-8',
			'format' => "$papel$orientacion",
			'default_font' => $fuente,
			'margin_left' => $margen_izq,
			'margin_right' => $margen_der,
			'margin_top' => $margen_sup,
			'margin_bottom' => $margen_inf
		]);
		$pdf->use_kwt = true;
		$pdf->SetDisplayMode( 'fullwidth', 'continuous' );
		$pdf->SetTitle( $titulo );
		$pdf->SetAuthor( $autor );
		$pdf->SetHTMLFooter( $pie );
		if ( file_exists($estilos) ) {
			$pdf->WriteHTML( file_get_contents( $estilos ), 1 );
		}
		$pdf->WriteHTML( $contenido, 2 );
		$pdf->Output( $nombre, $destino );
		unset($pdf);
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
}

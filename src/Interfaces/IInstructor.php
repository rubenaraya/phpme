<?php
namespace MasExperto\ME\Interfaces;

interface IInstructor extends IAdaptador {
	public function abrirContenido();
	public function crearRegistro( $usuario );
	public function guardarRespuestas( $seccion );
	public function consultarInformacion( $info );
	public function exportarRespuestas( $opciones );
	public function procesarResultados( $opciones );
	public function aplicarCalculos( $opciones );
	public function generarInforme( $opciones );
}
?>
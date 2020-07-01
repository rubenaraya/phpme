<?php
namespace MasExperto\ME\Interfaces;

interface IEmisor {
	public function Imprimir( $contenido, $opciones );
	public function mostrarErrores( $errores );
}
?>
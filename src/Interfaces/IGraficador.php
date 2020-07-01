<?php
namespace MasExperto\ME\Interfaces;

interface IGraficador {
	public function Graficar( $tipo, &$datos );
	public function graficarUbicaciones( &$datos );
	public function graficarSecciones( &$datos );
	public function graficarBarras( &$datos );
	public function graficarLineas( &$datos );
	public function agregarValores( $valores, &$matriz );
	public function cambiarFuente( $fuente );
	public function borrarTemporales();
}

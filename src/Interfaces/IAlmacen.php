<?php
namespace MasExperto\ME\Interfaces;

interface IAlmacen {
	public function Conectar( $credenciales );
	public function cargarArchivos( $destino, $opciones );
	public function adaptarImagenes( $destino, $archivos, $opciones );
	public function extraerZip( $destino, $archivos, $opciones );
	public function empaquetarZip( $origen, $destino, $opciones );
	public function extraerDatosExcel( $archivo, $opciones );
	public function abrirArchivo( $ubicacion );
	public function guardarArchivo( $destino, $contenido, $opciones );
	public function borrarArchivos( $lista );
	public function copiarCarpetas( $origen, $destino );
	public function borrarCarpetas( $origen, $quitar );
	public function explorarCarpeta( $origen, $carpeta, $opciones );
	public function validarNombre( $nombre, $patron );
}

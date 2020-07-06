<?php
namespace MasExperto\ME\Interfaces;

interface IPresentador {
	public function crearVista( $documento, $ruta );
	public function anexarDocumento( $documento, $ruta );
	public function anexarResultados($dto );
	public function anexarMetadatos( $matriz, $etiqueta );
	public function Transformar( $plantilla, $ruta, $opciones );
	public function abrirPlantilla( $archivo, $ruta, $reemplazar );
	public function filtrarVisibles( $roles, $entidad );
}

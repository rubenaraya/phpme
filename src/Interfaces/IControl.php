<?php
namespace MasExperto\ME\Interfaces;

interface IControl {
	public function inyectarContexto( &$ruteador );
	public function ejecutarOperacion();
	public function cargarPerfil( $id );
	public function guardarPerfil( $id, $datos, $etiqueta );
	public function comprobarPermisos( $roles, $esquema, $operacion );
	public function prepararPeticion( $caso, &$modelo );
}
?>
<?php
namespace MasExperto\ME\Interfaces;

interface IControl {
	public function Iniciar( &$ruteador );
	public function ejecutarOperacion();
	public function cargarPerfil( $id );
	public function guardarPerfil( $id, $datos, $etiqueta );
	public function comprobarPermisos( $roles, $esquema, $operacion );
}

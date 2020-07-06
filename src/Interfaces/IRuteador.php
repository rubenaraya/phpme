<?php
namespace MasExperto\ME\Interfaces;

interface IRuteador {
	public function procesarSolicitud();
	public function revisarCredencial( $canal );
	public function comprobarToken( $token, $tipo );
	public function autorizarAcceso( $tipo, $canal );
	public function verificarRequisitos( $metodos, $entradas );
	public function cambiarEstado( $datos, $mensaje );
	public function guardarCache($minutos );
	public function enviarRespuesta( $contenido, $opciones );
	public function enviarError( $tipo, $mensaje );
	public function Redirigir( $destino );
	public function Salir( $enviar );
}

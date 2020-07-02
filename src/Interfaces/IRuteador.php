<?php
namespace MasExperto\ME\Interfaces;

interface IRuteador {
	public function procesarSolicitud( $idiomas );
	public function autorizarAcceso( $tipo, $canal );
	public function verificarRequisitos( $metodos, $entradas );
	public function cambiarEstado( $datos, $mensaje );
	public function controlarCache( $minutos );
	public function enviarRespuesta( $contenido, $opciones );
	public function enviarError( $tipo, $mensaje );
	public function Redirigir( $destino );
	public function Salir( $enviar );
}

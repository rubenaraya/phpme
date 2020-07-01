<?php
namespace MasExperto\ME\Interfaces;

interface ICorreo {
	public function Conectar( $credenciales );
	public function enviarMensaje( $asunto, $mensaje, $destinatarios, $adjuntos );
}
?>
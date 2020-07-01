<?php
namespace MasExperto\ME\Clases;

use MasExperto\ME\Correo;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class CorreoSmtp extends Correo {
	
	public function Conectar( $credenciales ) {
		$this->credenciales = $credenciales;
	}

	public function enviarMensaje( $asunto, $mensaje, $destinatarios, $adjuntos = array() ) {
		$resultado = array();
		$resultado['estado'] = false;
		$mail = new PHPMailer( true );
		try {
			$mail->IsSMTP();
			$mail->Timeout = 30;
			$mail->SMTPAuth = true;
			$mail->SMTPSecure = 'tls';
			$mail->Host = $this->credenciales['SMTP'];
			$mail->Port = 587;
			$mail->Username = $this->credenciales['USER'];
			$mail->Password = $this->credenciales['PASSW'];
			$mail->From = utf8_decode( $this->credenciales['USER'] );
			$mail->FromName = utf8_decode( $this->credenciales['FROM'] );
			$mail->Subject = utf8_decode( $asunto );
			$mail->Body = utf8_decode( $mensaje );
			$total = 0;
			if ( count( $adjuntos ) > 0 ) {
				foreach ( $adjuntos as $adjunto ) {
					if ( strlen($adjunto) > 0 && file_exists($adjunto) && !is_dir($adjunto) ) {
						$adjunto = str_replace('\\', '/', $adjunto);
						$w = explode( '/', $adjunto );
						$n = array_pop( $w );
						$mail->AddAttachment( $adjunto, $n );
					}
				}
				$txt = nl2br( $mensaje );
				$mail->MsgHTML( utf8_decode( $txt ) );
			}
			foreach ( $destinatarios as $destinatario ) {
				$destinatario = trim($destinatario);
				if ( strlen($destinatario) > 5 ) {
					$mail->AddAddress( $destinatario, $destinatario );
					$total = $total + 1;
				}
			}
			if ( $total > 0 ) {
				$enviado = $mail->Send();
				$i = 1; 
				while ( (!$enviado) && ($i < 3) ) {
					sleep(3); 
					$enviado = $mail->Send(); 
					$i = $i + 1;
				}
				if ( $enviado ) { $resultado['estado'] = true; }
				$resultado['mensaje'] = $mail->ErrorInfo;
			}
		} catch (Exception $e) {
			$resultado['mensaje'] = $e->errorMessage();
		} catch (\Exception $e) {
		  $resultado['mensaje'] = $e->getMessage();
		}
		unset($mail);
		return $resultado;
	}
}

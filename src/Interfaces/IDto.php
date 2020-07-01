<?php
namespace MasExperto\ME\Interfaces;

interface IDto {
	public function get( $nombre, $objeto, $predeterminado );
	public function set( $nombre, $valor, $objeto );
	public function getset( $nombre );
	public function extraerResultado( $etiqueta );
	public function traspasarPeticion( $opcion );
	public function Vaciar( $todo );
}
?>
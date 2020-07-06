<?php
namespace MasExperto\ME\Interfaces;

interface IAdaptador {
	public function reemplazarMetadatos( $uid, &$modelo );
	public function combinarMetadatos( $uid, &$modelo );
	public function cambiarValores();
	public function cotejarPeticion( $info );
}

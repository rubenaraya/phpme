<?php
namespace MasExperto\ME\Interfaces;

interface IAdaptador {
	public function reemplazarMetadatos( $uid, &$dto, &$modelo );
	public function combinarMetadatos( $uid, &$dto, &$modelo );
	public function cambiarValores();
	public function cotejarPeticion($info );
}

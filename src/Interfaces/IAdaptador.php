<?php
namespace MasExperto\ME\Interfaces;

interface IAdaptador {
	public function combinarMetadatos( $uid, &$modelo, $reemplazar );
	public function cambiarValores();
	public function asignarPredeterminados();
	public function cotejarPeticion( $info );
}

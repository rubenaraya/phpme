<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IAdaptador;

abstract class Adaptador implements IAdaptador
{
	protected $modelo = null;
	protected $uid;
	public $objeto;
	public $clase;
	public $vista;
	public $esquema;
	public $ruta = array();
	public $sql = array();
	public $T = array();
	public $I = array();
	public $D = array();
	public $A = array();
	public $R = array();

	function __construct() {
		$this->ruta['xml'] = '';
		$this->ruta['xsl'] = '';
		$this->esquema = '';
        $this->vista = '';
        $this->objeto = '';
	}
	function __destruct() {
		unset($this->modelo, $this->sql, $this->T, $this->I, $this->D, $this->A, $this->R);
	}
	public function combinarMetadatos( $uid, &$modelo, $reemplazar = false ) {
		$this->uid = $uid;
		$this->modelo = &$modelo;
		$this->modelo->R = $this->R;
		if ( $reemplazar ) {
            if ( count($this->T)>0 ) { $this->modelo->T = $this->T; }
            if ( count($this->I)>0 ) { $this->modelo->I = $this->I; }
            if ( count($this->D)>0 ) { $this->modelo->D = $this->D; }
            if ( count($this->A)>0 ) { $this->modelo->A = $this->A; }
            if ( count($this->sql)>0 ) { $this->modelo->sql = array_replace($this->modelo->sql, $this->sql); }
        } else {
            $this->modelo->T = array_replace($this->modelo->T, $this->T);
            $this->modelo->I = array_replace($this->modelo->I, $this->I);
            $this->modelo->D = array_replace($this->modelo->D, $this->D);
            $this->modelo->A = array_replace($this->modelo->A, $this->A);
            $this->modelo->sql = array_replace($this->modelo->sql, $this->sql);
        }
		$this->cambiarValores();
	}
	public function cotejarPeticion($info = '' ) {
		$estado = 1;
		$mensaje = '';
		foreach ( $this->modelo->A as $nombre => $valor ) {
			if ( substr_count( ','.$valor['validar'].',', ','.$info.',' )>0 ) {
				$validacion = $this->modelo->Validar( $nombre, $this->modelo->dto->get($nombre) );
				$mensaje .= $validacion['mensaje'];
				$estado = ( $validacion['estado'] == 0 ? -1 : $estado );
				if ( $estado ) {
					$this->modelo->dto->getset( $nombre );
				}
			}
		}
		return array(
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}
	public function cambiarValores() {
	}
	public function asignarPredeterminados() {
	}
}

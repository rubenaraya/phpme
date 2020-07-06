<?php
namespace MasExperto\ME\Interfaces;

interface IModelo {
	public function Atributos( $nombre );
	public function Consultar();
	public function Nuevo();
	public function Agregar();
	public function Abrir();
	public function Editar();
	public function Borrar();
	public function Cambiar();
	public function Imagen();
	public function Archivo();
	public function Refrescar();
	public function Registrar();
	public function Ver();
    public function Cotejar( $caso );
    public function Validar( $nombre, $valor );
}

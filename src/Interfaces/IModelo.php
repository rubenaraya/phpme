<?php
namespace MasExperto\ME\Interfaces;

interface IModelo {
	public function Atributos( $nombre );
	public function Consultar( &$dto );
	public function Nuevo( &$dto );
	public function Agregar( &$dto );
	public function Abrir( &$dto );
	public function Editar( &$dto );
	public function Borrar( &$dto );
	public function Cambiar( &$dto );
	public function Imagen( &$dto );
	public function Archivo( &$dto );
	public function Descargar( &$dto );
	public function Refrescar( &$dto );
	public function Registrar( &$dto );
	public function Ejecutar( &$dto );
	public function Ver( &$dto );
	public function Exportar( &$dto );
	public function Validar( $nombre, $valor );
}
?>
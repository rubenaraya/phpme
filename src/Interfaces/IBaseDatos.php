<?php
namespace MasExperto\ME\Interfaces;

interface IBaseDatos {
	public function Conectar( $credenciales, &$dto );
	public function consultarColeccion( $instruccion, $etiqueta, $envolver, $nivel );
	public function consultarElemento( $instruccion, $etiqueta, $envolver, $nivel );
	public function consultarValores( $instruccion, $etiqueta );
	public function agregarElemento( $instruccion, $etiqueta );
	public function editarElementos( $instruccion, $etiqueta );
	public function borrarElementos( $instruccion, $etiqueta );
	public function generarExpresion( $tipo, $datos, $tabla, $uid );
	public function reemplazarValores( $expresion, $lista );
	public function aplicarFiltro( $filtro, $claves, $instruccion, $tabla );
	public function Cerrar();
}

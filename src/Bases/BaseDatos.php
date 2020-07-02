<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IBaseDatos;

abstract class BaseDatos implements IBaseDatos
{
	const FILTRO_CONTIENE		= 101;
	const FILTRO_PALABRAS		= 102;
	const FILTRO_TEXTO			= 103;
	const FILTRO_NUMERO			= 104;
	const FILTRO_PERIODO		= 105;
	const FILTRO_RANGO_FECHAS	= 106;
	const FILTRO_RANGO_HORAS	= 107;
	const FILTRO_RANGO_NUMEROS	= 108;
	const FILTRO_RANGO_DIAS		= 109;
	const FILTRO_LISTA_PALABRAS	= 110;
	const FILTRO_LISTA_NUMEROS	= 111;
	const FILTRO_INCLUYE		= 112;
	const FILTRO_COINCIDE		= 113;
	const EXPR_SELECT			= 201;
	const EXPR_INSERT			= 202;
	const EXPR_UPDATE			= 203;
	const EXPR_DELETE			= 204;

	protected $DTO = null;
	protected $conexion = null;
	protected $credenciales = null;

	function __construct() {}
	function __destruct() {
		unset($this->conexion);
		unset($this->DTO);
	}
}

<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\IAlmacen;

abstract class Almacen implements IAlmacen
{
	const TEMP		= 101;
	const PRIVADO	= 102;
	const PUBLICO	= 103;
	const F_JSON	= 201;
	const F_TXT		= 202;
	const F_HTML	= 203;
	const F_XML		= 204;
	const F_PDF		= 205;
	const F_XLS		= 206;
	const F_XLSX	= 207;

	public $rutas = array();

	function __construct() {}
	function __destruct() {}
}

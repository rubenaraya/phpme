<?php
namespace MasExperto\ME\Bases;

use MasExperto\ME\Interfaces\ICorreo;

abstract class Correo implements ICorreo
{
	protected $credenciales = null;

	function __construct() {}
	function __destruct() {}
}

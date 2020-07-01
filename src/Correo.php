<?php
namespace MasExperto\ME;
use MasExperto\ME\Interfaces\ICorreo;

abstract class Correo implements ICorreo
{
	//PROPIEDADES
		protected $credenciales = null;

	//CONSTRUCTOR
		function __construct() {}
		function __destruct() {}
}

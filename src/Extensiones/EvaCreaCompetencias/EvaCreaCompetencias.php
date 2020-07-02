<?php 
namespace MasExperto\ME\Extensiones\EvaCreaCompetencias;

use MasExperto\ME\Clases\PresentadorXml;
use MasExperto\ME\Instructor;
use MasExperto\ME\M;

class EvaCreaCompetencias extends Instructor
{
	function __construct() {
		parent::__construct();
			$this->clase = str_replace( array('SER\\', 'EXT\\'), '', static::class );
			$this->esquema = $this->clase . '.xml';
			$this->vista = $this->clase . '.xsl';
			$this->ruta['xml'] = __DIR__;
			$this->ruta['xsl'] = __DIR__;

		$this->sql['guardar_respuestas'] = "UPDATE respuestas SET {{expresion}} rep_estado=1 WHERE rep_idactividad='{{uid}}' AND rep_idusuario='{{idusuario}}' AND rep_app='{{app_id}}'";
		$this->sql['respuestas_agregar'] = "INSERT INTO respuestas (rep_idactividad, rep_idusuario, rep_clase, rep_app) VALUES ('{{uid}}', '{{idusuario}}', '{{clase}}', '{{app_id}}')";
		/*
			$this->sql['recuentos_ver'] = "SELECT item, opcion, total, porcentaje FROM recuentos WHERE idencuesta='{{id}}' ORDER BY id";
			$this->sql['respuestas_consultar'] = "SELECT respuestas.id AS idrespuesta, idafiliado, afiliados.nombre, afiliados.email, cat_sexo, cat_edad, cat_educa, cat_zona, cat_sector, cat_grupo, idgrupo, f1, f2, f3, f4, f5, f6, f7, f8, f9, f10 FROM respuestas INNER JOIN afiliados ON (respuestas.idafiliado=afiliados.id) WHERE idencuesta='{{id}}' AND respuestas.estado=3 ORDER BY respuestas.id";
			$this->sql['recuentos_consultar'] = "SELECT * FROM recuentos WHERE idencuesta='{{id}}' ORDER BY id";
			$this->sql['recalculos_consultar'] = "SELECT * FROM recalculos WHERE idencuesta='{{id}}' ORDER BY id";
			$this->sql['recuentos_borrar'] = "DELETE FROM recuentos WHERE idencuesta='{{id}}'";
			$this->sql['recalculos_borrar'] = "DELETE FROM recalculos WHERE idencuesta='{{id}}'";
		*/
		/* TODO: ¿Establecer atributos especiales para reglas de control de flujo? */
	}
	public function cambiarValores() {
	}
	public function consultarInformacion( $info = '' ) {
		/* Consumido por: ModeloActividades->Ver */
		$estado = 0;
		$mensaje = '';
		$nombre = '';
		$estilos = '';
		$contenido = '';
		$opc = array();
		switch ($info) {
			case 'avance': 
				$estado = 1;
				break;
			case 'resulta': 
				$estado = 1;
				break;
			case 'informe': 
				$estado = 1;
				break;
			case 'previa': 
				$estado = 1;
				break;
			case 'exporta': 
				$estado = 1;
				break;
		}
		if ( $estado == 1 ) {
			$this->documento = simplexml_load_file( $this->ruta['xml'] . '/' . $this->esquema );
			$base = $this->modelo->almacen->validarNombre( $this->docXpath("//instructor/titulo") );
			$nombre = $base . '_' . strval($this->uid);
			$opc['info'] = $info;
			$opc['incluir'] = M::E('RUTA/ME') . '/Recursos/Instructor.xsl';
			$estilos = M::E('RUTA/SERVICIO') .'/pdf.css';
			$presentador = new PresentadorXml();
			$presentador->documento = $this->documento;
			$presentador->crearVista();
			$presentador->anexarDatos( $this->dto );
			$presentador->anexarMatriz( $this->modelo->D );
			$presentador->anexarMatriz( $this->modelo->A, 'a' );
			$contenido = $presentador->Transformar( $this->vista, $this->ruta['xsl'], $opc );
		} else {
			$mensaje = $this->modelo->T['info-no-encontrada'];
			$contenido = $mensaje;
		}
		unset( $presentador );
		return array(
			'contenido'=> $contenido,
			'nombre'=> $nombre,
			'estilos'=> $estilos,
			'estado'=> $estado,
			'mensaje'=> $mensaje
		);
	}

	public function aplicarCalculos( $opciones ) {
	}
	public function exportarRespuestas( $opciones = array() ) {
		//Consumido por: ModeloActividades (Convertir?)
	}
	public function procesarResultados( $opciones = array() ) {
		//Consumido por: ModeloActividades (?)
	}
	public function generarInforme( $opciones = array() ) {
		//Consumido por: ModeloActividades (?)
	}
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package local
 * @subpackage reservasalas
 * @copyright 2014 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
 *            Nicolás Bañados Valladares (nbanados@alumnos.uai.cl)
 *            2015 Martín Améstica Montenegro (maamestica@alumnos.uai.cl)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined ( 'MOODLE_INTERNAL' ) || die ();
class tables {
	public static function get_rooms($edificioid = null) {
		global $DB, $OUTPUT;
		if ($edificioid) {
			$salas = $DB->get_records ( 'reservasalas_salas', array (
					'edificios_id' => $edificioid 
			) );
		} else {
			$salas = $DB->get_records ( 'reservasalas_salas' );
			// $salas = $DB->get_records_sql('select * from {reservasalas_salas} order by edificios_id');
		}
		$table = new html_table ();
		$table->head = array (
				get_string ( 'campus', 'local_reservasalas' ),
				get_string ( 'building', 'local_reservasalas' ),
				get_string ( 'room', 'local_reservasalas' ),
				get_string ( 'roomtype', 'local_reservasalas' ),
				get_string ( 'capacity', 'local_reservasalas' ),
				get_string ( 'adjustments', 'local_reservasalas' ) 
		);
		foreach ( $salas as $sala ) {
			$edificio = $DB->get_record ( 'reservasalas_edificios', array (
					'id' => $sala->edificios_id 
			) );
			$sede = $DB->get_record ( 'reservasalas_sedes', array (
					'id' => $edificio->sedes_id 
			) );
			
			if ($edificioid) {
				$editurl_sala = new moodle_url ( 'rooms.php', array (
						'action' => 'editar',
						'prevaction' => 'verporedificio',
						'idsala' => $sala->id,
						'sesskey' => sesskey () 
				) );
				$deleteurl_sala = new moodle_url ( 'rooms.php', array (
						'action' => 'borrar',
						'prevaction' => 'verporedificio',
						'edificio' => $edificio->id,
						'idsala' => $sala->id,
						'sesskey' => sesskey () 
				) );
			} else {
				$editurl_sala = new moodle_url ( 'rooms.php', array (
						'action' => 'editar',
						'idsala' => $sala->id,
						'sesskey' => sesskey () 
				) );
				$deleteurl_sala = new moodle_url ( 'rooms.php', array (
						'action' => 'borrar',
						'idsala' => $sala->id,
						'sesskey' => sesskey () 
				) );
			}
			
			$editicon_sala = new pix_icon ( 't/editstring', get_string ( 'modify', 'local_reservasalas' ) );
			$editaction_sala = $OUTPUT->action_icon ( $editurl_sala, $editicon_sala, new confirm_action ( get_string ( 'areyoueditroom', 'local_reservasalas' ) ) );
			
			$deleteicon_sala = new pix_icon ( 't/delete', get_string ( 'remove', 'local_reservasalas' ) );
			$deleteaction_sala = $OUTPUT->action_icon ( $deleteurl_sala, $deleteicon_sala, new confirm_action ( get_string ( 'areyouremoveroom', 'local_reservasalas' ) ) );
			
			$typeRoom = '';
			if ($sala->tipo == 1) {
				
				$typeRoom = get_string ( 'classroom', 'local_reservasalas' );
			} else if ($sala->tipo == 2) {
				
				$typeRoom = get_string ( 'studyroom', 'local_reservasalas' );
			} else if ($sala->tipo == 3) {
				
				$typeRoom = get_string ( 'reunionroom', 'local_reservasalas' );
			}
			
			$table->data [] = array (
					$sede->nombre,
					$edificio->nombre,
					$sala->nombre,
					$typeRoom,
					$sala->capacidad,
					$editaction_sala . $deleteaction_sala 
			);
		}
		return $table;
	}
	// Tabla utilizada en edificios.php, muestra todos los edificios que existen.
	public static function get_buildings() {
		global $DB, $OUTPUT;
		// $edificios = $DB->get_records('reservasalas_edificios');
		$edificios = $DB->get_records_sql ( 'select * from {reservasalas_edificios} order by sedes_id' );
		$table = new html_table ();
		$table->head = array (
				get_string ( 'campus', 'local_reservasalas' ),
				get_string ( 'building', 'local_reservasalas' ),
				get_string ( 'adjustments', 'local_reservasalas' ) 
		);
		
		foreach ( $edificios as $edificio ) {
			$sede = $DB->get_record ( 'reservasalas_sedes', array (
					'id' => $edificio->sedes_id 
			) );
			
			$editurl_edificios = new moodle_url ( 'buildings.php', array (
					'action' => 'editar',
					'edificio' => $edificio->id 
			) );
			$editurl_icon = new pix_icon ( 't/editstring', get_string ( 'modify', 'local_reservasalas' ) );
			$editaction_edificios = $OUTPUT->action_icon ( $editurl_edificios, $editurl_icon, new confirm_action ( get_string ( 'areyousureyouwanttoedit', 'local_reservasalas' ) ) );
			
			$deleteurl_sedes = new moodle_url ( 'buildings.php', array (
					'action' => 'borrar',
					'edificio' => $edificio->id,
					'sesskey' => sesskey () 
			) );
			$deleteicon_sedes = new pix_icon ( 't/delete', get_string ( 'remove', 'local_reservasalas' ) );
			$deleteaction_sedes = $OUTPUT->action_icon ( $deleteurl_sedes, $deleteicon_sedes, new confirm_action ( get_string ( 'thisabouttoremove', 'local_reservasalas' ) ) );
			
			// CAMBIAR URL
			$seeurl_salas = new moodle_url ( 'rooms.php', array (
					'action' => 'verporedificio',
					'edificio' => $edificio->id,
					'sesskey' => sesskey () 
			) );
			$seeicon_salas = new pix_icon ( 'i/preview', get_string ( 'seestudyrooms', 'local_reservasalas' ) );
			$seeaction_salas = $OUTPUT->action_icon ( $seeurl_salas, $seeicon_salas );
			
			$table->data [] = array (
					$sede->nombre,
					$edificio->nombre,
					$seeaction_salas . $editaction_edificios . $deleteaction_sedes 
			);
		}
		return $table;
	}
	// Utilizado en sedes.php, tabla que lista todas las sedes existentes.
	public static function get_campus() {
		global $DB, $OUTPUT;
		$places = $DB->get_records ( 'reservasalas_sedes' );
		
		$table = new html_table ();
		$table->head = array (
				get_string ( 'campus', 'local_reservasalas' ),
				get_string ( 'adjustments', 'local_reservasalas' ) 
		);
		foreach ( $places as $campus ) {
			$deleteurl_sedes = new moodle_url ( 'campus.php', array (
					'action' => 'borrar',
					'idsede' => $campus->id,
					'sesskey' => sesskey () 
			) );
			$deleteicon_sedes = new pix_icon ( 't/delete', get_string ( 'remove', 'local_reservasalas' ) );
			$deleteaction_sedes = $OUTPUT->action_icon ( $deleteurl_sedes, $deleteicon_sedes, new confirm_action ( get_string ( 'doyouwantdeletesite', 'local_reservasalas' ) ) );
			
			$editurl_sedes = new moodle_url ( 'campus.php', array (
					'action' => 'editar',
					'prevaction' => 'ver',
					'idsede' => $campus->id,
					'sesskey' => sesskey () 
			) );
			$editicon_sedes = new pix_icon ( 'i/edit', get_string ( 'edit', 'local_reservasalas' ) );
			$editaction_sedes = $OUTPUT->action_icon ( $editurl_sedes, $editicon_sedes, new confirm_action ( get_string ( 'doyouwanteditsite', 'local_reservasalas' ) ) );
			
			$table->data [] = array (
					$campus->nombre,
					$editaction_sedes . $deleteaction_sedes 
			);
		}
		return $table;
	}
	// Utilizado en resources.php, genera tabla con todos los recurso existentes.
	public static function get_resources() {
		global $DB, $OUTPUT;
		$resources = $DB->get_records ( 'reservasalas_recursos' );
		
		$table = new html_table ();
		$table->head = array (
				get_string ( 'resources', 'local_reservasalas' ),
				get_string ( 'adjustments', 'local_reservasalas' ) 
		);
		foreach ( $resources as $resource ) {
			$deleteurl_resource = new moodle_url ( 'resources.php', array (
					'action' => 'borrar',
					'idresource' => $resource->id,
					'sesskey' => sesskey () 
			) );
			$deleteicon_resource = new pix_icon ( 't/delete', get_string ( 'remove', 'local_reservasalas' ) );
			$deleteaction_resource = $OUTPUT->action_icon ( $deleteurl_resource, $deleteicon_resource, new confirm_action ( get_string ( 'doyouwantdelete', 'local_reservasalas' ) ) );
			
			$editurl_resource = new moodle_url ( 'resources.php', array (
					'action' => 'editar',
					'prevaction' => 'ver',
					'idresource' => $resource->id,
					'sesskey' => sesskey () 
			) );
			$editicon_resource = new pix_icon ( 'i/edit', get_string ( 'edit', 'local_reservasalas' ) );
			$editaction_resource = $OUTPUT->action_icon ( $editurl_resource, $editicon_resource, new confirm_action ( get_string ( 'doyouwantedit', 'local_reservasalas' ) ) );
			
			$table->data [] = array (
					$resource->nombre,
					$editaction_resource . $deleteaction_resource 
			);
		}
		return $table;
	}
}
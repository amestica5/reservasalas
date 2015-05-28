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
// a

/**
 *
 * @package local
 * @subpackage reservasalas
 * @copyright 2014 Francisco García Ralph (francisco.garcia.ralph@gmail.com)
 *            Nicolás Bañados Valladares (nbanados@alumnos.uai.cl)
 *            2015 Martín Améstica Montenegro (maamestica@alumnos.uai.cl)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (dirname ( dirname ( __FILE__ ) ) . '/../../config.php'); // requiered
require_once ($CFG->dirroot . '/local/reservasalas/admin/forms.php');
require_once ($CFG->dirroot . '/local/reservasalas/admin/tables.php');

// context setup
global $PAGE, $CFG, $OUTPUT, $DB;
require_login ();
$url = new moodle_url ( '/local/reservasalas/admin/rooms.php' );
$context = context_system::instance (); // context_system::instance();
$PAGE->set_context ( $context );
$PAGE->set_url ( $url );
$PAGE->set_pagelayout ( 'standard' );

// Capabilities
// check if user has the capacity to view specific content
// example: a student doesn´t have the capacity to view the content

if (! has_capability ( 'local/reservasalas:administration', $context )) {
	print_error ( get_string ( 'INVALID_ACCESS', 'Reserva_Sala' ) );
}

// define action var, exist diferents tipes like:: ver, editar, borrar, agregar, crear, informe
// every ACTION shows a diferent version of page, depends of the activities that user realice
// By defect the ACTION is ver, for this reason the page shows a table with all reasourse.

$action = optional_param ( 'action', 'ver', PARAM_TEXT );

// check if exist campus and buildings because rooms are in these.
if (! $DB->get_records ( 'reservasalas_salas' )) {
	$campus = 1;
	$buildings = 1;
	if (! $DB->get_records ( 'reservasalas_edificios' )) {
		$buildings = 0;
		if (! $DB->get_records ( 'reservasalas_sedes' )) {
			$campus = 0;
		}
	}
	
	// if doesn´t exist buildins the action change to sinedificios
	// this page redirect to create campus and buildings,
	// after that we have the capacity to create a new room
	
	if ($action == 'ver') {
		$action = "sinedificios";
	}
}

// Implementation create action
// ask for number of rooms will be create then redirect to add action

if ($action == 'crear') {
	$createroomsform = new create_rooms ();
	if ($createroomsform->is_cancelled ()) {
		$action = 'ver'; // ($redirecturl);
	} else if ($fromform = $createroomsform->get_data ()) {
		// redirecciona y envia parametros por url
		$redirecturl = new moodle_url ( 'rooms.php', array (
				'action' => 'agregar',
				'sede' => $fromform->SedeEdificio,
				'salas' => $fromform->sala,
				'type' => $fromform->roomType 
		) );
		redirect ( $redirecturl );
	}
}

// Implementation add room
// allow to add a number of rooms previously inform in create action.

if ($action == 'agregar') {
	
	$campusid = optional_param ( 'sede', 0, PARAM_INT ); // --> TODO, rename for campus or building. **$edificioid = optional_param('sede', 0, PARAM_INT);
	$roomType = optional_param ( 'type', 1, PARAM_INT );
	
	if ($buildingdata = $DB->get_record ( 'reservasalas_edificios', array (
			'id' => $campusid 
	) )) {
		$campusdata = $DB->get_record ( 'reservasalas_sedes', array (
				'id' => $buildingdata->sedes_id 
		) );
	}
	
	$rooms = optional_param ( 'salas', 0, PARAM_INT );
	$addroomsform = new add_rooms ( null, array (
			'sala' => $rooms,
			'SedeEdificio' => $campusid,
			'type' => $roomType 
	) );
	
	$redirecturl = new moodle_url ( 'index.php', array (
			'action' => 'agregarsalas' 
	) );
	
	if ($addroomsform->is_cancelled ()) {
		$action = 'ver';
	} else if ($fromform = $addroomsform->get_data ()) {
		
		$action = "informe";
	}
}

// Implementation edit action
// edit an existing room

if ($action == 'editar') {
	$idroom = required_param ( 'idsala', PARAM_INT );
	$prevaction = optional_param ( 'prevaction', 'ver', PARAM_TEXT );
	$editroom = $DB->get_record ( 'reservasalas_salas', array (
			'id' => $idroom 
	) );
	$editroombuilding = $DB->get_record ( 'reservasalas_edificios', array (
			'id' => $editroom->edificios_id 
	) );
	$campusdata = $DB->get_record ( 'reservasalas_sedes', array (
			'id' => $editroombuilding->sedes_id 
	) );
	
	$editroomsform = new edit_rooms ( null, array (
			'prevaction' => $prevaction,
			'edificioid' => $editroombuilding->id 
	) );
	if ($editroomsform->is_cancelled ()) {
		$action = $prevaction;
		// if form have correct info the rooms will be edit.
	} else if ($fromform = $editroomsform->get_data ()) {
		
		// get edit data about rooms from form
		$editroom->nombre = $fromform->cambiarnombresala;
		$editroom->nombre_pc = $fromform->cambiarnombrepc;
		$editroom->capacidad = $fromform->cap;
		$editroom->tipo = $fromform->roomType;
		
		// adit rooms with the new edit data
		$DB->update_record ( 'reservasalas_salas', $editroom );
		$resources = $DB->get_records ( 'reservasalas_recursos' );
		foreach ( $resources as $resource ) {
			$conditional = $resource->id;
			$roomdata = $DB->get_record ( 'reservasalas_salas', array (
					'nombre' => $fromform->cambiarnombresala,
					'edificios_id' => $editroombuilding->id,
					'tipo' => $fromform->roomType 
			) );
			// $resourcechange = $DB->get_records('reservasalas_salarecursos', array('salas_id' => $room_id->id));
			// condicionar si existia o no el recurso seleccionado en el formulario de edicion
			// Se agrega o no la relacion sala-recurso
			if ($_REQUEST [$conditional] == '1') { // --> TODO, change request form
				if ($DB->get_records ( 'reservasalas_salarecursos', array (
						'recursos_id' => $conditional,
						'salas_id' => $roomdata->id 
				) ) == null) {
					$DB->insert_record ( 'reservasalas_salarecursos', array (
							'recursos_id' => $resource->id,
							'salas_id' => $roomdata->id 
					) );
				}
			} else if ($_REQUEST [$conditional] == '0') { // --> TODO, change request form
				if ($DB->get_records ( 'reservasalas_salarecursos', array (
						'recursos_id' => $conditional,
						'salas_id' => $roomdata->id 
				) ) != null) {
					$roomdata = $DB->get_record ( 'reservasalas_salas', array (
							'nombre' => $fromform->cambiarnombresala 
					) );
					$DB->delete_records ( 'reservasalas_salarecursos', array (
							'recursos_id' => $resource->id,
							'salas_id' => $roomdata->id 
					) );
				}
			}
		}
		$action = $prevaction;
	}
}

// Implementation delete action
// delete an existing room

if ($action == 'borrar') {
	
	$roomid = required_param ( 'idsala', PARAM_INT );
	$prevaction = optional_param ( 'prevaction', 'ver', PARAM_TEXT );
	if (confirm_sesskey ()) {
		$DB->delete_records ( 'reservasalas_salas', array (
				'id' => $roomid 
		) );
		$DB->delete_records ( 'reservasalas_reservas', array (
				'salas_id' => $roomid 
		) );
		$DB->delete_records ( 'reservasalas_salarecursos', array (
				'salas_id' => $roomid 
		) );
		$action = $prevaction;
	} else {
		print_error ( "ERROR" );
	}
}

// Implementation view action
// display a table with all rooms

if ($action == 'ver') {
	$tabla = tables::get_rooms ();
} else if ($action == 'verporedificio') {
	
	$edificioid = optional_param ( 'edificio', NULL, PARAM_INT );
	$buildingdata = $DB->get_record ( 'reservasalas_edificios', array (
			'id' => $edificioid 
	) );
	$campusdata = $DB->get_record ( 'reservasalas_sedes', array (
			'id' => $buildingdata->sedes_id 
	) );
	
	$tabla = tables::get_rooms ( $edificioid );
}

// shows of actions activities.
// **************************************************************************************************************************************************
if ($action == 'ver') {
	$o = '';
	
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/sedes.php' ), get_string ( 'places', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	$title = get_string ( 'seeandmodrooms', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'rooms.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'studyrooms', 'local_reservasalas' ) );
	$url = new moodle_url ( "rooms.php", array (
			'action' => 'crear' 
	) );
	if (isset ( $nocreada )) {
		$o .= $nocreada;
	}
	$o .= $OUTPUT->single_button ( $url, get_string ( 'createnewrooms', 'local_reservasalas' ) );
	
	$o .= html_writer::table ( $tabla );
	$o .= $OUTPUT->single_button ( $url, get_string ( 'createnewrooms', 'local_reservasalas' ) );
	$o .= $OUTPUT->footer ();
} else if ($action == 'verporedificio') {
	
	$o = '';
	$title = get_string ( 'seeandmodrooms', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'rooms.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	
	$secondtitle = "<h4>" . get_string ( 'campus', 'local_reservasalas' ) . ": " . $campusdata->nombre . "</h4><h4>" . get_string ( 'building', 'local_reservasalas' ) . ": " . $buildingdata->nombre . "</h4>";
	
	$o .= "<h2>" . $secondtitle . "</h2><br>";
	$o .= html_writer::table ( $tabla );
	$o .= "<hr>";
	$o .= $OUTPUT->single_button ( 'buildings.php', get_string ( 'backtobuildings', 'local_reservasalas' ) );
	$o .= $OUTPUT->footer ();
} else if ($action == 'crear') {
	$o = '';
	$title = get_string ( 'roomscreates', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), '' );
	$PAGE->navbar->add ( $title, 'rooms.php?action=crear' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( $title );
	ob_start ();
	$createroomsform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	$o .= $OUTPUT->footer ();
} else if ($action == 'agregar') {
	$o = '';
	
	$title = get_string ( 'roomscreates', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'rooms.php' );
	$PAGE->navbar->add ( $title, 'rooms.php?action=crear' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( $title );
	
	$secondtitle = "<h4>" . get_string ( 'campus', 'local_reservasalas' ) . ": " . $campusdata->nombre . "</h4><h4>" . get_string ( 'building', 'local_reservasalas' ) . ": " . $buildingdata->nombre . "</h4>";
	$o .= "<h2>" . $secondtitle . "</h2><br>";
	
	ob_start ();
	$addroomsform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	$o .= $OUTPUT->footer ();
} else if ($action == 'editar') {
	$o = '';
	$title = get_string ( 'roomedit', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'rooms.php' );
	$PAGE->navbar->add ( $title, '' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( get_string ( 'roomedit', 'local_reservasalas' ) );
	$o .= "<h4>" . get_string ( 'campus', 'local_reservasalas' ) . ": $campusdata->nombre </h4>";
	$o .= "<h4>" . get_string ( 'building', 'local_reservasalas' ) . ": $editroombuilding->nombre </h4>";
	$o .= "<br>";
	
	ob_start ();
	$editroomsform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	
	$o .= $OUTPUT->footer ();
} 

/**
 * this action doesn't work yet.
 */

else if ($action == 'informe') { // inform about creation rooms status
	$o = '';
	
	$title = get_string ( 'report', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'rooms.php' );
	$PAGE->navbar->add ( get_string ( 'roomscreates', 'local_reservasalas' ), 'rooms.php?action=crear' );
	$PAGE->navbar->add ( $title, 'rooms.php?action=informe' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( $title );
	
	$secondtitle = "<h4>" . get_string ( 'campus', 'local_reservasalas' ) . ": " . $fromform->nombresede . "</h4><h4>" . get_string ( 'building', 'local_reservasalas' ) . ": " . $fromform->nombreedificio . "</h4>";
	$o .= "<h2>" . $secondtitle . "</h2><br>";
	
	ob_start ();
	// create a table with any new room that was create
	// inform with creation room generate an error, or if was succesfull create
	$table = new html_table ();
	$table->head = array (
			get_string ( 'room', 'local_reservasalas' ),
			get_string ( 'capacity', 'local_reservasalas' ),
			get_string ( 'created', 'local_reservasalas' ),
			get_string ( 'report', 'local_reservasalas' ) 
	);
	
	for($i = 0; $i < $fromform->number; $i ++) {
		$nsala = "sala" . $i;
		$pc = "pc" . $i;
		$ntype = "cap$i";
		$nres = "res$i";
		// check if the room was create
		if ($DB->get_record ( 'reservasalas_salas', Array (
				'edificios_id' => $fromform->edificio,
				'nombre' => $_REQUEST [$nsala],
				'tipo' => $fromform->typeRoom 
		) )) {
			$row = new html_table_row ( array (
					$_REQUEST [$nsala],
					$_REQUEST [$ntype],
					'No',
					get_string ( 'nameoftheexisting', 'local_reservasalas' ) 
			) );
			$table->data [] = $row;
		} else {
			// if the room doesn´t exist create these
			if (($_REQUEST [$ntype] + 1) > 1 || $_REQUEST [$ntype] == 0) {
				$DB->insert_record ( 'reservasalas_salas', Array (
						'id' => '',
						'nombre' => $_REQUEST [$nsala],
						'nombre_pc' => $_REQUEST [$pc],
						'edificios_id' => $fromform->edificio,
						'tipo' => $fromform->typeRoom,
						'capacidad' => $_REQUEST [$ntype] 
				) );
				$row = new html_table_row ( array (
						$_REQUEST [$nsala],
						$_REQUEST [$ntype],
						get_string ( 'yes', 'local_reservasalas' ),
						get_string ( 'clasroomsuccesscreated', 'local_reservasalas' ) 
				) );
				$table->data [] = $row;
				$resources = $DB->get_records ( 'reservasalas_recursos' );
				// create relationship of room-resourse
				foreach ( $resources as $resource ) {
					$conditional = $i . $resource->id;
					if ($_REQUEST [$conditional] == '1') {
						$roomdata = $DB->get_record ( 'reservasalas_salas', array (
								'nombre' => $_REQUEST [$nsala],
								'edificios_id' => $fromform->edificio,
								'tipo' => $fromform->typeRoom 
						) );
						$DB->insert_record ( 'reservasalas_salarecursos', array (
								'recursos_id' => $resource->id,
								'salas_id' => $roomdata->id 
						) );
					}
				}
			} else {
				$row = new html_table_row ( array (
						$_REQUEST [$nsala],
						$_REQUEST [$ntype],
						'No',
						get_string ( 'roomcapacityacepted', 'local_reservasalas' ) 
				) );
				$table->data [] = $row;
			}
		}
	}
	$url = new moodle_url ( "rooms.php", array (
			'action' => 'crear' 
	) );
	$url2 = new moodle_url ( "rooms.php", array (
			'action' => 'ver' 
	) );
	$row = new html_table_row ( array (
			'',
			'',
			'',
			'' 
	) );
	$table->data [] = $row;
	$row = new html_table_row ( array (
			$OUTPUT->single_button ( $url, get_string ( 'createnewrooms', 'local_reservasalas' ) ),
			$OUTPUT->single_button ( $url2, get_string ( 'next', 'local_reservasalas' ) ),
			'',
			'' 
	) );
	$table->data [] = $row;
	echo html_writer::table ( $table );
	$o .= ob_get_contents ();
	ob_end_clean ();
	$o .= $OUTPUT->footer ();
} else if ($action == "sinedificios") {
	$o = '';
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/sedes.php' ), get_string ( 'places', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	$title = get_string ( 'seeandmodrooms', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'rooms.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'studyrooms', 'local_reservasalas' ) );
	$o .= $OUTPUT->heading ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	
	if ($campus == 0) {
		$url = new moodle_url ( "sedes.php", array (
				'action' => 'crear' 
		) );
		$o .= "<center><strong>" . get_string ( 'therearenotsites', 'local_reservasalas' ) . "<strong><center>";
		$o .= $OUTPUT->single_button ( $url, get_string ( 'campuscreate', 'local_reservasalas' ) );
	} elseif ($buildings == 0) {
		$url = new moodle_url ( "buildings.php", array (
				'action' => 'crear' 
		) );
		$o .= "<center><strong>" . get_string ( 'therearenotbuildings', 'local_reservasalas' ) . "<strong><center>";
		$o .= $OUTPUT->single_button ( $url, get_string ( 'createbuildings', 'local_reservasalas' ) );
	} else {
		
		$url = new moodle_url ( "rooms.php", array (
				'action' => 'crear' 
		) );
		$o .= "<center><strong>" . get_string ( 'thereisnotrooms', 'local_reservasalas' ) . "<strong><center>";
		$o .= $OUTPUT->single_button ( $url, get_string ( 'createrooms', 'local_reservasalas' ) );
	}
	$o .= $OUTPUT->footer ();
} else {
	print_error ( get_string ( 'invalidaction', 'local_reservasalas' ) );
}

echo $o;
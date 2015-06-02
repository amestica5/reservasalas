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
require_once (dirname ( dirname ( __FILE__ ) ) . '/../../config.php'); // required
require_once ($CFG->dirroot . '/local/reservasalas/admin/forms.php');
require_once ($CFG->dirroot . '/local/reservasalas/admin/tables.php');

// context setup
global $PAGE, $CFG, $OUTPUT, $DB;
require_login ();
$url = new moodle_url ( '/local/reservasalas/admin/campus.php' );
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

// define action var, exist diferents tipes like: ver, editar, eliminar, agregar, crear
// every ACTION shows a diferent version of page, depends of the activities that user realice
// By defect the ACTION is ver, for this reason the page shows a table with all reasourse.

$action = optional_param ( 'action', 'ver', PARAM_TEXT );

// Implementation of create action
// create a new campus

if ($action == 'crear') {
	
	$createcampusform = new create_campus ();
	if ($createcampusform->is_cancelled ()) {
		$action = 'ver';
	} else if ($fromform = $createcampusform->get_data ()) {
		$newcampusdata = new stdClass ();
		$newcampusdata->nombre = $fromform->sede;
		$DB->insert_record ( 'reservasalas_sedes', $newcampusdata );
		$action = "ver";
	}
}

// implemantation of edit action
// edit an existing campus

if ($action == 'editar') {
	
	$idcampus = optional_param ( 'idsede', '0', PARAM_INT );
	$hiddencampusid = optional_param ( 'idplace', '0', PARAM_INT );
	$prevaction = optional_param ( 'prevaction', 'ver', PARAM_TEXT );
	$campusdata = $DB->get_record ( 'reservasalas_sedes', array (
			'id' => $idcampus 
	) );
	$recordeditcampus = $DB->get_record ( 'reservasalas_sedes', array (
			'id' => $idcampus 
	) );
	
	$campuseditform = new edit_campus ( null, array (
			'prevaction' => $prevaction,
			'idplace' => $idcampus,
			'placename' => $campusdata->nombre 
	) );
	if ($campuseditform->is_cancelled ()) {
		$action = $prevaction;
	} else if ($fromform = $campuseditform->get_data ()) {
		
		if ($hiddencampusid != 0) {
			$recordeditcampus = $DB->get_record ( 'reservasalas_sedes', array (
					'id' => $hiddencampusid 
			) );
			$recordeditcampus->nombre = $fromform->place;
			$DB->update_record ( 'reservasalas_sedes', $recordeditcampus );
			$action = $prevaction;
		}
	}
}

// implementation of delete action
// delete an existing campus

if ($action == 'borrar') {
	
	$idcampus = required_param ( 'idsede', PARAM_INT );
	if (confirm_sesskey ()) {
		$buildings = $DB->get_records ( 'reservasalas_edificios', array (
				'sedes_id' => $idcampus 
		) );
		foreach ( $buildings as $building ) {
			$rooms = $DB->get_records ( 'reservasalas_salas', array (
					'edificios_id' => $building->id 
			) );
			foreach ( $rooms as $room ) {
				$DB->delete_records ( 'reservasalas_reservas', array (
						'salas_id' => $room->id 
				) );
			}
			$DB->delete_records ( 'reservasalas_salas', array (
					'edificios_id' => $building->id 
			) );
		}
		$DB->delete_records ( 'reservasalas_edificios', array (
				'sedes_id' => $idcampus 
		) );
		$DB->delete_records ( 'reservasalas_sedes', array (
				'id' => $idcampus 
		) );
		
		$action = "ver";
	} else {
		print_error ( "ERROR" );
	}
}

//implementation of view action
//view a table with campus, default page

if($action == 'ver'){

	$table= tables::get_campus();

}

// shows of actions activities.
// **************************************************************************************************************************************************
if ($action == 'editar') {
	
	$o = '';
	$title = get_string ( 'editcampus', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodplaces', 'local_reservasalas' ), 'campus.php' );
	$PAGE->navbar->add ( $title, '' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( get_string ( 'editcampus', 'local_reservasalas' ) );
	$o .= "<h4>" . get_string ( 'campus', 'local_reservasalas' ) . ": $campusdata->nombre </h4>";
	$o .= "<br>";
	
	ob_start ();
	$campuseditform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	
	$o .= $OUTPUT->footer ();
} else if ($action == 'ver') {
	$o = '';
	
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/campus.php' ), get_string ( 'places', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	$title = get_string ( 'seeandmodplaces', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( $title, 'campus.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'sites', 'local_reservasalas' ) );
	$url = new moodle_url ( "campus.php", array (
			'action' => 'crear' 
	) );
	
	$o .= html_writer::table ( $table );
	$o .= $OUTPUT->single_button ( $url, get_string ( 'createnewplace', 'local_reservasalas' ) );
	$o .= $OUTPUT->footer ();
} else if ($action = "crear") {
	$o = '';
	$title = get_string ( 'campuscreate', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'places', 'local_reservasalas' ), 'campus.php' );
	$PAGE->navbar->add ( $title );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( $title );
	ob_start ();
	$createcampusform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	$o .= $OUTPUT->footer ();
} else {
	print_error ( get_string ( 'invalidaction', 'local_reservasalas' ) );
}
echo $o;

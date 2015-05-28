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
require_once ($CFG->dirroot . '/local/reservasalas/admin/locallib.php');

global $PAGE, $CFG, $OUTPUT, $DB;
require_login ();
$url = new moodle_url ( '/local/reservasalas/admin/resources.php' );
$context = context_system::instance ();
$PAGE->set_context ( $context );
$PAGE->set_url ( $url );
$PAGE->set_pagelayout ( 'standard' );

$action = optional_param ( 'action', 'ver', PARAM_TEXT ); // --> TO DO, change action lang
                                                          
// Capabilities
                                                          // check if user has the capacity to view specific content
                                                          // example: a student doesn´t have the capacity to view the content

if (! has_capability ( 'local/reservasalas:administration', $context )) { // --> TO DO, change name of pluggin
	print_error ( get_string ( 'INVALID_ACCESS', 'Reserva_Sala' ) ); // --> TO DO, change name of lang php
}

// define action var, exist diferents tipes like: ver, editar, crear y borrar
// every ACTION shows a diferent version of page, depends of the activities that user realice
// By defect the ACTION is ver, for this reason the page shows a table with all reasourse.

if (! $DB->get_records ( 'reservasalas_recursos' )) { // --> TO DO, change table lang
	
	if ($action == 'ver') { // --> TO DO, change action lang
		$action = "sinrecursos";
	}
}

// implementation of create action
// allows create resourse

if ($action == 'crear') { // --> TO DO, change action lang
	$resourceform = new create_resources ();
	if ($resourceform->is_cancelled ()) {
		$action = 'ver'; // --> TO DO, change action lang
	} else if ($fromform = $resourceform->get_data ()) {
		// the existing resourse check was realice in the form.
		// create a new resourse
		$record = new stdClass ();
		$record->nombre = $fromform->resource; // --> TO DO, change key lang
		$DB->insert_record ( 'reservasalas_recursos', $record ); // --> TO DO, change table lang
		$action = "ver"; // --> TO DO, change action lang
	}
}

// implementation of edit action
// edit a existing resourse

if ($action == 'editar') { // --> TO DO, change action lang
	
	$prevaction = optional_param ( 'prevaction', 'ver', PARAM_TEXT ); // --> TO DO, change action lang
	$idresource = optional_param ( 'idresource', '0', PARAM_INT );
	
	$resourcename = $DB->get_record ( 'reservasalas_recursos', array (
			'id' => $idresource 
	) );
	$record = $DB->get_record ( 'reservasalas_recursos', array (
			'id' => $idresource 
	) );
	$editform = new edit_resources ( null, array (
			'prevaction' => $prevaction,
			'idresource' => $idresource,
			'resourcename' => $resourcename->nombre 
	) );
	
	if ($editform->is_cancelled ()) {
		$action = $prevaction;
	} 

	else if ($fromform = $editform->get_data ()) {
		if ($idresource != 0) {
			// el recurso se edita, se cambia su nombre por uno no existente
			$record = $DB->get_record ( 'reservasalas_recursos', array (
					'id' => $idresource 
			) );
			$record->nombre = $fromform->resource;
			$DB->update_record ( 'reservasalas_recursos', $record );
			$action = $prevaction;
		}
	}
}

// implementation of delete action
// delete a exist resourse

if ($action == 'borrar') { // --> TO DO, change action lang
	
	$idresource = required_param ( 'idresource', PARAM_INT );
	if (confirm_sesskey ()) {
		$resources = $DB->get_records ( 'reservasalas_recursos', array (
				'id' => $idresource 
		) ); // --> TO Do, change table lang
		     // Equal SQL line 103 and 90
		foreach ( $resources as $resource ) {
			$DB->delete_records ( 'reservasalas_salarecursos', array (
					'recursos_id' => $resource->id 
			) );
		}
		$DB->delete_records ( 'reservasalas_recursos', array (
				'id' => $idresource 
		) );
		$action = "ver";
	} else {
		print_error ( "ERROR" );
	}
}

// implementation of see action
// show a table with all resourses

if ($action == 'ver') { // --> TO DO, change action lang
	$tabla = tables::get_resources ();
}

// shows of actions activities.
// **************************************************************************************************************************************************
if ($action == 'editar') {
	
	$o = '';
	$title = get_string ( 'editresource', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodresources', 'local_reservasalas' ), 'resources.php' );
	$PAGE->navbar->add ( $title, '' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( get_string ( 'editresource', 'local_reservasalas' ) );
	$o .= '<h4>' . get_string ( 'resource', 'local_reservasalas' ) . ': ' . $resourcename->nombre . '</h4>';
	$o .= "<br>";
	
	ob_start ();
	$editform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	
	$o .= $OUTPUT->footer ();
} else if ($action == 'ver') {
	$o = '';
	
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/sedes.php' ), get_string ( 'places', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	$title = get_string ( 'seeandmodresources', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( $title, 'resources.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	// $o.= $OUTPUT->heading($title);
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'resources', 'local_reservasalas' ) );
	$url = new moodle_url ( "resources.php", array (
			'action' => 'crear' 
	) );
	$o .= $OUTPUT->single_button ( $url, get_string ( 'createnewresource', 'local_reservasalas' ) );
	$o .= html_writer::table ( $tabla );
	$o .= $OUTPUT->single_button ( $url, get_string ( 'createnewresource', 'local_reservasalas' ) );
	$o .= $OUTPUT->footer ();
} else if ($action == 'crear') {
	$o = '';
	$title = get_string ( 'createresource', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'resources', 'local_reservasalas' ), 'resources.php' );
	$PAGE->navbar->add ( $title );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( $title );
	ob_start ();
	$resourceform->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	$o .= $OUTPUT->footer ();
} else if ($action == "sinrecursos") {
	$o = '';
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/sedes.php' ), get_string ( 'places', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	$title = get_string ( 'seeandmodrooms', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodrooms', 'local_reservasalas' ), 'resources.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'resources', 'local_reservasalas' ) );
	$o .= $OUTPUT->heading ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	
	$url = new moodle_url ( "resources.php", array (
			'action' => 'crear' 
	) );
	$o .= "<center><strong>" . get_string ( 'nosystemresources', 'local_reservasalas' ) . "<strong><center>";
	$o .= $OUTPUT->single_button ( $url, get_string ( 'createresource', 'local_reservasalas' ) );
	$o .= $OUTPUT->footer ();
} else {
	print_error ( get_string ( 'invalidaction', 'local_reservasalas' ) );
}
echo $o;
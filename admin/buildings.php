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
$url = new moodle_url ( '/local/reservasalas/admin/buildings.php' );
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

// warning when modules were wrong written
$warning = '';

// define action var, exist diferents tipes like: ver, editar, borrar, agregar, crear.
// every ACTION shows a diferent version of page, depends of the activities that user realice
// By defect the ACTION is ver, for this reason the page shows a table with all reasourse.

$action = optional_param ( 'action', 'ver', PARAM_TEXT );

// review if campus exists because buildings are in the campus

if (! $DB->get_records ( 'reservasalas_edificios' )) {
	$campus = 1; // --> TODO, review if this value is important
	if (! $DB->get_records ( 'reservasalas_sedes' )) {
		
		$campus = 0;
	}
	if ($action == 'ver') {
		// if campus doesn't exists the action change for 'sinsedes'
		$action = "sinsedes";
	}
}

// implementation of create action
// create a new building
if ($action == 'crear') {
	// creation form
	$createbuilding = new create_building ();
	if ($createbuilding->is_cancelled ()) {
		$action = 'ver';
		// if form doesn't cancel and the data is correct it will create a new building
	} else if ($fromform = $createbuilding->get_data ()) {
		
		$newbuildingdata = new stdClass ();
		$newbuildingdata->nombre = $fromform->edificio;
		$newbuildingdata->sedes_id = $fromform->sede;
		$explode = $fromform->modules;
		
		// create a building associated to the campus
		if ($DB->insert_record ( 'reservasalas_edificios', $newbuildingdata )) {
			$recordnewbuilding = new stdClass ();
			
			$buildingid = $DB->get_record ( 'reservasalas_edificios', array (
					'nombre' => $fromform->edificio,
					'sedes_id' => $fromform->sede 
			) );
			$modulesArray = array ();
			$modulesArray = explode ( '#', $explode );
			$newmodulesinfo = array ();
			
			// create a array with new insert modules of the new building
			foreach ( $modulesArray as $moduleArray ) {
				$newmodulesinfo [] = $moduleArray;
			}
			// se agregan los modulos al edificio. El nombre, hora inicio y hora fin para cada modulo.
			foreach ( $newmodulesinfo as $newmoduleinfo ) {
				if ($newmoduleinfo) {
					$moduleinfo = explode ( ',', $newmoduleinfo );
					$time = explode ( '-', $moduleinfo [1] );
					$moduleName = $moduleinfo [0];
					$start_module = $time [0];
					$end_module = $time [1];
					if (! empty ( $moduleName ) && ! empty ( $start_module ) && ! empty ( $end_module )) {
						
						$recordnewbuilding->nombre_modulo = $moduleName;
						$recordnewbuilding->hora_inicio = $start_module;
						$recordnewbuilding->hora_fin = $end_module;
						$recordnewbuilding->edificio_id = $buildingid->id;
						$DB->insert_record ( 'reservasalas_modulos', $recordnewbuilding );
					}
				}
			}
		} else {
			print_error ( "ERROR" );
		}
		$action = 'ver';
	}
}

// implementation of edit action
// edit an existing building

if ($action == 'editar') {
	
	$idbuilding = optional_param ( 'edificio', '0', PARAM_INT );
	$prevaction = optional_param ( 'prevaction', 'ver', PARAM_TEXT );
	
	if ($idbuilding != 0) {
		$buildingdata = $DB->get_record ( 'reservasalas_edificios', array (
				'id' => $idbuilding 
		) );
		// $campusdata = $DB->get_record('reservasalas_sedes', array('id'=>$buildingdata->sedes_id));
		// $record = $DB->get_record('reservasalas_edificios', array('id'=>$idbuilding));
	} else {
		$buildingdata = new stdClass ();
		$buildingdata->nombre = "";
	}
	
	// get campus
	$campus = $DB->get_records ( 'reservasalas_sedes' ); // get all campus
	$campusarray = array ();
	foreach ( $campus as $key => $value ) { // create an array with campus list
		$campusarray [$value->id] = $value->nombre;
	}
	
	// get modules of the building to edit
	$modulesdata = $DB->get_records ( 'reservasalas_modulos', array (
			'edificio_id' => $idbuilding 
	) );
	$modulesarray = array ();
	foreach ( $modulesdata as $key => $value ) {
		$modulesarray [] = '#' . $value->nombre_modulo . ',' . $value->hora_inicio . '-' . $value->hora_fin . '';
	}
	
	// create a form to edit building
	$editform = new edit_buildings ( null, array (
			'prevaction' => $prevaction,
			'idbuilding' => $idbuilding,
			'buildingname' => $buildingdata->nombre,
			'place' => $campusarray,
			'modules' => $modulesarray 
	) );
	// if form was cancelled, redirect to view action page.
	if ($editform->is_cancelled ()) {
		$action = 'ver';
		// if the form was accept, get the data of this.
	} else if ($fromform = $editform->get_data ()) {
		
		$hiddenbuildingid = optional_param ( 'buildingid', 0, PARAM_INT );
		
		if ($hiddenbuildingid != 0) {
			
			$explode = $fromform->modules; // --> TODO create a modules function line 96 to 103
			$modulesArray = array ();
			$modulesArray = explode ( '#', $explode );
			$editmodules = array ();
			
			// create an array with modules insert in the edit mode
			foreach ( $modulesArray as $moduleArray ) {
				$editmodules [] = $moduleArray;
			}
			
			// get info about modules of edit building
			$recordeditbuilding = $DB->get_records ( 'reservasalas_modulos', array (
					'edificio_id' => $fromform->buildingid 
			) );
			$listmodules = array ();
			// create array with row many as modules edit building have
			foreach ( $recordeditbuilding as $recordseditbuilding ) {
				$listmodules [] = array (
						"id" => $recordseditbuilding->id 
				);
			}
			
			$nummodules = count ( $listmodules );
			$auxcountmodules = 0;
			// review all edit modules
			foreach ( $editmodules as $editmodule ) {
				
				if ($editmodule != null) {
					$string = explode ( ',', $editmodule );
					
					$time = explode ( '-', $string [1] );
					// get form info about edit building like name module, start and finish hour.
					$moduleName = $string [0];
					$start_module = $time [0];
					$end_module = $time [1];
					
					if (! empty ( $moduleName ) && ! empty ( $start_module ) && ! empty ( $end_module )) {
						
						if ($auxcountmodules < $nummodules) { // if building have modules these will be edit.
							$param = $listmodules [$auxcountmodules] ["id"];
							$info = $DB->get_record ( "reservasalas_modulos", array (
									'id' => $param,
									'edificio_id' => $fromform->buildingid 
							) );
							$info->nombre_modulo = $moduleName;
							$info->hora_inicio = $start_module;
							$info->hora_fin = $end_module;
							$DB->update_record ( 'reservasalas_modulos', $info );
							$auxcountmodules ++;
						} else { // if the building doesný have modules, these will be create.
							
							$recordeditbuilding = new stdClass ();
							$recordeditbuilding->nombre_modulo = $moduleName;
							$recordeditbuilding->hora_inicio = $start_module;
							$recordeditbuilding->hora_fin = $end_module;
							$recordeditbuilding->edificio_id = $fromform->buildingid;
							$DB->insert_record ( 'reservasalas_modulos', $recordeditbuilding );
						}
					} else { // if insert info is not correct the action will be change.
						
						$warning = get_string ( 'warning', 'local_reservasalas' ) . '</br>';
						$action = 'ver';
					}
				}
			}
			
			if ($auxcountmodules < $nummodules) {
				// if previewsly exist more modules than new or edit modules they will be delete
				$total = $nummodules - $auxcountmodules;
				echo $total;
				$select = "edificio_id='$fromform->buildingid' ORDER BY id DESC limit $total";
				$existingmodules = $DB->get_records_select ( 'reservasalas_modulos', $select );
				foreach ( $existingmodules as $existingmodule ) {
					$DB->delete_records ( 'reservasalas_modulos', array (
							'id' => $existingmodule->id 
					) );
				}
			}
			// change old data for new edit data like name of builfing and campus.
			$editbuildingdata = $DB->get_record ( 'reservasalas_edificios', array (
					'id' => $fromform->buildingid 
			) );
			$editbuildingdata->nombre = $fromform->edificio;
			$editbuildingdata->sedes_id = $fromform->sede;
			$DB->update_record ( 'reservasalas_edificios', $editbuildingdata );
			$action = $prevaction;
		}
		$action = 'ver';
	}
}

// implementation of delete action
// delete an existing building

if ($action == 'borrar') {
	$deleteidbuilding = required_param ( 'edificio', PARAM_INT );
	// if the building have rooms these will be delete
	if ($rooms = $DB->get_records ( 'reservasalas_salas', array (
			'edificios_id' => $deleteidbuilding 
	) )) {
		foreach ( $rooms as $room ) {
			$DB->delete_records ( 'reservasalas_reservas', array (
					'salas_id' => $room->id 
			) );
		}
		$DB->delete_records ( 'reservasalas_salas', array (
				'edificios_id' => $deleteidbuilding 
		) );
	}
	$DB->delete_records ( 'reservasalas_edificios', array (
			'id' => $deleteidbuilding 
	) );
	$action = "ver";
}

// implementation of view action
// create a table with all buildings
if ($action == 'ver') {
	$tabla = tables::get_buildings ();
}

// **************************************************************************************************
// Se crean las vistas de cada ACTION previamente implementados.

if ($action == 'editar') {
	
	$o = '';
	$title = get_string ( 'editbuilding', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodbuildings', 'local_reservasalas' ), 'buildings.php' );
	$PAGE->navbar->add ( $title, '' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( get_string ( 'editbuilding', 'local_reservasalas' ) );
	
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
	
	$title = get_string ( 'seeandmodbuildings', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( $title, 'buildings.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	// $o.= $OUTPUT->heading("Edificios");
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'buildings', 'local_reservasalas' ) );
	$urledificio = new moodle_url ( "buildings.php", array (
			'action' => 'crear' 
	) );
	$o .= $OUTPUT->single_button ( $urledificio, get_string ( 'createbuildings', 'local_reservasalas' ) );
	$o .= html_writer::table ( $tabla );
	$o .= $OUTPUT->single_button ( $urledificio, get_string ( 'createbuilding', 'local_reservasalas' ) );
	$o .= $OUTPUT->footer ();
} else if ($action == 'crear') {
	$o = '';
	$title = get_string ( 'createbuildings', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'seeandmodbuildings', 'local_reservasalas' ), 'buildings.php' );
	$PAGE->navbar->add ( $title );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->heading ( $title );
	ob_start ();
	$createbuilding->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
	
	$o .= $OUTPUT->footer ();
} else if ($action == "sinsedes") {
	$o = '';
	$toprow = array ();
	$toprow [] = new tabobject ( get_string ( 'sites', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/sedes.php' ), get_string ( 'sites', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'buildings', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/buildings.php' ), get_string ( 'buildings', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'studyrooms', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/rooms.php' ), get_string ( 'rooms', 'local_reservasalas' ) );
	$toprow [] = new tabobject ( get_string ( 'resources', 'local_reservasalas' ), new moodle_url ( '/local/reservasalas/admin/resources.php' ), get_string ( 'resources', 'local_reservasalas' ) );
	
	$title = get_string ( 'seeandmodbuildings', 'local_reservasalas' );
	$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ) );
	$PAGE->navbar->add ( get_string ( 'adjustments', 'local_reservasalas' ) );
	$PAGE->navbar->add ( $title, 'buildings.php' );
	$PAGE->set_title ( $title );
	$PAGE->set_heading ( $title );
	
	$o .= $OUTPUT->header ();
	$o .= $OUTPUT->tabtree ( $toprow, get_string ( 'buildings', 'local_reservasalas' ) );
	$o .= $OUTPUT->heading ( get_string ( 'buildings', 'local_reservasalas' ) );
	
	if ($campus == 0) {
		$url = new moodle_url ( "sedes.php", array (
				'action' => 'crear' 
		) );
		$o .= "<center><strong>" . get_string ( 'nosites', 'local_reservasalas' ) . "<strong><center>";
		$o .= $OUTPUT->single_button ( $url, get_string ( 'campuscreate', 'local_reservasalas' ) );
	} else {
		
		$url = new moodle_url ( "buildings.php", array (
				'action' => 'crear' 
		) );
		$o .= "<center><strong>" . get_string ( 'nobuildings', 'local_reservasalas' ) . "<strong><center>";
		$o .= $OUTPUT->single_button ( $url, get_string ( 'createbuildings', 'local_reservasalas' ) );
	}
	$o .= $OUTPUT->footer ();
} else {
	print_error ( get_string ( 'invalidaction', 'local_reservasalas' ) );
}

echo $o;
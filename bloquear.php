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
 * @copyright 2013 Marcelo Epuyao
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Page to block students.
require_once (dirname ( __FILE__ ) . '/../../config.php'); // Required.
require_once ($CFG->dirroot . '/local/reservasalas/forms.php');
require_once ($CFG->dirroot . '/local/reservasalas/tablas.php');

global $PAGE, $CFG, $OUTPUT, $DB;
require_login ();
$url = new moodle_url ( '/local/reservasalas/bloquear.php' );
$context = context_system::instance ();
$PAGE->set_context ( $context );
$PAGE->set_url ( $url );
$PAGE->set_pagelayout ( 'standard' );

// Capabilities.
// Validates the capability of the user, so he/she can see the content of the page.
// In these case only the admin can see the Module Admin can see the content.
if (! has_capability ( 'local/reservasalas:blocking', $context )) {
	print_error ( get_string ( 'INVALID_ACCESS', 'Reserva_Sala' ) );
}

// Breadcrumbs.
$PAGE->navbar->add ( get_string ( 'roomsreserve', 'local_reservasalas' ), 'reservar.php' );
$PAGE->navbar->add ( get_string ( 'users', 'local_reservasalas' ) );
$PAGE->navbar->add ( get_string ( 'blockstudent', 'local_reservasalas' ), 'bloquear.php' );

// Form to block a student.
$buscador = new buscadorUsuario ( null );
if ($fromform = $buscador->get_data ()) {
	// Blocks the user from the Database.
	if ($usuario = $DB->get_record ( 'user', array (
			'username' => $fromform->usuario 
	) )) {
		$record = new stdClass ();
		$record->comentarios = $fromform->comentario;
		$record->alumno_id = $usuario->id;
		$record->estado = 1;
		$record->fecha_bloqueo = date ( 'Y-m-d' );
		$record->id_reserva = "";
		
		$DB->insert_record ( 'reservasalas_bloqueados', $record );
		$bloqueado = true;
	} else {
		print_error ( "error" );
	}
}

// It loads the page, header, title and breadcrumbs.

$o = '';
$title = get_string ( 'blockstudent', 'local_reservasalas' );
$PAGE->set_title ( $title );
$PAGE->set_heading ( $title );
$o .= $OUTPUT->header ();
$o .= $OUTPUT->heading ( $title );

/*
 * Depending if the institutional mail is correct,
 * and in the same time the user hasn't been blocked,
 * or the user was already blocked,
 * it will show the success of the operation or the failure.
 */

if (isset ( $bloqueado )) {
	$o .= get_string ( 'thestudent', 'local_reservasalas' ) . $usuario->firstname . " " . $usuario->lastname . get_string ( 'suspendeduntilthe', 'local_reservasalas' ) . date ( 'd-m-Y', strtotime ( "+ 3 days" ) );
	$o .= $OUTPUT->single_button ( 'bloquear.php', get_string ( 'blockagain', 'local_reservasalas' ) );
} else {
	ob_start ();
	$buscador->display ();
	$o .= ob_get_contents ();
	ob_end_clean ();
}

$o .= $OUTPUT->footer ();

echo $o;

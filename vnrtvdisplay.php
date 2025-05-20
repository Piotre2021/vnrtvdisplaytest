<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://hintergrundbewegung.de
 * @since             1.0.0
 * @package           Vnrtvdisplay
 *
 * @wordpress-plugin
 * Plugin Name:       VNR-TV-DISPLAY
 * Plugin URI:        https://hintergrundbewegung.de
 * Description:       Import, synch and display data from CASTR.COM and Excel(CSV) by API uploads
 * Version:           1.0.0
 * Author:            Peter Mertzlin
 * Author URI:        https://hintergrundbewegung.de/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vnrtvdisplay
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VNRTVDISPLAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vnrtvdisplay-activator.php
 */
function activate_vnrtvdisplay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vnrtvdisplay-activator.php';
	Vnrtvdisplay_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vnrtvdisplay-deactivator.php
 */
function deactivate_vnrtvdisplay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vnrtvdisplay-deactivator.php';
	Vnrtvdisplay_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vnrtvdisplay' );
register_deactivation_hook( __FILE__, 'deactivate_vnrtvdisplay' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-vnrtvdisplay.php';




add_action('rest_api_init', 'register_custom_upload_endpoint');  

function register_custom_upload_endpoint() {  

    header("Access-Control-Allow-Origin: *");  
    header("Access-Control-Allow-Methods: POST");  
    register_rest_route('custom-uploader/v1', '/upload', array(  
        'methods'  => 'POST',  
        'callback' => 'handle_file_upload',  
        'permission_callback' => function () {  
            return true;  
        },  
    ));  
}


add_action('rest_api_init', 'register_custom_excelupload_endpoint');  

function register_custom_excelupload_endpoint() {  

    header("Access-Control-Allow-Origin: *");  
    header("Access-Control-Allow-Methods: POST");  
    register_rest_route('custom-uploader/v1', '/excelupload', array(  
        'methods'  => 'POST',  
        'callback' => 'handle_excelfile_upload',  
        'permission_callback' => function () {  
            return true;  
        },  
    ));  
}



function your_custom_callback($dir, $name, $ext){
    return $name;
}

function readCSV($filename, $delimeter=',')
{
    $handle = fopen($filename, "r"); 
    if ($handle === false) {
        return false;
    }

    while (($data = fgetcsv($handle, 0, $delimeter)) !== false) {
       yield $data;
    }

    fclose($handle);
	return $data;
}

function handle_excelfile_upload($request) {  

	if ( function_exists( 'cbxphpspreadsheet_loadable' ) && cbxphpspreadsheet_loadable() ) {
		//Include PHPExcel
		require_once( CBXPHPSPREADSHEET_ROOT_PATH . 'lib/vendor/autoload.php' ); //or use 'cbxphpspreadsheet_load();'
	}
$zahl = "7777777777777";
// Check if a file was uploaded  
if (empty($_FILES['file'])) {  
	return new WP_Error('no_file', 'No file uploaded.', array('status' => 400));  
}  

$file = $_FILES['file'];  

//$inputFileName = './sampleData/example1.xls';
$target_dir = wp_upload_dir();
	$target_dir = $target_dir['path'];
	$target_dir = substr($target_dir, 0, -5);
	global $wpdb;
	$target_file = $target_dir.basename($_FILES['file']['name']);
	

	if(move_uploaded_file($_FILES['file']['tmp_name'], $target_file)){
		$message = "EXCEL IMPORT OK.";	

		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($target_file);
		$worksheet = $spreadsheet->getSheet('0');

		for ($x = 2; $x <= 1000; $x++) {
			$formatvalue = $worksheet->getCell('A'.$x)->getValue()."-".$worksheet->getCell('B'.$x)->getValue();
			$formatpart1 = $worksheet->getCell('A'.$x)->getValue();
			$formatpart2 = $worksheet->getCell('B'.$x)->getValue();
			if($formatvalue!="-"){
				//echo $formatvalue."<br>";
				// check if dataset exists
				$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vnrtvdetails WHERE content_name = '$formatvalue'", OBJECT );

				if($results[0]->id!=""){
					$resultsid = $results[0]->id;
					//this entry does exists and will be updated
					//echo "update ID:" .$resultsid."<br>";
					$wpdb->update(
						'moxe_vnrtvdetails',
						array(
						'format' => $formatpart1,	
						'formatnr' => $formatpart2,	
						'titel' => $worksheet->getCell('D'.$x)->getValue(),	
						'subhead' => $worksheet->getCell('E'.$x)->getValue(),	
						'text' => $worksheet->getCell('F'.$x)->getValue(),	
						'text2' => $worksheet->getCell('G'.$x)->getValue(),	
						'cta1text' => $worksheet->getCell('I'.$x)->getValue(),	
						'cta1link' => $worksheet->getCell('J'.$x)->getValue(),	
						'grafiklink' => $worksheet->getCell('K'.$x)->getValue(),	
						'cta2text' => $worksheet->getCell('L'.$x)->getValue(),	
						'cta2link' => $worksheet->getCell('M'.$x)->getValue(),	
						),
						array( 'id' => $resultsid ),
						array( '%s','%s',
						'%s',	
						'%s','%s','%s','%s','%s','%s','%s'	
						),
						array( '%d' )
						);
					
					}
				
			}
		}

		


			
	}
	else {
		$message = "EXCEL IMPORT NOT OK.";		
	}
		
return new WP_REST_Response(array('success'=> true,'message'=> $message), 200);

}


function handle_file_upload($request) {  
$zahl = "999999999";
// Check if a file was uploaded  
if (empty($_FILES['file'])) {  
	return new WP_Error('no_file', 'No file uploaded.', array('status' => 400));  
}  

$file = $_FILES['file'];  

//$json = json_encode($request);

// Validate file type  
$allowed_mimes = array(  
	'csv'  => 'text/csv',  
);  
$file_info = wp_check_filetype($file['name'], $allowed_mimes);  

if (!$file_info['ext']) {  
	return new WP_Error('invalid_type', 'File type not allowed.', array('status' => 400));  
}  

// Validate file size (e.g., 5MB limit)  
$max_size = 5 * 1024 * 1024;  
if ($file['size'] > $max_size) {  
	return new WP_Error('file_too_large', 'File exceeds 5MB limit.', array('status' => 400));  
}  

// read

$target_dir = wp_upload_dir();
$target_dir = $target_dir['path'];
	global $wpdb;
	$target_file = $target_dir.basename($_FILES['file']['name']);

	//if(move_uploaded_file($_FILES['file']['tmp_name'], $target_file)){
	//	$message = "UPLOAD OK.";
	//} else { $message = "UPLOAD NOT OK.";}

	$target_filex = $_FILES['file']['tmp_name'];

	$thepath = $target_dir.$file['name'];
	$csv = readCSV($target_filex); 
	foreach ( $csv as $c ) {
		$firstColumn = $c[0];
	
		$firstColumn = str_replace("Playing", "", $firstColumn);
		$stringid = substr($firstColumn,0,8);
		$secondColumn = $c[1];

		$pieces = explode(",",$secondColumn);
		$datumkomplett = $pieces[0];
		$zeitkomplett = $pieces[1];

		$datepieces = explode("/",$datumkomplett);
		$timepieces = explode(":",$zeitkomplett);

		$dasdatum = $datepieces[2]."-".$datepieces[0]."-".$datepieces[1]." ".$timepieces[0].":".$timepieces[1].":".$timepieces[2];
		
		$stringid = trim($stringid); 
		if(trim($stringid) != "Content") {
			if(trim($stringid) != "Break") {
				if(trim($stringid) != "Pull") {
				$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vnrtvdetails WHERE content_name = '$stringid'", OBJECT );
				if($results[0]->content_name != $stringid){
					$wpdb->insert('moxe_vnrtvdetails',array('content_name' => trim($stringid),'startdatetime' => $dasdatum),array('%s','%s'));
				} else {
					$resultsid = $results[0]->id;
					//this entry does exists and will be updated
					//echo "update ID:" .$resultsid."<br>";
					$checher = $wpdb->update('moxe_vnrtvdetails',array('startdatetime' => $dasdatum),
						array( 'id' => $resultsid ),
						array( '%s'	),
						array( '%d' )
						);
				}
		}}}
	}

	$message = "CSV IMPORT OK.";


	
// PHP Code

//$target_dir = $target_dir['path'];
return new WP_REST_Response(array('success'=> true,'message'=> $message), 200);

}  

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vnrtvdisplay() {

	$plugin = new Vnrtvdisplay();
	$plugin->run();

}


// function that runs when shortcode is called
function wpb_demo_shortcode() { 
  
	// Things that you want to do.
	$content = '<form name="csvupload" method="post" action="'.plugin_dir_url(__FILE__).'process/" enctype="multipart/form-data">';
	$content .= '<input type="file" name="vnrtvcastrcsvdata" /><br>'; 
	$content .= '<input type="submit" name="vnr_submit_csv_upload_button" value="UPLOAD FILE">'; 
	$content .= '</form><br>'; 

	$content .= '<form name="excelupload" method="post" action="'.plugin_dir_url(__FILE__).'process/" enctype="multipart/form-data">';
	$content .= '<input type="file" name="vnrtvcastrexceldata" /><br>'; 
	$content .= '<input type="submit" name="vnr_submit_excel_upload_button" value="UPLOAD FILE">'; 
	$content .= '</form>'; 
	  
	// Output needs to be return
	return $content;
	}
	// register shortcode
	add_shortcode('greeting', 'wpb_demo_shortcode');

function vnr_display_streaminfo(){
	$content = "";
	global $wpdb;
	//make sql query to get the ID

	$resultstime = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vnrtvdetails WHERE startdatetime > now() order by startdatetime", OBJECT );

	
	$contentid = $resultstime[0]->id;
	$contentid = $contentid - 1;

	setlocale(LC_TIME, "de_DE.utf8");
	$datumtext = strftime("%A"); 
	$monate = array(1=>"Januar",
                2=>"Februar",
                3=>"M&auml;rz",
                4=>"April",
                5=>"Mai",
                6=>"Juni",
                7=>"Juli",
                8=>"August",
                9=>"September",
                10=>"Oktober",
                11=>"November",
                12=>"Dezember");

	$monat = date("n");
	$tag = date("j");
	$monatdeutsch = $monate[$monat];

	$listitemcounter = 0;

	

	$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vnrtvdetails WHERE id = '$contentid'", OBJECT );

	$resultsall = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vnrtvdetails WHERE startdatetime > CURDATE() order by startdatetime", OBJECT );

	if(trim($results[0]->cta2link)!=""){

	$link2 = "<br><div class='cta2' id='cta2id' style='display:none'><a class='cta1style' href='".$results[0]->cta2link."'>".$results[0]->cta2text."</a></div>"; } else 
	{$link2 = ""; }

	$vnrlistdesc = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.<ul><li>Punkt 1</li><li>Punkt 2</li><li>Punkt 3</li></ul>";

	$heutecontent = "<h3><b><u class='vnrthinunderline'>".$datumtext.", ".$tag.". ".$monatdeutsch."</u></b></h3><div class='vnrheuteliste'>";

		foreach($resultsall as $resultitem) {

			$uhrzeit = substr($resultitem->startdatetime,10,6);
			$listitemcounter++;
			$vnrlistdesc = "<p>".$resultitem->text."</p><p>".$resultitem->text2."</p>";
	// listitems loop
	$heutecontent .= "<div class='vnrlistitem'><div id='vnrlistitemclosed".$listitemcounter."' onclick='document.getElementById(\"vnrlistitemopen".$listitemcounter."\").style.display = \"block\"; document.getElementById(\"vnrlistitemclosed".$listitemcounter."\").style.display = \"none\";'><div class='vnrlistbuttoncol'>►</div><div class='vnrlisttimecol'>".$uhrzeit."</div><div class='vnrlisttitelcol'>".$resultitem->titel."</div><div style='clear:both'></div></div><div id='vnrlistitemopen".$listitemcounter."' class='vnrtvlistclosed'  onclick='document.getElementById(\"vnrlistitemclosed".$listitemcounter."\").style.display = \"block\"; document.getElementById(\"vnrlistitemopen".$listitemcounter."\").style.display = \"none\";'><div class='vnrlistbuttoncol'>▼</div><div class='vnrlisttimecol'>".$uhrzeit."</div><div class='vnrlisttitelcol'>".$resultitem->titel."<div class='vnrlistdesc'>".$vnrlistdesc."</div></div><div style='clear:both'></div></div></div>";
		}
	$heutecontent .= "</div>";

	$jetztcontent = "<div class='vnrtvinfojetzt'><h3>".$results[0]->titel."</h3><h4>".$results[0]->subhead."</h4><p class='vnrjetzttext'>".$results[0]->text."</p><br><div class='vnrtvlogo'><img src='".$results[0]->grafiklink."' /></div><div class='cta1'><a class='cta1style' href='".$results[0]->cta1link."'>".$results[0]->cta1text."</a></div>".$link2."</div>";

	$sekunden = $results[0]->timerseconds;
	

	$content .= "<div class='tab'><button class='tablinks' id='defaultOpen' onclick='openInfotab(event, \"jetzt\")'>Gerade läuft</button><button class='tablinks' onclick='openInfotab(event, \"heute\")'>Unser heutiges Programm</button></div><div id='jetzt' class='tabcontent'>".$jetztcontent."</div><div id='heute' class='tabcontent'>".$heutecontent."</div><script>
	function displayLink2() {
		document.getElementById('cta2id').style.display = 'block';

	}
	const myTimeout = setTimeout(displayLink2, ".$sekunden."000);
	function openInfotab(evt, cityName) {var i, tabcontent, tablinks;
		tabcontent = document.getElementsByClassName('tabcontent');
		for (i = 0; i < tabcontent.length; i++) {
		  tabcontent[i].style.display = 'none';
		} 
		tablinks = document.getElementsByClassName('tablinks');
		for (i = 0; i < tablinks.length; i++) {
		  tablinks[i].className = tablinks[i].className.replace(' active', '');
		} 
		document.getElementById(cityName).style.display = 'block';
		evt.currentTarget.className += ' active';
	  } document.getElementById('defaultOpen').click();</script>";
	return $content;
}

add_shortcode('vnrtvstreaminfo', 'vnr_display_streaminfo');




function example_form_capture(){
	if ( function_exists( 'cbxphpspreadsheet_loadable' ) && cbxphpspreadsheet_loadable() ) {
		//Include PHPExcel
		require_once( CBXPHPSPREADSHEET_ROOT_PATH . 'lib/vendor/autoload.php' ); //or use 'cbxphpspreadsheet_load();'
	}

	if(isset($_POST['vnr_submit_csv_upload_button'])) {

		echo "<pre>"; print_r($_POST); echo "</pre>";
		//$target_dir = wp_upload_dir();
		//$target_dir = $target_dir['path'];
		global $wpdb;
		$target_file = $_FILES['vnrtvcastrcsvdata']['tmp_name'];
		if($_FILES['vnrtvcastrcsvdata']['name'] != "") {
			
			$csv = readCSV($target_file); 
			foreach ( $csv as $c ) {
				$firstColumn = $c[0];
				// filter "playing"
				$firstColumn = str_replace("Playing", "", $firstColumn);
				$stringid = substr($firstColumn,0,8);
				$secondColumn = $c[1];

				$pieces = explode(",",$secondColumn);
				$datumkomplett = $pieces[0];
				$zeitkomplett = $pieces[1];

				$datepieces = explode("/",$datumkomplett);
				$timepieces = explode(":",$zeitkomplett);

				$dasdatum = $datepieces[2]."-".$datepieces[0]."-".$datepieces[1]." ".$timepieces[0].":".$timepieces[1].":".$timepieces[2];
				
				echo "1: ".$firstColumn." - "."datum: ".$datumkomplett." - "."zeit: ".$zeitkomplett." DATUM: ".$dasdatum."<br>";
				$stringid = trim($stringid); 
				if(trim($stringid) != "Content") {
					if(trim($stringid) != "Break") {
						if(trim($stringid) != "Pull") {
						echo $stringid."<br>";
							// check if data entry already exists with this content_name 
						$results = $wpdb->get_results( "SELECT content_name FROM {$wpdb->prefix}vnrtvdetails WHERE content_name = '$stringid'", OBJECT );
						//echo $results."<br>";
						//echo "<pre>"; print_r($results[0]); echo "</pre>";
						if($results[0]->content_name != $stringid){
							//this entry dont exists
							$wpdb->insert('moxe_vnrtvdetails',array('content_name' => trim($stringid),'startdatetime' => $dasdatum),array('%s','%s'));
						} 
						//$wpdb->query($wpdb->prepare("INSERT INTO $wpdb->vnrtvdetails(content_name)VALUES (%s)",array($stringid)));
				}}}
			}
			
				//echo "<pre>"; print_r($line_of_text); echo "</pre>";
				
		} 
		
		
		else {
			$message = "NO FILE INPUT!";
		}

		echo $message;
	}

	if(isset($_POST['vnr_submit_excel_upload_button'])) {

	$target_dir = wp_upload_dir();
	$target_dir = $target_dir['path'];
	global $wpdb;
	$target_file = $target_dir.basename($_FILES['vnrtvcastrexceldata']['name']);

	if(move_uploaded_file($_FILES['vnrtvcastrexceldata']['tmp_name'], $target_file)){
		$message = "UPLOAD OK.";

		//$inputFileName = './sampleData/example1.xls';

		/** Load $inputFileName to a Spreadsheet Object  **/
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($target_file);
		$worksheet = $spreadsheet->getSheet('0');
		//var_dump($spreadsheet);
		
		//echo $worksheet->getCell('A1')->getValue();

		for ($x = 2; $x <= 1000; $x++) {
			$formatvalue = $worksheet->getCell('A'.$x)->getValue()."-".$worksheet->getCell('B'.$x)->getValue();
			$formatpart1 = $worksheet->getCell('A'.$x)->getValue();
			$formatpart2 = $worksheet->getCell('B'.$x)->getValue();
			if($formatvalue!="-"){
				//echo $formatvalue."<br>";
				// check if dataset exists
				$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vnrtvdetails WHERE content_name = '$formatvalue'", OBJECT );

				if($results[0]->id!=""){
					$resultsid = $results[0]->id;
					//this entry does exists and will be updated
					//echo "update ID:" .$resultsid."<br>";
					$wpdb->update(
						'moxe_vnrtvdetails',
						array(
						'format' => $formatpart1,	
						'formatnr' => $formatpart2,	
						'titel' => $worksheet->getCell('D'.$x)->getValue(),	
						'subhead' => $worksheet->getCell('E'.$x)->getValue(),	
						'text' => $worksheet->getCell('F'.$x)->getValue(),	
						'cta1text' => $worksheet->getCell('H'.$x)->getValue(),	
						'cta1link' => $worksheet->getCell('I'.$x)->getValue(),	
						'grafiklink' => $worksheet->getCell('J'.$x)->getValue(),	
						'cta2text' => $worksheet->getCell('K'.$x)->getValue(),	
						'cta2link' => $worksheet->getCell('L'.$x)->getValue(),	
						),
						array( 'id' => $resultsid ),
						array( '%s','%s',
						'%s',	
						'%s','%s','%s','%s','%s','%s','%s'	
						),
						array( '%d' )
						);
					
					}
				
			}
		}

		//echo "ready.";
		  
		//echo $spreadsheet;
		
	}
 	//echo $target_file;

	//echo "<pre>"; print_r($csv); echo "</pre>";
	}

}

add_action('wp_head','example_form_capture');


run_vnrtvdisplay();

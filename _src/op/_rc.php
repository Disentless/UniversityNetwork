<?php 

########################################################################
# Functions.
#

// Die with 403 and msg.
function throw403($msg){
    http_response_code(403);
    if (isset($msg)) die($msg);
    
    global $mysql;
    global $query;
    if (isset($mysql) && isset($query)){
        die($mysql->error." query:".$query);
    }
    die('Unknown error');
}

// Placeholder
function checkInt($myVar){
    if (isset($myVar)) return $myVar;
    throw403('missing parameter');
}

function checkString($myVar, $min, $max){
    if (!isset($myVar)) throw403('Данные отсутствуют');
    if (isset($min) && strlen($myVar) < $min) throw403('Неверный размер строки');
    if (isset($max) && strlen($myVar) > $max) throw403('Неверный размер строки');
    
    return $myVar;
}

function _checkDate($myVar){
    if (isset($myVar)) return $myVar;
    throw403('missing parameter');
}

// Codes
function generateHash(){
    $length = 7;
    $result = '';
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';
    $maxRand = strlen($alphabet) - 1;
    for($i = 0; $i < $length; ++$i){
        $result .= $alphabet[rand(0, $maxRand)];
    }
    return $result;
}

function runMultiQuery($query, $autocommit = true){
    global $mysql;
    if (!$mysql->query("START TRANSACTION")) throw403('Cant start transaction.');
    if ($mysql->multi_query($query)){
        do {
            $mysql->store_result();
        } while($mysql->next_result());
        if ($mysql->errno != 0) {
            $error = $mysql->error;
            $mysql->query("ROLLBACK");
            throw403($error." for query: ".$query);
        }
        if ($autocommit) $mysql->query("COMMIT");
    } else throw403($mysql->error);
}

// Creates array with queries for getting new/updated/deleted/etc. rows of specific table.
function composeNote($table, $type){
    global $time;
    return array(
        'updated' => "SELECT * FROM `$table` WHERE UNIX_TIMESTAMP(`Modified`) > $time",
        'deleted' => "SELECT `ID` FROM `DelLog` WHERE `Text` LIKE '$type' AND UNIX_TIMESTAMP(`Time`) > $time"
    );
}

#########################################################################
# Execution start.
$start_exec_time = microtime(true);
$start_memory_usage = memory_get_usage();
session_start();

#########################################################################
# Setup.
#
date_default_timezone_set('UTC');
// Executed database queries log.
$queryLog = 'query_log.txt';

#########################################################################
# Taking data from $_GET, $_POST, $_SESSION.
#
if (!isset($_GET['rtype'])){
    throw403('Invalid method.');
}

$update = $_GET['update'] ?? false;

if (isset($_POST['json'])){
    $_POST = json_decode($_POST['json'], true);
}

# Masks.
$requestMasks = array(
    'faculty_mod_list' => 30,
    'faculty_mod_add' => 8,
    'faculty_mod_modify' => 8,
    'faculty_mod_delete' => 24,
    'profile_mod_list' => 30,
    'profile_mod_add' => 8,
    'profile_mod_modify' => 8,
    'profile_mod_delete' => 8,
    'group_mod_list' => 24,
    'group_mod_add' => 8,
    'group_mod_modify' => 8,
    'group_mod_delete' => 24,
    'group_mod_program_list' => 8,
    'grouplist_mod_add' => 8,
    'grouplist_mod_list' => 30,
    'grouplist_mod_modify' => 8,
    'grouplist_mod_pr_change' => 8,
    'grouplist_mod_delete' => 24,
    'program_mod_list' => 24,
    'program_mod_add' => 8,
    'program_mod_modify' => 8,
    'program_mod_delete' => 24,
    'group_req_subject_list' => 14,
    'group_req_list' => 30,
    'manager_mod_register' => 31,
    'manager_mod_invite' => 16,
    'manager_mod_list' => 16,
    'manager_mod_delete' => 16,
    'student_mod_add' => 8,
    'student_mod_register' => 31,
    'student_mod_modify' => 8,
    'student_mod_delete' => 24,
    'auth_req_login' => 31,
    'dep_mod_list' => 30,
    'dep_mod_add' => 8,
    'dep_mod_modify' => 8,
    'dep_mod_delete' => 24,
    'prof_mod_list' => 24,
    'prof_mod_add' => 8,
    'prof_mod_modify' => 8,
    'prof_mod_delete' => 24,
    'schedule_mod_list' => 30,
    'schedule_mod_add' => 8,
    'schedule_mod_modify' => 8,
    'schedule_mod_delete' => 24,
    'subject_mod_list' => 30,
    'subject_mod_add' => 8,
    'subject_mod_modify' => 8,
    'subject_mod_delete' => 24,
    'subgroup_mod_select' => 6,
    'assignment_mod_list' => 6,
    'assignment_mod_add' => 4,
    'auth_req_invite_check' => 31,
    'auth_req_signout' => 31,
    'assignment_mod_toggle' => 6,
    'assignment_mod_modify' => 4,
    'assignment_mod_delete' => 4,
    'room_mod_list' => 14,
    'room_mod_add' => 8,
    'room_mod_modify' => 8,
    'room_mod_delete' => 8,
    'semester_mod_list' => 31,
    'semester_mod_add' => 8,
    'semester_mod_delete' => 24,
    # Labs
    'lab_mod_class_list' => 6,
    'lab_mod_list' => 6,
    'lab_mod_add' => 6,
    'lab_mod_set' => 6,
    'lab_mod_unset' => 6,
    'lab_mod_delete' => 4,
    'lab_mod_modify' => 6,
    # CG_paper
    'cg_mod_class_list' => 6,
    'cg_mod_list' => 6,
    'cg_mod_add' => 6,
    'cg_mod_set' => 6,
    'cg_mod_unset' => 6,
    'cg_mod_delete' => 4,
    'cg_mod_modify' => 6,
    # Tests
    'tests_mod_class_list' => 6,
    'tests_mod_list' => 6,
    'tests_mod_add' => 6,
    'tests_mod_set' => 6,
    'tests_mod_unset' => 6,
    'tests_mod_delete' => 4,
    'tests_mod_modify' => 6,
    # KR
    'kr_mod_class_list' => 6,
    'kr_mod_list' => 6,
    'kr_mod_add' => 6,
    'kr_mod_set' => 6,
    'kr_mod_unset' => 6,
    'kr_mod_delete' => 4,
    'kr_mod_modify' => 6,
    #
    'upload_mod_add' => 6,
    'upload_req_class_files' => 6,
    'upload_mod_delete' => 6,
    
    # Debug/Info
    'request_time_client' => 31
);

# Composing type.
$type = $_GET['rtype'].'_'.$_GET['type'];
$data = array_merge($_GET, $_POST);

$accountMask = $_SESSION['accountMask'] ?? 1;
$groupID = $_SESSION['groupID'];
$accountType = $_SESSION['accountType'];
$userID = $_SESSION['userID'];

if (($requestMasks[$type] & $accountMask) == 0) throw403();

# At this point request is authorized.
# Configuraion of MySQL. Connects to database and creates $mysql variable.
require_once "db_connect.php.dsf";
# Type check.
require_once "check.php";
# Executing script.
require $_GET['rtype'].".php";

# Requests populate variable $output as assossiative/index array of data.

# Request is completed.

$mysql->close();

# Logging performance info.
$end_exec_time = microtime(true);
$end_memory_usage = memory_get_usage();
$peak_memory = memory_get_peak_usage();

file_put_contents('S:/php7/usage_info.txt', "{$_GET['rtype']} {$_GET['type']} $start_exec_time $end_exec_time $start_memory_usage $end_memory_usage $peak_memory
", FILE_APPEND | LOCK_EX);

# Feedback.
die(json_encode($output));
?>
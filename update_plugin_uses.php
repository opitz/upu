<?php
/**
 * Created by PhpStorm.
 * User: opitz
 * Date: 19/11/21
 * Time: 15:58
 */
echo "\n\nUPU - Update Plugin Uses v.0.4.2\n";
echo "____________________________________________\n\n";

$server = '127.0.0.1';
$db_name = 'qmulmoodleprod';
$db2_name = 'moosis';
$db_user = 'moodle_user';
$db_pass = 'moodle';

echo "server = $server\n";
echo "db_name = $db_name\n";
echo "db2_name = $db2_name\n";
echo "db_user = $db_user\n\n";
echo "____________________________________________\n\n";

// Create connections
$conn = new mysqli($server, $db_user, $db_pass, $db_name);
$conn2 = new mysqli($server, $db_user, $db_pass, $db2_name);

// Check connection
if ($conn->connect_error) {
    die("Connection to '$db_name' failed: " . $conn->connect_error);
}
echo "Connection to '$db_name' successfull\n";

// Check connection
if ($conn2->connect_error) {
    die("Connection to '$db2_name' failed: " . $conn2->connect_error);
}
echo "Connection to '$db2_name' successfull\n";

echo "\n";
echo "____________________________________________\n\n";

function count_uses($sql, $path) {
    $conn = $GLOBALS['conn'];
    $conn2 = $GLOBALS['conn2'];
    $o='';
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Output data of each row
        while($row = $result->fetch_assoc()) {
            $o .= write_uses("$path" . $row['name'], $row['uses']);
        }
    } else {
        $o = "0 results\n";
    }

    return $o;
}

function write_uses($path, $uses) {
    $conn2 = $GLOBALS['conn2'];
    $o = '';

    $sql2 = "select * from plugins where install_path = '$path'";
    $result2 = $conn2->query($sql2);
    if ($result2->num_rows > 0) {
        $plugin = $result2->fetch_assoc(); // Get the plugin data as array

        // Write the changes into the database
        $sql = "UPDATE plugins SET uses_number='" . $uses . "' WHERE id=" . $plugin['id'];

        if ($conn2->query($sql) === TRUE) {
            $o .= "Updated $path (" . $plugin['title'] . ") plugin with $uses uses\n";
        } else {
            $o .= "Error updating record: " . $conn2->error;
        }
    }
    return $o;
}


echo "1. Updating Block Plugins\n______________________________________\n\n";

$sql = "
select
bi.blockname as name, count(cx.instanceid) as uses
from mdl_block_instances bi
join mdl_context cx on cx.id = bi.parentcontextid
where 1
and cx.contextlevel = 50
group by bi.blockname
order by bi.blockname
;";

echo count_uses($sql, 'blocks/');
echo "\n";

echo "2. Updating Module Plugins\n______________________________________\n\n";

$sql = "
select
m.name, count(distinct cm.course) as uses
from mdl_modules m
join mdl_course_modules cm on cm.module = m.id
where 1
group by m.name
order by m.name
;";

echo count_uses($sql, 'mod/');

echo "\n";

echo "3. Updating Course Format Plugins\n______________________________________\n\n";

$sql = "
select 
format as name, count(id) as uses
from mdl_course
group by format
order by format
;";

echo count_uses($sql, 'course/format/');

echo "\n";

echo "4. Updating Local Plugins\n______________________________________\n\n";

$sql = "
SELECT 'activitytodo' as name, count(distinct courseid) as uses 
FROM mdl_local_activitytodo
;";
echo count_uses($sql, 'local/');

$sql = "
SELECT 
'xp' as name, count(distinct courseid) as uses
FROM qmulmoodleprod.mdl_local_xp_config
;";
echo count_uses($sql, 'local/');

echo "\n";

echo "5. Updating Plagiarism Plugins\n______________________________________\n\n";

$sql = "
SELECT
'turnitin' as name, count(distinct courseid) as uses
FROM qmulmoodleprod.mdl_plagiarism_turnitin_courses
;";
echo count_uses($sql, 'plagiarism/');



echo "\n";

echo "6. Updating Question Types Plugins\n______________________________________\n\n";

// Get all tables for qtypes
$sql = "
show tables
where Tables_in_$db_name like '%qtype%'
";

$result = $conn->query($sql);
$tables = [];
$qtypes = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tables[] = $row[array_key_first($row)];
    }

    // Get the number of courses for each question type plugin separetely
    foreach($tables as $table) {
        $sql2 = " 
        select q.qtype as name, count(distinct cx.instanceid) as uses 
        from $table qt
        join mdl_question q on q.id = qt.questionid
        join mdl_question_categories qc on qc.id = q.category
        join mdl_context cx on cx.id = qc.contextid
        where 1
        and cx.contextlevel = 50
        group by q.qtype
        ;
        ";

        $result2 = $conn->query($sql2);
        if (isset($result2->num_rows) && $result2->num_rows > 0) {
            $record = $result2->fetch_assoc();
            if(!isset($qtypes[$record['name']])) {
                $qtypes[$record['name']] = $record['uses'];
            } else {
                if ($qtypes[$record['name']] < $record['uses']) {
                    $qtypes[$record['name']] = $record['uses'];
                }
            }
        }
    }

    // Write the results to the MooSIS database
    $path = "question/type/";
    foreach ($qtypes as $name => $uses) {
        echo write_uses($path . $name, $uses);
    }

} else {
    echo "Does not compute!\n";
}




$conn2->close();
$conn->close();
echo "\n\n------------\n\n";
?>

<?php
/**
 * Created by PhpStorm.
 * User: opitz
 * Date: 19/11/21
 * Time: 15:58
 */
echo "\n\nUPU - Update Plugin Uses v.0.2\n------------------------------\n\n";

$server = '127.0.0.1';
$db_name = 'qmulmoodleprod';
$db2_name = 'moosis';
$db_user = 'moodle_user';
$db_pass = 'moodle';

echo "server = $server";
echo "db_name = $db_name";
echo "db2_name = $db2_name";
echo "db_user = $db_user";

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

echo "1. Updating Block Plugins\n______________________________________\n\n";

$sql = "
select
bi.blockname as block, count(cx.instanceid) as uses
from mdl_block_instances bi
join mdl_context cx on cx.id = bi.parentcontextid
where 1
and cx.contextlevel = 50
group by bi.blockname
order by bi.blockname
;";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $sql2 = "
        select * from plugins where install_path = 'blocks/" . $row['block'] . "'
        ";

        $result2 = $conn2->query($sql2);
        if ($result2->num_rows > 0) {
            $plugin = $result2->fetch_assoc(); // Get the plugin data as array

            // Write the changes into the database
            $sql = "UPDATE plugins SET uses_number='" . $row['uses'] . "' WHERE id=" . $plugin['id'];

            if ($conn2->query($sql) === TRUE) {
                echo "Updated blocks/" . $row['block'] . " (" . $plugin['title'] . ") plugin with " . $row['uses'] . " uses\n";
            } else {
                echo "Error updating record: " . $conn->error;
            }

        }
    }
} else {
    echo "0 results\n";
}

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

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $sql2 = "
        select * from plugins where install_path = 'mod/" . $row['name'] . "'
        ";
        $result2 = $conn2->query($sql2);
        if ($result2->num_rows > 0) {
            $plugin = $result2->fetch_assoc(); // Get the plugin data as array

            // Write the changes into the database
            $sql = "UPDATE plugins SET uses_number='" . $row['uses'] . "' WHERE id=" . $plugin['id'];

            if ($conn2->query($sql) === TRUE) {
                echo "Updated mod/" . $row['name'] . " (" . $plugin['title'] . ") plugin with " . $row['uses'] . " uses\n";
            } else {
                echo "Error updating record: " . $conn->error;
            }

        }
    }
} else {
    echo "0 results\n";
}

echo "\n";

echo "3. Updating Course Format Plugins\n______________________________________\n\n";

$sql = "
select 
format as name, count(id) as uses
from mdl_course
group by format
order by format
;";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $sql2 = "
        select * from plugins where install_path = 'course/format/" . $row['name'] . "'
        ";
        $result2 = $conn2->query($sql2);
        if ($result2->num_rows > 0) {
            $plugin = $result2->fetch_assoc(); // Get the plugin data as array

            // Write the changes into the database
            $sql = "UPDATE plugins SET uses_number='" . $row['uses'] . "' WHERE id=" . $plugin['id'];

            if ($conn2->query($sql) === TRUE) {
                echo "Updated course/format/" . $row['name'] . " (" . $plugin['title'] . ") plugin with " . $row['uses'] . " uses\n";
            } else {
                echo "Error updating record: " . $conn->error;
            }

        }
    }
} else {
    echo "0 results\n";
}



$conn2->close();
$conn->close();
echo "\n\n------------\n\n";
?>

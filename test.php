<?php
/**
 * Created by PhpStorm.
 * User: opitz
 * Date: 23/11/21
 * Time: 12:47
 */
echo "\n\nUPU - Test Area\n";
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

echo "____________________________________________\n\n";


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

    foreach($tables as $table) {
        $plugin = substr($table, 10);
        if (preg_match('/(.*?)_/', $plugin, $match) == 1) {
            $plugin = $match[1];
        }
        echo "==> $plugin = ";

        // Now get the number of courses using each question plugin
        $sql2 = " 
        select '$plugin' as name, count(distinct cx.instanceid) as uses 
        from $table qt
        join mdl_question q on q.id = qt.questionid
        join mdl_question_categories qc on qc.id = q.category
        join mdl_context cx on cx.id = qc.contextid
        where 1
        and cx.contextlevel = 50
        ;
        ";

        $result2 = $conn->query($sql2);
        if (isset($result2->num_rows) && $result2->num_rows > 0) {
            $record = $result2->fetch_assoc();
            if(!isset($qtypes[$plugin])) {
//                echo "====> UPDATE\n";
                $qtypes[$plugin] = $record['uses'];
            } else {
                if ($qtypes[$plugin] < $record['uses']) {
                    $qtypes[$plugin] = $record['uses'];
                }
//                $qtypes[$plugin] = $record['uses'];
            }
            echo $record['uses'];
        } else {
//            $qtypes[$plugin] = "n.a.";
            echo "n.a.";
        }

        echo "\n\n";

    }

    print_r($qtypes);

} else {
    echo "Does not compute!";
}
<?php

$conn = new mysqli('localhost', 'root', '', 'adeus_admin');
// mysql 3d5749f4af07
require "Router.php";
$router = new \Bramus\Router\Router();

function show($e){
    print_r(json_encode($e, JSON_PRETTY_PRINT));
}

function san($e){
    return filter_var($e, FILTER_SANITIZE_STRING);
}


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



$router->get('/', function() {
    echo 'Smart Grid API';
});



// UPDATE TOTAL METER ENERGY
$router->patch('/meter/total_energy/(\w+)', function($e) {
    global $conn; 
    $meter_id = san($e);
    if ($energy = json_decode(file_get_contents('php://input')) ){
        mysqli_query($conn, "UPDATE meter_summary SET total_energy='$energy' WHERE meter_id='$e'");
    }
});


// ASSIGN TASK
$router->post('/task/assign/', function() {
    global $conn; 
    if ($data = json_decode(file_get_contents('php://input')) ){
        foreach ($data->tasks as $t => $m) {
            mysqli_query($conn, "INSERT INTO tasks (task_id, meter_id, status) VALUES ('$t', '$m', 'Pending')");
        }
            show('OK');
    }else{
        show("Invalid JSON");
    }
});


// UPDATE ASSIGN TASK
$router->patch('/task/assign/', function() {
    global $conn; 
    if ($data = json_decode(file_get_contents('php://input')) ){
        foreach ($data->tasks as $t => $m) {
            mysqli_query($conn, "UPDATE tasks SET  meter_id='$m' WHERE task_id='$t'");
        }
        show('OK');
    }else{
        show("Invalid JSON");
    }
});






// OLD
// %%%%%%%%%%%%%%%%%%%%%%


// hubs/
$router->get('/hubs/', function() {
    global $conn; 
    $hubs = array();
    $hubs_query = mysqli_query($conn, "SELECT * FROM hub_summary");
    while($x = mysqli_fetch_assoc($hubs_query)){$hubs[]=$x;}
    show($hubs);
});


// hubs/{count}
$router->get('/hubs/(\d+)?', function($count=1000) {
    global $conn; 
    $hubs = array();
    $count = san($count);
    $hubs_query = mysqli_query($conn, "SELECT * FROM hub_summary LIMIT 0, $count");
    while($x = mysqli_fetch_assoc($hubs_query)){$hubs[]=$x;}
    show($hubs);
});




// hubs/clutser/{cluster}
$router->get('/hubs/cluster/(\w+)', function($e) {
    global $conn; 
    $hubs = array();
    $e = san($e);

    $hubs_query = mysqli_query($conn, "SELECT DISTINCT hub_id FROM `meter_summary` JOIN meter_to_cluster ON meter_summary.meter_id=meter_to_cluster.meter_id WHERE cluster_id='$e';");
    while($x = mysqli_fetch_assoc($hubs_query)){array_push($hubs, $x['hub_id']);}
    show($hubs);
});




// hub/HUB_ID
$router->get('/hub/(\w+)', function($hub_id) {
    global $conn; 
    $hub_summary = array();
    $hub_id = san($hub_id);

    if($hub_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hub_summary WHERE hub_id='$hub_id'"))){

        $hub_summary['consuming_meters'] = array();
        $hub_summary['generating_meters'] = array();

        $meters_q = mysqli_query($conn, "SELECT * FROM meter_summary WHERE hub_id='$hub_id'");

        while ($x=mysqli_fetch_assoc($meters_q)){
            unset($x['id']);
            unset($x['hub_id']);
            if($x['meter_type']=='C'){
                $hub_summary['consuming_meters'][] = $x;
            }elseif($x['meter_type']=='G'){
                $hub_summary['generating_meters'][] = $x;
            }
        }
        
        $sockets = array();

        if($sockets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sockets_summary WHERE hub_id='$hub_id'"))){
            $sockets['socket_data'] = json_decode($sockets['socket_data']);
            unset($sockets['id']);
            unset($sockets['hub_id']);
        }

        $hub_summary['sockets']=$sockets;
    }

    show($hub_summary);
});





// meter/{meter_id}
$router->get('/meter/(\w+)', function($e) {
    global $conn; 
    $meter_summary = array();
    $e = san($e);

    $meter_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM meter_summary WHERE meter_id='$e'"));
    if ($meter_summary=='') {
        $meter_summary = "Meter not found";
    }
    show($meter_summary);
});





// meter/{meter_id}
$router->get('/meters/cluster/(\w+)', function($e) {
    global $conn; 
    $meter_summary = array();
    $e = san($e);

    $meter_query = mysqli_query($conn, "SELECT * FROM meter_to_cluster WHERE cluster_id='$e'");
    if ($meter_summary=='') {
        $meter_summary = "Meter not found";
    }

    while($x = mysqli_fetch_assoc($meter_query)){array_push($meter_summary, $x['meter_id']);}
    show($meter_summary);
});








// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
$router->run();



?>
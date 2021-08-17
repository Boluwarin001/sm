<?php 

$data = '{
  "imbalance_id":"imbalance_id_1",
  "tasks":{
    "task_id_1":"meter_id_1",
    "task_id_2":"meter_id_2"
    }
  }';

$url ="http://localhost/smart-grid-api/task/assign";

$options = array(
  'http' => array(
    'method'  => 'POST',
    'content' => $data,
    'header'=>  "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n"
    )
);

$context  = stream_context_create( $options );
$result = file_get_contents( $url, false, $context );

print_r($result);

 ?>
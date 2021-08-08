<?php
include "smile.php";
error_reporting(0);
function db_set($key, $value=null)
{
	$data = json_decode(file_get_contents(check_json), true);
	$data[$key] = (string)$value;
	$fd = fopen(check_json, 'w');
    fwrite($fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fclose($fd);
}

function db_del($key)
{
	$data = json_decode(file_get_contents(check_json), true);
	unset($data[$key]);
	$fd = fopen(check_json, 'w');
    fwrite($fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fclose($fd);
}

function all_del($key)
{
	$data = json_decode(file_get_contents('all.json'), true);
	unset($data[$key]);
	$fd = fopen("all.json", 'w');
    fwrite($fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fclose($fd);
}

const check_json = "check.json";
const all_json = "all.json";
const v = '5.101';
const token = 'токен';
$mes = "Новые оценочки ".smile()."<br>";

$check_data = json_decode(file_get_contents(check_json),true);
$today = date("Y-m-d");
$data = json_decode(file_get_contents(all_json));

foreach($data as $peer_id => $token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.dnevnik.ru/v2.0/users/me/feed?date=".$today."&limit=1&access_token=".$token);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $req = curl_exec($ch);
    curl_close($ch); 
    
    $resp = json_decode($req);
    
    $n = (string)count($resp->days[0]->summary->marksCards);
    if ($resp->type === "invalidToken")
    {
        all_del($peer_id);
        db_del($peer_id);
        $msg = "Ваш токен больше не валиден, обновите его, для этого ответьте любым сообщением ".smile();
        file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($msg)."&access_token=".token."&v=".v."&random_id=0");
    }
    elseif ($n === "0" and $resp != false)
    {
        db_del($peer_id);
    }
    elseif ((string)$check_data[$peer_id] !== $n and $resp != false)
    {
        db_set($peer_id, $n);
        $ofset = $n - $check_data[$peer_id];
        $out_msg = "";
        foreach(array_slice($resp->days[0]->summary->marksCards,0 ,$ofset ) as $all_data)
        {
            $mark = $all_data->marks[0]->mark->value;
            $subject = $all_data->subjectName;
            $out_msg = $out_msg."( ".$mark." ) ".$subject."<br>";
        }
        //отослать сообщение
        file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes.$out_msg)."&access_token=".token."&v=".v."&random_id=0");
    }
}
?>

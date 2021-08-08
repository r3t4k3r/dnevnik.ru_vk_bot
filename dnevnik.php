<?php

include "smile.php";

$data = json_decode(file_get_contents('php://input'));
$v = '5.101';
$token = 'токен';
$myid = 474370216; # ид одмина
$group_id = 186066789; # id группы
$dn_token = "xyu"; // юзлесс переменная
$confirmation = "aefecc63"; // подтверждение от вк
$site= "http://bomj5146.chsw.ru/dnevnik_bot_vk/tst.php"; //ссылка на tst.php
$secret = "2281337"; // секретный ключ ВК
$encode_key = "net_lol"; // код для шифрования, так же поменять в tst.php
$encode_algoritm = "AES-128-CBC";

//FUNCTIONS

function String2Hex($text){
    global $encode_key, $encode_algoritm;
    $string = openssl_encrypt($text, $encode_algoritm, $encode_key);
    $hex='';
    for ($i=0; $i < strlen($string); $i++){
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

function dz($today)
{
    global $dn_token;
    $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/users/me/feed?date=".$today."&limit=1&access_token=".$dn_token));
    $count = count($resp->days[0]->todayHomeworks);
    $mes = "";
    $n=0;
    for ($i = 0;$i < $count;++$i)
    {
        if (mb_strpos($mes,$resp->days[0]->todayHomeworks[$i]->work->text) === false)
        {
            $mes = $mes."&#9888;".$resp->days[0]->todayHomeworks[$i]->subjectName.":<br>".$resp->days[0]->todayHomeworks[$i]->work->text;
            if (count($resp->days[0]->todayHomeworks[$i]->work->files) != 0)
            {
                $mes = $mes."<br>* Найден прикрепленный файл ".smile();
            } 
            $mes = $mes."<br><br>";
        }
        else{$n+=1;}
    }
    if ($count == 0)
    {
        $mes = "Нет дз ".smile();
    }
    else 
    {
        $mes = "Задано ".($count-$n)."/".count($resp->days[0]->todaySchedule)."<br><br>".$mes;
    }
    return $mes;
}
function rasp($today)
{
    global $dn_token;
    $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/users/me/feed?date=".$today."&limit=1&access_token=".$dn_token));
    $count = count($resp->days[0]->todaySchedule);
    $mes = "";
    for ($i = 0;$i < $count;++$i)
    {
        $num =$i + 1;
        $mes = $mes.$num.". ".$resp->days[0]->todaySchedule[$i]->subjectName;
        if (count($resp->days[0]->todaySchedule[$i]->importantWorkTypes) != 0)
        {
            $mes = $mes." (".$resp->days[0]->todaySchedule[$i]->importantWorkTypes[0]->name.")";
        }
        $mes =$mes."<br>";
    }
    
    if ($count == 0)
    {
        $mes = "Нет уроков ".smile();
    }
    return $mes;
}
function db_set($key, $value=null)
{
	$data = json_decode(file_get_contents("all.json"), true);
	$data[$key] = $value;
	$fd = fopen("all.json", 'w');
    fwrite($fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fclose($fd);
}
function db_get($key=null)
{
	$data = json_decode(file_get_contents("all.json"), true);
	$mes = "";
	if($key != null)
	{
		$mes = $data[$key];
	}
    if ($mes == "")
	{
	    	$mes = "0";
	}
	return (string)$mes;
}
function db_del($key)
{
	$data = json_decode(file_get_contents("all.json"), true);
	unset($data[$key]);
	$fd = fopen("all.json", 'w');
    fwrite($fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fclose($fd);
}
function marks($lesson_id)
{
    global $dn_token;
    $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/lessons/".$lesson_id."/marks?access_token=".$dn_token));
    $n = count($resp);
    $mes = "";
    $marks_array = [];
    $unical_names_array = [];
    for($i = 0; $i < $n; $i++)
    {
        $response = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/persons/".$resp[$i]->person."?access_token=".$dn_token));
        if (!in_array($response->shortName, $unical_names_array)) {
            $unical_names_array[] = $response->shortName;
            $marks_array[] = $resp[$i]->value;
        }
        else {
            $marks_index = array_search($response->shortName, $unical_names_array);
            $marks_array[$marks_index] = $marks_array[$marks_index].'/'.$resp[$i]->value;
        }
    }
    for($i = 0; $i < count($marks_array); $i++)
    {
        $l = array_search(max($marks_array), $marks_array);
        $mes = $mes.'( '.$marks_array[$l].' ) # '.$unical_names_array[$l]."<br>";
        $marks_array[$l] = -1;
    }
    $mark5 = substr_count($mes,"5");
    $mark4 = substr_count($mes,"4");
    $mark3 = substr_count($mes,"3");
    $mark2 = substr_count($mes,"2");
    $new_mes = "Всего: ".($mark5+$mark4+$mark3+$mark2)."<br>Пятерок: ".$mark5."<br>Четвёрок: ".$mark4."<br>Триплетов: ".$mark3."<br>Параш: ".$mark2."<br><br>".$mes;
    return $new_mes;
}


//-----------------------------CODE---------------------------

switch ($data->type) 
{
    //CONFIRMATION
	case 'confirmation':
		echo $confirmation; //если запросили токен
		break;
	//NEW_MSG
    case 'message_new': //если новое сообщение
        $text = $data->object->text;
        $low_text = mb_strtolower($text);
        $peer_id = $data->object->peer_id; 
        $from_id = $data->object->from_id;
        $request_id = $data->object->id;
        $chat_id = $peer_id - 2000000000;
        $get_secret = $data->secret;
        
        if ($secret !== $get_secret)
        {
            echo "sosi";
            break;
        }
        
        //Проверка на повтор запроса
        if ($request_id !== 0)
        {
            $requests_data_from_file = json_decode(file_get_contents('requests.json'));
            if (in_array($request_id, $requests_data_from_file) === true)
            {
                echo "ok";
                break;
            }
            else
            {
                if (count($requests_data_from_file) > 10000)
                {
                    $requests_data_from_file = array_slice($input, 5000);
                }
                $requests_data_from_file[] = $request_id;
            	$fd = fopen("requests.json", 'w');
                fwrite($fd, json_encode($requests_data_from_file, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                fclose($fd);
            }
        }
        
        //DELETE CLUB FROM MSG
        if (strpos($text,"club".$group_id) !== false)
        {
            $text = substr($text,strpos($text,"]")+2);
            $low_text = substr($low_text,strpos($low_text,"]")+2);
        }
        //FILTER
        if (strpos($text,",") === 0)
        {
           $text = str_replace($text,",","");
           $low_text = str_replace($low_text,",","");
        }
        if (strpos($text," ") === 0)
        {
           $text = mb_substr($text,1);
           $low_text = mb_substr($low_text,1);
        }
        //IF BOT INVITE
        if ($data->object->action->type == "chat_invite_user" and $data->object->action->member_id == -$group_id)
        {
            $mes = "Назначьте бота администратором беседы и напишите команду: рег, если такой возможности нет, то напишите команду: @club".$group_id." рег";
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        if ($low_text == "stat")
        {
            $stat = (string)count(json_decode(file_get_contents("all.json"), true));
            $mes = "Юзеров в базе: ".$stat."<br>".smile();
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
            echo "ok";
            break;
        }
        
        //CHECK VALID TOKEN
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.dnevnik.ru/v2.0/users/me?access_token=".db_get($peer_id));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch); 
        $rr = json_decode($output);
        
        if ($output === "")
        {
            echo "ok";
            break;
        }
        elseif ($low_text === "рег" or $rr->type === "invalidToken")
        {
            db_del($peer_id);
            //создание ссылки
            $keyboard = "{\"buttons\":[],\"one_time\":true}";
            $encoded = String2Hex((string)$peer_id."");
            $link = "https://login.dnevnik.ru/oauth2?response_type=token&client_id=B8006D75-70A9-4291-885C-13D8511BB2AE&scope=CommonInfo,ContactInfo,FriendsAndRelatives,EducationalInfo&redirect_uri=".$site."?u=".$encoded;
            $resp = json_decode(file_get_contents("https://api.vk.com/method/utils.checkLink?url=".urlencode($link)."&access_token=".$token."&v=".$v));
            $shorted_link = json_decode(file_get_contents("https://api.vk.com/method/utils.getShortLink?url=".urlencode($link)."&access_token=".$token."&v=".$v));
			$short = $shorted_link->response->short_url;
            $mes = "Для авторизации перейдите по ссылке:\n".$short."<br>Не передавайте её 3-им лицам! Авторизация сбрасывается сама раз в 2 недели. Так же вы можете выйти, командой рег.";
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&keyboard=".urlencode($keyboard)."&access_token=".$token."&v=".$v."&random_id=0");
            echo "ok";
            break;
        }
        else
        {
            $dn_token = db_get($peer_id);
        //COMMANDS
        if ($low_text == "помощь")
        {
            $mes = "оценки - список полученых оценок сегодня
оценка [номер] - рейтинг по оценке<br>дз сегодня - заданое на сегодня дз
дз завтра - заданое на завтра дз
расписание сегодня - расписание на сегодня
расписание завтра - расписание на завтра
дз [день недели] - дз в этот день
расписание [день недели] - расписание в этот день
on - включить клавиатуру
off - выключить клавиатуру"; 
            
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        elseif ($low_text == "кто я")
        {
            $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/users/me?access_token=".$dn_token));
            $mes = $resp->shortName." ".smile();
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        elseif (strpos($low_text,"расписание ") === 0)
        {
            $day = mb_substr($low_text,11);
            if ($day == "сегодня" or $day == "завтра")
            {
                if ($day == "сегодня"){$today = date("Y-m-d");}
                elseif ($day == "завтра"){$today = date('Y-m-d', strtotime('+1 day', strtotime(date("Y-m-d"))));}
                $mes = rasp($today);
                if ($mes == ""){$mes == "Вы написали что-то не так";}
                file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
                echo "ok";
                break;
            }
            if ($day == "понедельник"){$days = "monday";}
            if ($day == "вторник"){$days = "tuesday";}
            if ($day == "среда"){$days = "wednesday";}
            if ($day == "четверг"){$days = "thursday";}
            if ($day == "пятница"){$days = "friday";}
            if ($day == "суббота"){$days = "saturday";}
            $date = new DateTime();
            $date->modify('next '.$days);
            $today = $date->format('Y-m-d');
            if ($today == date("Y-m-d")){$mes = "Вы написали что-то не так ".smile();}
            else{$mes = rasp($today);}
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
            
        }
        elseif (strpos($low_text,"дз ") === 0)
        {
            $day = mb_substr($low_text,3);
            if ($day == "сегодня" or $day == "завтра")
            {
                if ($day == "сегодня"){$today = date("Y-m-d");}
                elseif ($day == "завтра"){$today = date('Y-m-d', strtotime('+1 day', strtotime(date("Y-m-d"))));}
                $mes = dz($today);
                if ($mes == ""){$mes == "Вы написали что-то не так";}
                file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
                echo "ok";
                break;
            }
            if ($day == "понедельник"){$days = "monday";}
            if ($day == "вторник"){$days = "tuesday";}
            if ($day == "среда"){$days = "wednesday";}
            if ($day == "четверг"){$days = "thursday";}
            if ($day == "пятница"){$days = "friday";}
            if ($day == "суббота"){$days = "saturday";}
            $date = new DateTime();
            $date->modify('next '.$days);
            $today = $date->format('Y-m-d');
            if ($today == date("Y-m-d")){$mes = "Вы написали что-то не так ".smile();}
            else{$mes = dz($today);}
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        elseif (strpos($low_text,"рейт ") === 0)
        {
            $lesson_id = mb_substr($low_text,5);
            $mes = marks($lesson_id);
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        elseif ($low_text === "оценки")
        {
            $today = date("Y-m-d");
            $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/users/me/feed?date=".$today."&limit=1&access_token=".$dn_token));
            $n = count($resp->days[0]->summary->marksCards);
            $mes = "";
            $keybod = array();//2 buttons array
            $buttons = array();//buttons(2)
            for ($i = 0; $i < $n; $i++)
            {
				$plus_i="оценка-";
				$plus_i = $plus_i.($i+1);
                $mes = $mes.($i+1).". ".$resp->days[0]->summary->marksCards[$i]->subjectName." (".$resp->days[0]->summary->marksCards[$i]->workTypeName.")<br>";
                $button = array("action"=>array("type"=>"text","label"=>$plus_i),"color"=>"primary");
                $buttons[] = $button;
                if (count($buttons) === 2)
                {
					$keybod[] = $buttons;
					$buttons = array();
				}
            }
            $keybod = array_slice($keybod, 0, 5); // костыль
            if (count($buttons) !== 0)
			{
				$keybod[] = $buttons;
			}
			
            $mes = "Список предметов: <br>".$mes;
            if ($n === 0)
            {
				$mes = "Тут пустовато ".smile()."<br>Сегодня нет оценочек ".smile();
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
			}
            else
            {
				$keyboard = json_encode(array(
				"inline"=>true,
				"buttons"=>$keybod
				));
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&keyboard=".urlencode($keyboard)."&access_token=".$token."&v=".$v."&random_id=0");
			}
        }
        elseif (strpos($low_text,'оценка') === 0)
        {
            $num_mark = (int)mb_substr($low_text,7) - 1;
            if ($request_id === 0)
            {
                $mes = "К сожалению (так как вк не отсылылает ид запроса из бесед) эта функция работает только в ЛС ".smile();
                file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&keyboard=".urlencode($keyboard)."&access_token=".$token."&v=".$v."&random_id=0");
                echo "ok";
                break;
            }
            $today = date("Y-m-d");
            $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/users/me/feed?date=".$today."&limit=1&access_token=".$dn_token));
            $n = count($resp->days[0]->summary->marksCards);
            $lesson_id = $resp->days[0]->summary->marksCards[$num_mark]->lesson->id;
            $mes = 'Оценка №'.($num_mark+1)."<br><br>".marks($lesson_id);
            if ($lesson_id == ""){$mes = "Нет такой оценки ".smile();}
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
            echo ('ok');//ответ для ВК
            break;
        }
        elseif ($low_text == "on")
        {
            //KEYBOARD
            $keyboard = json_encode(array(
            "one_time"=>false,
            "buttons"=>
            array(
            	//string 1
            	array(
            		//button 1
            		array(
            			"action"=>array(
            				"type"=>"text",
            				"label"=>"Оценки"
            				),
            			"color"=>"primary"
            			),
            		//button 2
            		array(
            			"action"=>array(
            				"type"=>"text",
            				"label"=>"Помощь"
            				),
            			"color"=>"primary"
            			)
            		),
            	//string 2
            	array(
            		//button 3
            		array(
            			"action"=>array(
            				"type"=>"text",
            				"label"=>"Дз сегодня"
            				),
            			"color"=>"negative"
            			),
            		//button 4
            		array(
            			"action"=>array(
            				"type"=>"text",
            				"label"=>"Дз завтра"
            				),
            			"color"=>"positive"
            			)
            		),
            	//string 3
            	array(
            		//button 5
            		array(
            			"action"=>array(
            				"type"=>"text",
            				"label"=>"Расписание сегодня"
            				),
            			"color"=>"negative"
            			),
            		//button 6
            		array(
            			"action"=>array(
            				"type"=>"text",
            				"label"=>"Расписание завтра"
            				),
            			"color"=>"positive"
            			)
            		)
            	)
            ));
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=on&keyboard=".urlencode($keyboard)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        elseif ($low_text == "off")
        {
            $keyboard = "{\"buttons\":[],\"one_time\":true}";
            file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=off&keyboard=".urlencode($keyboard)."&access_token=".$token."&v=".$v."&random_id=0");
        }
        }
        echo ('ok');//ответ для ВК
        break;
}
?>

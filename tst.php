<html>
<body>
    <script type="text/javascript">
        let url = window.location.href;
        if (url.indexOf('#') != -1)
        {
            let new_url = url.replace("#","&");
            window.location.href = new_url;
        }
    </script>
    <?php
    include "smile.php";
    
    $encode_key = "net_lol"; // так же поменять в dnevnik.php
    $encode_algoritm = "AES-128-CBC";
    $v = '5.101';
    $token = 'c6f2bd1173198fe2a729590972bb405e1014a991b0a6d2745a9ee8bf7f117a31d8daad94bf34a4e3bdb4d'; // токен вк
    $group_id = 186066789; // ид группы

    function Hex2String($hex){
        global $encode_key, $encode_algoritm;
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        $text = openssl_decrypt($string, $encode_algoritm, $encode_key);
        return $text;
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
    	return $mes;
    }

    $test_token = $_GET['access_token'];
    $peer_id = Hex2String($_GET['u']);

    if ($test_token == "")
    {
        echo "empty";
    }
    else
    {
        #   Проверка
        if (db_get($peer_id) !== "0")
        {
            echo "Вы уже авторизованы, если хотите автироватся по новой, напишите боту \"рег\" ".smile();
            header('Refresh: 10; url=https://vk.com');
        }
        elseif ($peer_id == 0)
        {
            echo "Параметр u невалиден, сообщите администратору ".smile();
            header('Refresh: 10; url=https://vk.com');
        }
        else
        {
            $ch = file_get_contents('https://api.dnevnik.ru/v2.0/users/me?access_token='.$test_token);
            $response = json_decode($ch);
        
            if ($response->type == "invalidToken")
            {
                echo "Invalid token";
                header('Refresh: 10; url=url=https://vk.com');
            }
            elseif (db_get($peer_id) == "0")
            {
                db_set($peer_id, $test_token);
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
                $resp = json_decode(file_get_contents("https://api.dnevnik.ru/v2.0/users/me?access_token=".$test_token));
                $mes = "Вы авторизовались как \"".$resp->shortName."\" и теперь вам доступны все команды, для их вывода напишите \"помощь\" или нажмите на кнопку.<br>Вы можете сбросить авторизацию командой \"рег\". ".smile(); 
                file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&keyboard=".urlencode($keyboard)."&access_token=".$token."&v=".$v."&random_id=0");
                echo "Успешная авторизация, редирект на вк ".smile();
                header('Refresh: 5; url=https://vk.com');
            }
            else
            {
                echo "Какойта эрор, отпишите админу ".smile();
                header('Refresh: 10; url=https://vk.com');
            }
        }
    }
    ?>
</body>
</html>

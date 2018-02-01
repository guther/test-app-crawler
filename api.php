<?php
// retira do cache do ajax
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

//$_POST["nome"] = "Roberto Carlos";

// checa o envio da variável
if(!isset($_POST["nome"])){
    exit;
}

// recebe o nome da celebridade
$nome_celeb = urlencode(trim($_POST["nome"]));

// checa se existe conteúdo na variável nome
if(strlen($nome_celeb)==0){
    exit;
}

$info = [];
$info["rede_social"] = [];

$extrair_nome = ["nomecompleto","nome_completo","nome_comp","nome completo","Nome Completo","nome_denascimento"];
$extrair_datanascimento = ["nascimento_data","datadenascimento"];
$extrair_cidadenatal = ["cidadenatal","nascimento_local","nascimento_cidade","origem"];
$extrair_nomepai = ["nome_pai"];
$extrair_nomemae = ["nome_mãe"];
$extrair_conjuge = ["cônjuge ","cônjugue "];
$extrair_profissao = ["profissão","ocupação"];
$extrair_filhos = ["Filhos","filhos"];
$extrair_apelido = ["apelido"];
$extrair_altura = ["altura"];
$extrair_peso = ["peso"];
$extrair_imagem = ["imagem"];
$extrair_datamorte = ["morte_data"];
$extrair_website = ["website"];

function fnc_get_main_picture_wikipedia($id_picture){

    if(strpos($id_picture," ")!==false){
        $id_picture = urlencode($id_picture);
    }

    $json = fnc_curl("https://pt.wikipedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiprop=url&titles=Image:$id_picture");
   
    if(fnc_is_json($json)){
        $json = json_decode($json);
        return $json->query->pages->{-1}->imageinfo[0]->url;
    }
   
    return false;
}

function fnc_desambiguacao($txt){
    global $info;
    $linhas = explode("\n",$txt);

    for($i=0;$i<count($linhas);$i++){
        if(substr_count($linhas[$i],"[[")>0 && substr_count($linhas[$i],"]]")>0){
            $linhas[$i] = trim($linhas[$i]);

            if($linhas[$i][0]!="*")
                continue;

            preg_match('/\[\[(.*?)\]\]/', $linhas[$i], $match);
            $txt_link = $match[1];
            $linhas[$i] = str_replace("[[".$txt_link."]]","<a href='javascript:void(0)' onClick='fnc_remove_ambiguo(this)'>".$txt_link."</a>",$linhas[$i]);
        }
    }
    $info["ambiguo"] = implode("<br/>",$linhas);
}

function fnc_get_info_api_wikipedia($celeb=NULL){
    global  $nome_celeb,
            $info,
            $extrair_nome,
            $extrair_datanascimento,
            $extrair_cidadenatal,
            $extrair_nomepai,
            $extrair_nomemae,
            $extrair_conjuge,
            $extrair_profissao,
            $extrair_filhos,
            $extrair_apelido,
            $extrair_altura,
            $extrair_peso,
            $extrair_imagem,
            $extrair_datamorte,
            $extrair_website;

    if($celeb==NULL){
        $celeb = $nome_celeb;
    }

    $json = fnc_curl("https://pt.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&format=json&formatversion=2&titles=$celeb");
   
    if(fnc_is_json($json)){
        $json = json_decode($json);
       
        // não encontrado no Wikipdia
        if(!isset($json->query->pages[0]->revisions) && isset($json->query->pages[0]->missing) && $json->query->pages[0]->missing==true){
            return "erro";
        }
        elseif(isset($json->query->pages[0]->revisions) && strpos($json->query->pages[0]->revisions[0]->content,"{{desambiguação")!==false){
           
            fnc_desambiguacao($json->query->pages[0]->revisions[0]->content);
            return "ambiguo";
        }
        
        $conteudo = $json->query->pages[0]->revisions[0]->content;
        
        //var_dump($conteudo); exit;
       
        // checa se é redirecionamento
        if( (strpos(strtolower($conteudo),"#redirecionamento")!==false && strpos(strtolower($conteudo),"#redirecionamento")==0) || 
            (strpos(strtolower($conteudo),"#redirect")!==false && strpos(strtolower($conteudo),"#redirect")==0)
        ) {
            $novo_nome = preg_replace('/\s+/', ' ',$conteudo);
            $novo_nome = str_replace(["#REDIRECIONAMENTO [[","#redirecionamento [[","#redirect [[","#REDIRECT [["],"",$novo_nome);
            $novo_nome = substr($novo_nome,0,strlen($novo_nome)-2);
            $novo_nome = urlencode($novo_nome);
            fnc_get_info_api_wikipedia($novo_nome);
        }
        else{

            for($i=0;$i<count($extrair_nome);$i++){ 
                $aux  = fnc_wikipedia_extrair($extrair_nome[$i],$conteudo);
                if($aux){
                    $info["nome_completo"] = $aux;
                    break;
                }
            }

            for($i=0;$i<count($extrair_datanascimento);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_datanascimento[$i],$conteudo);
                if($aux){
                    $info["data_de_nascimento"] = $aux;
                    break;
                }
            }

            for($i=0;$i<count($extrair_cidadenatal);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_cidadenatal[$i],$conteudo);
                if($aux){
                    $info["cidade_natal"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_nomepai);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_nomepai[$i],$conteudo);
                if($aux){
                    $info["nome_pai"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_nomemae);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_nomemae[$i],$conteudo);
                if($aux){
                    $info["nome_mae"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_conjuge);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_conjuge[$i],$conteudo);
                if($aux){
                    $info["conjuge"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_profissao);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_profissao[$i],$conteudo);
                if($aux){
                    $info["profissao"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_filhos);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_filhos[$i],$conteudo);
                if($aux){
                    $info["filhos"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_apelido);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_apelido[$i],$conteudo);
                if($aux){
                    $info["apelido"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_altura);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_altura[$i],$conteudo);
                if($aux){
                    $info["altura"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_peso);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_peso[$i],$conteudo);
                if($aux){
                    $info["peso"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_imagem);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_imagem[$i],$conteudo);
                if($aux && !isset($info["foto"])){
                    $info["foto"] = fnc_get_main_picture_wikipedia($aux);
                }
            }

             
            for($i=0;$i<count($extrair_datamorte);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_datamorte[$i],$conteudo);
                if($aux){
                    $info["data_de_falecimento"] = $aux;
                }
            }

            for($i=0;$i<count($extrair_website);$i++){ 
                $aux = fnc_wikipedia_extrair($extrair_website[$i],$conteudo);
                if($aux){
                    $info["website"] = $aux;
                }
            }

        }
    }
    else{
        echo "wikipedia não gerou json";
    }
   
}

function fnc_wikipedia_extrair($marcador,$texto){
    global  $extrair_datanascimento,
            $extrair_datamorte,
            $extrair_cidadenatal,
            $extrair_nomemae,
            $extrair_nomepai,
            $extrair_conjuge,
            $extrair_apelido,
            $extrair_altura;

    $prefixo = ["|","| ","|  "];
    for($i=0;$i<count($prefixo);$i++){ 
        if(strpos($texto,$prefixo[$i].$marcador)!==false){
            $retorno = explode($prefixo[$i].$marcador,$texto,2);
            $retorno = trim($retorno[1]);
            $retorno = explode("\n",substr($retorno,1,strlen($retorno)));
            $retorno = trim($retorno[0]);
            
            if(strlen($retorno)>0){

                if(in_array($marcador,$extrair_datanascimento))
                    $retorno = fnc_wikipedia_format_date($retorno);
                elseif(in_array($marcador,$extrair_datamorte))
                    $retorno = fnc_wikipedia_format_date($retorno);
                elseif(in_array($marcador,$extrair_cidadenatal))
                    $retorno = fnc_wikipedia_format_cidadenatal($retorno);
                elseif(in_array($marcador,$extrair_nomemae))
                    $retorno = fnc_wikipedia_format_nomemae($retorno);
                elseif(in_array($marcador,$extrair_nomepai))
                    $retorno = fnc_wikipedia_format_nomepai($retorno);
                elseif(in_array($marcador,$extrair_conjuge))
                    $retorno = fnc_wikipedia_format_conjuge($retorno);
                elseif(in_array($marcador,$extrair_apelido))
                    $retorno = fnc_wikipedia_format_apelido($retorno);
                else if(in_array($marcador,$extrair_altura))
                    $retorno = fnc_wikipedia_format_altura($retorno);
                
                $retorno = str_replace(["[[","]]"]," ",$retorno);
            }

            return $retorno;
        }
    }
    return false;
}

function fnc_wikipedia_format_altura($altura){
    $altura = explode(" ",$altura,2);
    return $altura[0];
}

function fnc_remover_citacao($string){
    $bbcode = ["{{citar","{{Citar"];
    for($i=0;$i<count($bbcode);$i++){
        $u = 0;
        do{
            $string = fnc_delete_all_between($bbcode[$i],"}}",$string);
            $u++;
            if($u==50){
                $string .= "}}";
            }
        }
        while(strpos($string,$bbcode[$i])!==false && $u<100);
    }
    $bbcode = ["(''"];
        for($i=0;$i<count($bbcode);$i++){
            $u = 0;
            do{
                $string = fnc_delete_all_between($bbcode[$i],"'')",$string);
                $u++;
                if($u==50){
                    $string .= "'')";
                }
            }
            while(strpos($string,$bbcode[$i])!==false && $u<100);
        }
   
    $bbcode = ["[http"];
    for($i=0;$i<count($bbcode);$i++){
        $u = 0;
        do{
            $string = fnc_delete_all_between($bbcode[$i],"]",$string);
            $u++;
            if($u==50){
                $string .= "]";
            }
        }
        while(strpos($string,$bbcode[$i])!==false && $u<100);
    }
    return $string;
}

function fnc_wikipedia_format_apelido($apelido){
    $apelido = fnc_remover_citacao($apelido);
    $apelido = str_replace(["{{small|","}}"],"",$apelido);

    return $apelido;
}

function fnc_wikipedia_format_conjuge($conjuge){
    $conjuge = str_replace(["{{nowrap|","{{small|","}}","{{casamento |tipo=M|","{{casamento |tipo=m|","{{casamento |tipo=n|","{{casamento |tipo=N|","fim=div","()=pequeno |"],"",$conjuge);
    $conjuge = fnc_remover_citacao($conjuge);
    return $conjuge;
}

function fnc_wikipedia_format_nomepai($nome){
    $nome = fnc_remover_citacao($nome);
    return $nome;
}

function fnc_wikipedia_format_nomemae($nome){
    $nome = fnc_remover_citacao($nome);
    if(strpos($nome,"|")!==false){
        $nome = explode("|",$nome);
        $nome = end($nome);
    }
    return $nome;
}

function fnc_wikipedia_format_date($data){

    if(strlen($data)==0)
        return $data;

    $bbcode = ["{{nascimento|lang=br|","{{nascimento|",
                "{{dni|lang=br|","{{dni|",
                "dnibr|lang=br|","dnibr|",
                "{{morte|lang=br|","{{morte|"];

    for($i=0;$i<count($bbcode);$i++){ 
        if(strpos($data,$bbcode[$i])!==false){
            $aux = explode($bbcode[$i],$data,2);
            $aux = explode("}}",$aux[1],2);
            $data = $aux[0];
            break;
        }
    }

    $aux = (int) $data;

    if($aux==0){
        return "";
    }

    list($dia,$mes,$ano) = explode("|",$data);

    $dia = intval($dia);
    $mes = intval($mes);

    if($dia<10){
        $dia = "0".$dia;
    }

    $nmes = array(1 => "Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");

    $data = implode(" de ",[$dia,$nmes[$mes],$ano]);

    return $data;
}

function fnc_wikipedia_format_cidadenatal($cidade){
    if(strpos($cidade,"[[")!==false){
        $cidade = str_replace([" ([[","([["],", ",$cidade);
        $cidade = str_replace(["[[","]])","]]"],"",$cidade);
    }
    return $cidade;
}


function fnc_get_info_api_google(){
    global $nome_celeb, $info;

   // $key = "AIzaSyAkEU-6xM77wbmoLwIN9YWGJ6i-k4eKr8w";
    
    $key = "AIzaSyAIbn7Uy7GaRJeTGP45jhqoLjp8sUFEDO8";

    $cx = "012971029451227585742:_osukaimdsg";

    $json = fnc_curl("https://www.googleapis.com/customsearch/v1?key=$key&cx=$cx&alt=json&gl=br&q=$nome_celeb");
    if(fnc_is_json($json)){
        $json = json_decode($json);

       // var_dump($json);

        foreach($json->items as $item){
            //var_dump($item);
            
            // captura a foto do Wikipedia
            if(strpos($item->formattedUrl,"pt.bywiki.com/wiki/")!==false){
                if(is_object($item->pagemap) && is_array($item->pagemap->cse_image) && !isset($info["foto"]))
                    $info["foto"] = $item->pagemap->cse_image[0]->src;
            }

            // captura a rede social Instagram
            if(strpos($item->formattedUrl,"www.instagram.com/")!==false){
                $info["rede_social"]["instagram"] = $item->formattedUrl;
            }

            // captura a rede social Facebook
            if(strpos($item->formattedUrl,"www.facebook.com/")!==false){
                $info["rede_social"]["facebook"] = $item->formattedUrl;
            }

            // captura a rede social Twitter
            if(strpos($item->formattedUrl,"twitter.com/")!==false){
                $info["rede_social"]["twitter"] = $item->formattedUrl;
            }
        }   
    }    
}

function fnc_get_info_api_bing(){
    global $nome_celeb, $info;

    $accessKey = '38b857fe637a46c4a61af02aa55d0119';

    $url = "https://api.cognitive.microsoft.com/bing/v7.0/news/search?subscription-key=$accessKey&q=$nome_celeb";

    $json = fnc_curl($url);
    
    if(fnc_is_json($json)){
        $json = json_decode($json);
        $info["noticias"] = $json->value;
    }

}

function fnc_is_json($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function fnc_delete_all_between($beginning, $end, $string) {
    $beginningPos = strpos($string, $beginning);
    $endPos = strpos($string, $end);
    if ($beginningPos === false || $endPos === false) {
      return $string;
    }
  
    $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
  
    return str_replace($textToDelete, '', $string);
}
   
function fnc_curl($url){
    $ch = curl_init();    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    $tmpfname = '/tmp/cookie.txt';
    curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfname);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfname);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

// obter informações da API do Google
//fnc_get_info_api_google();

//obter informações da API do Wikipedia
$retorno = fnc_get_info_api_wikipedia();

// obter informações da API do Bing
if($retorno!="erro" && $retorno!="ambiguo")
    fnc_get_info_api_bing();

// não encontrou a calebridade
if($retorno=="erro")
    $info["msg_erro"] = "nao_encontrado";
elseif($retorno=="ambiguo")
    $info["msg_erro"] = "ambiguo";

header('Content-Type: application/json');
echo json_encode($info);

?>
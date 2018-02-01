function fnc_hook_ready(){
    fnc_init_listeners();
}

function fnc_init_listeners(){
    // inicia o listener do botão submit
    document.querySelector("#submit").addEventListener("click",fnc_bt_submit_click);

}

function fnc_reset_results(){
    var rs = document.querySelector("#celebrity_redes_sociais");
    rs.innerHTML = "";

    var info = document.querySelector("#celebrity_info");
    info.innerHTML = "";
    info.appendChild(rs);

    document.querySelector("#celebrity_foto").src = "";

    document.querySelector(".content").style.display="none";

}

function fnc_remove_ambiguo(obj){
    document.querySelector("#celeb").value = obj.innerHTML;
    document.querySelector("#submit").click();  
}

function fnc_renderizar_info(info){

    var info_txt = {"ambiguo" : {"label":"Ambiguidade nos resultados!<br/>Clique em uma opção abaixo para realizar a busca","group":"info"},
                    "data_de_nascimento" : {"label":"Nascimento","group":"info"},
                    "data_de_falecimento" : {"label":"Falecimento","group":"info"},
                    "cidade_natal" : {"label":"Cidade Natal","group":"info"},
                    "nome_pai" : {"label":"Nome do Pai","group":"info"},
                    "nome_mae" : {"label":"Nome da Mãe","group":"info"},
                    "conjuge" : {"label":"Cônjuge","group":"info"},
                    "filhos" : {"label":"Filhos","group":"info"},
                    "apelido" : {"label":"Apelido","group":"info"},
                    "altura" : {"label":"Altura","group":"info"},
                    "peso" : {"label":"Peso","group":"info"},
                    "profissao" : {"label":"Profissão","group":"info"},
                    "website" : {"label":"Website Oficial","group":"info"},
                    "instagram" : { "label":"Instagram", "group":"redes_sociais"},
                    "facebook"  : { "label":"Facebook",  "group":"redes_sociais"},
                    "twitter"   : { "label":"Twitter",   "group":"redes_sociais"}
                    }

    var obj;
    for(var i in info){
        obj = document.querySelector("#celebrity_"+i);
        if(obj){
            if(obj.tagName=="IMG")
                obj.src = info[i];
            else
                obj.textContent = info[i];
        }
        else if(typeof info_txt[i] != "undefined"){

            var element = fnc_criar_objeto(info_txt[i],info[i]);
            obj = document.querySelector("#celebrity_"+info_txt[i].group);
            obj.appendChild(element);

        }
        else if(typeof info[i] == "object" && i!="noticias"){
            fnc_renderizar_info(info[i]);
        }
        else{
            console.error("É necessário atribuir",i,"ao info_txt.");
        }
    }

    // reordena as redes sociais para a parte inferior das informações
    var rs = document.querySelector("#celebrity_redes_sociais");
    var info_dados = document.querySelector("#celebrity_info");
    info_dados.appendChild(rs);

    // insere as notícias
    var html_noticias = "<div class='last_news_title'>Últimas Notícias</div>";
    
    if(info!=null && Object.keys(info).indexOf("noticias")>-1){
        for(var i=0;i<info["noticias"].length;i++){
            var noticia = info["noticias"][i];
            var noticia_imagem = "img/no_image_news.png";
            if(typeof noticia.image != "undefined"){
                noticia_imagem = noticia.image.thumbnail.contentUrl;
            }
            var noticia_titulo = noticia.name;
            var noticia_url = noticia.url;
            var noticia_descricao = noticia.description;

            html_noticias += 
            "<div class='container_noticia'>"+
                "<div class='container_noticia_imagem'>"+
                    "<a href='"+noticia_url+"' target='_blank'>"+
                        "<img class='noticia_imagem' src='"+noticia_imagem+"'>"+
                    "</a>"+
                "</div>"+
                "<div class='container_noticia_txt'>"+
                    "<div class='noticia_titulo'>"+
                        "<a href='"+noticia_url+"' target='_blank'>"+
                            noticia_titulo+
                        "</a>"+
                    "</div>"+
                    "<div class='noticia_descricao'>"+
                        noticia_descricao+
                    "</div>"+
                "</div>"+
                "<div class='clear'></div>"+
            "</div>"; 
        }
    }

    document.querySelector("#celebrity_noticias_lista").innerHTML = html_noticias;

    window.document.title = info.nome_completo;
}

function fnc_criar_objeto(txt,info){
    var div = document.createElement("div");
    var label = document.createElement("span");
    label.innerHTML = txt.label+":";
    label.classList.add("label_info");
    div.appendChild(label);
    var desc = document.createElement("span");

    if(txt.group=="redes_sociais"){
        var linkk =  document.createElement("a");
        linkk.setAttribute("href",info);
        linkk.setAttribute("target","_blank");
        linkk.classList.add("link_info");
        linkk.textContent = info;
        desc.appendChild(linkk);
    }
    else{ 
        desc.innerHTML = info;
    }
    desc.classList.add("desc_info");
    div.appendChild(desc);
    return div;
}

function fnc_bt_submit_click(e){
    var celeb = document.querySelector("#celeb");
    if(fnc_validate_input_celeb(celeb)){
        var nome_celeb = celeb.value.trim();

        var xmlHttp = fnc_ajax();
        if(xmlHttp){ 
            var old_txt = e.target.value;
            fnc_bt_submit_disable(e.target);
            document.querySelector(".loading").style.display="block";
            fnc_reset_results();

            xmlHttp.onreadystatechange=function() {
                if(xmlHttp.readyState==4) {
                    var info=xmlHttp.responseText;
                    if(fnc_is_json(info)){
                        info = JSON.parse(info);
                        if(info["msg_erro"] != "nao_encontrado"){ 
                            fnc_renderizar_info(info);
                            document.querySelector(".content").style.display="block";
                        }
                        else{
                            alert("A busca não retornou resultados.\nVerifique se o nome está digitado corretamente.");
                        }
                    }
                    else{
                        console.error("JSON inválido!");
                    }
                    fnc_bt_submit_enable(e.target,old_txt);
                    document.querySelector(".loading").style.display="none";
                }
            };
            var param = "nome="+nome_celeb;
            xmlHttp.open("post","api.php",true);	
            xmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
            xmlHttp.send(param);
        }
    }
}

function fnc_bt_submit_disable(bt){
    bt.value = "AGUARDE";
    bt.disabled = true;
}
function fnc_bt_submit_enable(bt,txt){
    bt.value = txt;
    bt.disabled = false;
}
function fnc_validate_input_celeb(celeb){
    if(celeb.value.trim().length==0){
       alert("Por favor insira o nome da celebridade.");
       fnc_reset_celeb();
       return false;
    }
    return true;
}

function fnc_reset_celeb(){
    celeb.value = "";
    celeb.focus();
}

function fnc_ajax(){
    var xmlHttp;
    try{
        xmlHttp=new XMLHttpRequest(); 
    }
    catch(e){
        try{
            xmlHttp=new ActiveXObject("Msxml2.XMLHTTP"); 
        }
        catch(e){
            try{
                xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch(e){
                alert("Seu navegador não suporta AJAX!");
                return false;
            }  
        }
    }
    return xmlHttp;
}

function fnc_is_json(str) {
    try {
        JSON.parse(str);
    }
    catch (e) {
        return false;
    }
    return true;
}

document.addEventListener("DOMContentLoaded",fnc_hook_ready);
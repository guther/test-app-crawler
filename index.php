<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<title>Info Celebrity</title>

<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet"> 

<link rel="stylesheet" href="css/styles.css" />

<script>
// 
</script>

<script src="js/scripts.js"></script>

</head>

<body>
	
	<div id="main">
		<div class="header">
			<div>Info Celebrity</div>
		</div>
		
		<div class="form">
			<div>
		Procurando por informações de celebridades?
			</div>
			<div>
			  <input type="text" id="celeb" name="celeb" placeholder="Digite o nome da celebridade" required/>
			  <input type="button" id="submit" value="BUSCAR"/>
			</div>
			<div class="clear"></div>
			<div class="loading"></div>
		</div>

		<div class="content">
			<div id="celebrity_nome_completo"></div>
			<div class="container_dados">
				<div class="foto">
					<img id="celebrity_foto" onError="this.src='img/empty_photo.jpg'">
				</div>
				<div id="celebrity_info">
					<div id="celebrity_redes_sociais"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div id="celebrity_noticias_lista"></div>
		</div>
		
	</div>
	

</body>

</html>



<!doctype html>
<html lang="en">
<head>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>

  <link rel="stylesheet" href="main.css">
  <script src="password_form.js?<?= time() ?>"></script>

  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>

<form id="form-password" action="https://terminal.linerapp.com/licenseCheck.php">

  <label for="slct-widget">Выберите виджет из списка</label>
  <select id="slct-widget" name="w_code">
    <option value="copylead">Копирование сделки</option>
    <option value="autotask">Автозадачи</option>
    <option value="calc">Калькулятор</option>
    <option value="storage">Хранилище документов</option>
    <option value="distr">Распределение заявок</option>
    <option value="not_del_files">Запрет редактирвоания примечаний</option>
  </select>

  <label for="inp-subdomain">Субдомен клиент</label>
  <input id="inp-subdomain" type="text" name="user"

  ><label for="inp-password">Сгенерированный пароль</label>
  <input id="inp-password" type="text" readonly>

  <input type="submit" id="btn-generate-password" value="Сгенерировать пароль">

  <input id="inp-view" name="view" type="hidden" value="1">

</form>

</body>

</html>
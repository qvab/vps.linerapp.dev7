<?php
require_once "config.php";

class Auth
{
    private $link;
    private $config;
    private $curl;
    private $user = array();
    private $out;
    private $code;

    public function __construct()
    {
        $this->config = new Config();
        $this->link = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/auth.php?type=json';
        $this->curl = curl_init();
        $this->user = array(
            'USER_LOGIN' => $this->config->login,
            'USER_HASH' => $this->config->hash
        );
        $this->setOption();
    }

    /*
     * Устанавливаем опции
     */
    private function setOption() {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $this->link);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->user));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
    }

    /*
     * Инициируем запрос к API и сохраняем ответ в переменную
     */
    public function getOut(){
        return $this->out = curl_exec($this->curl);
    }

    /*
     * Получим HTTP-код ответа сервера
     */
    public function getCode(){
        return $this->code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
}
?>
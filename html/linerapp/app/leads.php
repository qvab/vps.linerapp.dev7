<?php
require_once "config.php";

class Leads
{
    private $link_leads;
    private $link_contacts;
    private $link_links;
    private $link_get;
    public $config;

    public function __construct()
    {
        $this->config = new Config();
        $this->link_leads = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/leads/set';
        $this->link_contacts = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';
        $this->link_links = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/links/set';
        $this->link_field = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/fields/set';
        $this->curl = curl_init();
    }
	
	/*
     * Создаем сделку
     *
     */
    public function createLead($arrLead) {
		$leads['request']["leads"]['add'] = $arrLead;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $this->link_leads);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($leads));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
		
		$out=curl_exec($this->curl);
		$code=curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		//curl_close($this->curl); 
		$response=json_decode($out,true);
		$response=$response['response'];
		return $response;
    }
	
	/*
     * Создаем поле
     *
     */
    public function createField($arrField) {
		$leads['request']["fields"]['add'] = $arrField;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $this->link_field);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($leads));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
		
		return curl_exec($this->curl);
    }
	
	/*
     * Обновляем информацию по сделке    
	 */
	 public function updateLead($arrLead) {
		$leads['request']['leads']['update'] = $arrLead;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $this->link_leads);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($leads));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
		
		return curl_exec($this->curl);
    }

    /*
     * Создаем контакт
     */
    public function setContact($id) {
        $leads['request']["contacts"]['add'] = $this->getArrayContacts($id);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $this->link_contacts);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($leads));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
    }
	
	/*
     * Обновляем информацию по контакту
     */
    public function updateContact($array) {
       $leads['request']["contacts"]['update'] = $array;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $this->link_contacts);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($leads));
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
		
		$out=curl_exec($this->curl);
		$code=curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		//curl_close($this->curl); 
		$response=json_decode($out,true);
		$response=$response['response'];
		return $response;
    }
	
	/*
     * Устанавливаем связи между сущностями
     */
    public function setLinks($array) {
        $links['request']['links']['link'] = $array;
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->curl, CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($this->curl, CURLOPT_URL,$this->link_links);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS,json_encode($links));
		curl_setopt($this->curl, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		curl_setopt($this->curl, CURLOPT_HEADER,false);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); 
		curl_setopt($this->curl, CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); 
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
		 
		$out=curl_exec($this->curl);
		$code=curl_getinfo($this->curl,CURLINFO_HTTP_CODE);

    }
	
	/*
     * Получаем сделку по id
     */
	public function getLeadById($id){
		$this->link_get = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/leads/list?id[]='.$id;
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
		curl_setopt($this->curl, CURLOPT_URL, $this->link_get);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
		
		return curl_exec($this->curl);
		//$code=curl_getinfo($this->curl,CURLINFO_HTTP_CODE);
	}
	
	/*
     * Получаем контакт по id
     */
	public function getContactById($id){
		$this->link_get = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/contacts/list?id[]='.$id;
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
		curl_setopt($this->curl, CURLOPT_URL, $this->link_get);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
	}
	
	public function getContacts(){
		$this->link_get = 'https://'.$this->config->subdomain.'.amocrm.ru/api/v2/contacts/';
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
		curl_setopt($this->curl, CURLOPT_URL, $this->link_get);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
		$out = $this->getOut();
		$response = json_decode($out, true);
		return $response;
	}
	
	/*
     * Получаем связи сделок и контактов по id контакта
     */
	public function getContactLinksById($id){
		$this->link_get = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/contacts/links';
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
		curl_setopt($this->curl, CURLOPT_URL, $this->link_get);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
		
		$out = $this->getOut();
		$response = json_decode($out, true);
		$response = $response['response']["links"];
		foreach($response as $link){
			if($id == $link["contact_id"]){
				$links[] = $link;
			}	
		}	
		return $links;
	}
	
	/*
	 * Получаем информацию о текущем аккаунте
	 */
	public function getCurrentUser(){
		$this->link_get = 'https://'.$this->config->subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
		curl_setopt($this->curl, CURLOPT_URL, $this->link_get);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt'); 
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt'); 
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
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
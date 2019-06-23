<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return 'It works, yep';
});

Route::post('/account/add', 'LeadController@addAccount');

Route::post('/leads/copy/set', 'LeadController@setCopyLead');

Route::get('/leads/copy/test', 'LeadController@testCopyLead');

Route::post('/leads/copy/resp', 'LeadController@respCopyLead');




Route::post('/leads/calc', 'LeadController@calc');
Route::post('/leads/extcalc', 'LeadController@extcalc');
Route::post('/leads/duplicate', 'LeadController@copy');
Route::post('/leads/duplicate2', 'LeadController@copy');

Route::post('/leads/distribution', 'LeadController@distribution');
Route::post('/leads/distribution/dataset', 'LeadController@distributionDataSet');
Route::post('/leads/testReq', 'LeadController@testReq');

Route::get('/leads/testdistr', 'TestController@distr');

Route::get('/test/test', 'TestController@test');


Route::model('account', App\Models\Account::class);
Route::model('autotask', App\Models\AutoTask::class);
Route::get('/cron/distribution/', 'LeadController@cronDistribution');
Route::get('/destroy/distribution/{account}', 'LeadController@destroyDistribution');

Route::get('/leads/autotask/run', 'MWautotask@run');
Route::get('/leads/autotask-date/run', 'MWAutotaskDate@run');


Route::post('/leads/autotask/set', 'LeadController@saveAutoTask');

Route::get('/leads/autotask/{account}/show', 'LeadController@getAllAutoTask');
Route::get('/leads/autotask/{account}/get/{autotask}', 'LeadController@getAutoTaskById');

Route::post('/leads/autotask/{account}/update/{autotask}', 'LeadController@updateAutoTaskById');
Route::post('/leads/autotask/{account}/delete/{autotask}', 'LeadController@deleteAutoTaskById');

Route::post('/leads/change/respstage', 'LeadController@changeRespStage');
Route::get('/leads/change/{account}/respstage', 'LeadController@getRespStage');

Route::post('/sdk/show/{products?}', 'SDKController@index');
Route::post('/sdk/link', 'SDKController@link');
Route::post('/sdk/search', 'SDKController@search');


// Googoe
Route::get('/docs/test_token/{sSubdomain}', 'MWGoogle@testToken');
Route::get('/docs/auth/', 'MWGoogle@auth');

Route::get('/docs/list/folders', 'MWGoogle@listFolder');
Route::get('/docs/get/files/{idFolder}', 'MWGoogle@getFilesInFolder');
Route::get('/docs/get/allfiles', 'MWGoogle@listFiles');

Route::post('/docs/upload', "MWGoogle@upload");


Route::post('/docs/get/files/lead', "MWGoogle@getFilesLead");
Route::get('/docs/get/file/{idFile}', "MWGoogle@getFile");

Route::get('/docs/delete/file/{idFile}', "MWGoogle@deleteFile");


Route::get('/docs/get/types/{sSubdomain}', "MWGoogle@getTypes");


Route::get('/docs/delete/type/{id}', "MWGoogle@delType");

route::post('/docs/add/type', "MWGoogle@addType");


route::post('/docs/add/setting', "MWGoogle@addSetting");


// report

route::get('/report/download/{account}/{user}', "MWReport@download");
route::post('/report/set/', "MWReport@set");
route::get('/report/get/{account_id}', "MWReport@get");


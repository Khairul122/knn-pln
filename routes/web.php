<?php
/** @var Router $router */

// Landing
$router->get('/', 'LandingController@index');

// Auth
$router->get('/login',   'AuthController@showLogin');
$router->post('/login',  'AuthController@login');
$router->get('/logout',  'AuthController@logout');

// Dashboard (protected)
$router->get('/dashboard', 'DashboardController@index');

// Pemeliharaan (protected)
$router->get('/pemeliharaan',              'PemeliharaanController@index');
$router->get('/pemeliharaan/create',       'PemeliharaanController@create');
$router->post('/pemeliharaan/create',      'PemeliharaanController@store');
$router->get('/pemeliharaan/edit/{id}',    'PemeliharaanController@edit');

// Labeling FMEA (protected)
$router->get('/labeling',                 'LabelingController@index');
$router->get('/labeling/create/{pemId}',  'LabelingController@create');
$router->post('/labeling/store',          'LabelingController@store');
$router->get('/labeling/edit/{id}',       'LabelingController@edit');
$router->post('/labeling/edit/{id}',      'LabelingController@update');
$router->post('/labeling/delete/{id}',    'LabelingController@delete');
$router->post('/labeling/delete-all',     'LabelingController@deleteAll');
$router->post('/labeling/auto-label',     'LabelingController@autoLabel');
$router->get('/labeling/split',           'LabelingController@splitForm');
$router->post('/labeling/split',          'LabelingController@executeSplit');
$router->post('/labeling/split/reset',    'LabelingController@resetSplitData');
$router->post('/pemeliharaan/edit/{id}',   'PemeliharaanController@update');
$router->post('/pemeliharaan/delete/{id}', 'PemeliharaanController@delete');

// Laporan Risiko (protected)
$router->get('/laporan', 'LaporanController@index');

// KNN Training, Evaluation & Prediction (protected)
$router->get('/knn/train',           'KnnController@trainForm');
$router->post('/knn/train',          'KnnController@train');
$router->get('/knn/evaluate',        'KnnController@evaluate');
$router->get('/knn/predict',         'KnnController@predictForm');
$router->post('/knn/predict',        'KnnController@predictManual');
$router->post('/knn/predict/batch',  'KnnController@predictBatch');
$router->post('/knn/predict/clear',  'KnnController@clearPredictionsBatch');
$router->post('/knn/delete/{id}',    'KnnController@deleteModel');

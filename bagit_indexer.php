<?php

/***
 * Script for parsing out data from Bags for indexing in ElasticSearch.
 *
 * Run 'php bagit_indexer.php --help' for usage.
 */

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
require_once 'vendor/scholarslab/bagit/lib/bagit.php';

$cmd = new Commando\Command();
$cmd->option('i')
  ->aka('input')
  ->require(true)
  ->describedAs('Absolute or relative path to a directory containing Bags. Trailing slash is optional.')
  ->must(function ($dir_path) {
      if (file_exists($dir_path)) {
          return true;
      } else {
          return false;
      }
});
$cmd->option('e')
  ->aka('elasticsearch_url')
  ->describedAs('URL (including port number) of your Elasticsearch endpoint. Default is "http://localhost:9200".')
  ->default('http://localhost:9200');
$cmd->option('x')
  ->aka('elasticsearch_index')
  ->describedAs('Elasticsearch index. Default is "bags".')
  ->default('bags');

$all_files = scandir($cmd['input']);
$bags = array_diff($all_files, array('.', '..'));
$bag_paths = array();
foreach ($bags as $bag_file) {
  $path = $cmd['input'] . DIRECTORY_SEPARATOR . $bag_file;
  $bag_paths[] = $path;
}

$climate = new League\CLImate\CLImate;
$bag_num = 0;
$progress = $climate->progress()->total(count($bag_paths));

$index['source_path'] = realpath($cmd['input']);

foreach ($bag_paths as $bag_path) {
  if (is_file($bag_path)) {
    $bag = new BagIt($bag_path);


    // $index = array('id' => pathinfo($bag_path, PATHINFO_FILENAME));
    $index['bagit_version'] = $bag->bagVersion;
    $bag->fetch->fileName = basename($bag->fetch->fileName);
    $index['fetch'] = $bag->fetch;

    $bag_info = array();
    foreach ($bag->bagInfoData as $tag => &$value) {
      $trimmed_tag = trim($tag); 
      $index['bag-info'][$trimmed_tag] = trim($value);
    }

    $manifest = $bag->manifest;

    $data_files = array_keys($manifest->data);
    $index['data_files'] = $data_files;

    unset($manifest->pathPrefix);
    $manifest->fileName = basename($manifest->fileName);

    $index['manifest'] = $manifest;

    $client = new Client([
      'base_uri' => $cmd['elasticsearch_url'],
      ['headers' => ['Content-type' => 'application/json']],
      ]);
    try {
      $request = new Request('POST', $cmd['elasticsearch_index'] . '/bag/' . pathinfo($bag_path, PATHINFO_FILENAME));
      $response = $client->send($request, ['body' => json_encode($index)]);
    }
    catch (ClientException $e) {
      print Psr7\str($e->getRequest());
      print Psr7\str($e->getResponse());
    }
  }

  $bag_num++;
  $progress->current($bag_num);
}

print "Done. " . $bag_num . " Bags added to " . $cmd['elasticsearch_url'] . '/' . $cmd['elasticsearch_index'] . PHP_EOL;

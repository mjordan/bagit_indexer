<?php

/***
 * Script for parsing out data from Bags for indexing in Solr or ElasticSearch.
 *
 * Run 'php bagit_indexer.php --help' for usage.
 */

require 'vendor/autoload.php';
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
$cmd->option('o')
  ->aka('output')
  ->require(true)
  ->describedAs('Absolute or relative path to the directory where the JSON documents will be saved. Trailing slash is optional.');

if (!file_exists($cmd['output'])) {
  mkdir($cmd['output']);
}

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

foreach ($bag_paths as $bag_path) {
  if (is_file($bag_path)) {
    $bag = new BagIt($bag_path);

    $index = array('id' => pathinfo($bag_path, PATHINFO_FILENAME));
    $index['bagit_version'] = $bag->bagVersion;
    $bag->fetch->fileName = basename($bag->fetch->fileName);
    $index['fetch'] = $bag->fetch;

    $bag_info = array();
    foreach ($bag->bagInfoData as $tag => $value) {
      $index['bag-info'][$tag] = $value;
    }

    $manifest = $bag->manifest;
    // We don't want these two bits of info.
    unset($manifest->pathPrefix);
    $manifest->fileName = basename($manifest->fileName);

    $index['manifest'] = $manifest;

    $output_file_path = $cmd['output'] . DIRECTORY_SEPARATOR . pathinfo($bag_path, PATHINFO_FILENAME) . '.json';
    file_put_contents($output_file_path, json_encode($index));
  }

  $bag_num++;
  $progress->current($bag_num);
}

print "Done. JSON files for indexing are in " . $cmd['output'] . PHP_EOL;

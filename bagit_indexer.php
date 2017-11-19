<?php

/**
 * Script for parsing out data from Bags for indexing in ElasticSearch.
 *
 * Run 'php bagit_indexer.php --help' for usage.
 */

require 'vendor/autoload.php';
require 'vendor/scholarslab/bagit/lib/bagit.php';
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use \wapmorgan\UnifiedArchive\UnifiedArchive;

$descriptive_extensions = array('txt', 'md', 'xml');

$cmd = new Commando\Command();
$cmd->option('i')
  ->aka('input')
  ->require(true)
  ->describedAs('Absolute or relative path to either a directory containing Bags (trailing slash is optional), or to a Bag filename.')
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
$cmd->option('d')
  ->aka('descriptive_files')
  ->describedAs('Comma-separated list of file paths relative to the Bag data directory that are to be indexed into the "descriptive" field.')
  ->default('');
$cmd->option('x')
  ->aka('elasticsearch_index')
  ->describedAs('Elasticsearch index. Default is "bags".')
  ->default('bags');

if (is_dir($cmd['input'])) {
  $all_files = scandir($cmd['input']);
  $bags = array_diff($all_files, array('.', '..'));
  $bag_paths = array();
  foreach ($bags as $bag_file) {
    $path = $cmd['input'] . DIRECTORY_SEPARATOR . $bag_file;
    $bag_paths[] = $path;
  }
}
else {
  $bag_paths = array();
  $bag_paths[] = $cmd['input'];
}

$climate = new League\CLImate\CLImate;
$bag_num = 0;
$progress = $climate->progress()->total(count($bag_paths));

$index['bag_location'] = realpath($cmd['input']);

foreach ($bag_paths as $bag_path) {
  if (is_file($bag_path)) {
    $bag = new BagIt($bag_path);

    $errors = $bag->validate();
    if (count($errors) === 0) {
      $index['bag_validated']['timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
      $index['bag_validated']['result'] = 'valid';
    } else {
      $index['bag_validated']['timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
      $index['bag_validated']['result'] = 'invalid';
      $index['bag_validated']['errors'][] = $errors;
    }

    $bag_id = pathinfo($bag_path, PATHINFO_FILENAME);

    $bag_sha1 = sha1_file($bag_path);
    $index['bag_hash']['type'] = 'sha1';
    $index['bag_hash']['value'] = $bag_sha1;

    $index['bagit_version'] = $bag->bagVersion;
    $bag->fetch->fileName = basename($bag->fetch->fileName);
    $index['fetch'] = $bag->fetch;

    $index['descriptive'] = get_descriptive_data($bag_id, realpath($bag_path), $cmd['descriptive_files']);
    var_dump($index['descriptive']);

    $bag_info = array();
    foreach ($bag->bagInfoData as $tag => &$value) {
      $trimmed_tag = trim($tag); 
      $index['bag-info'][$trimmed_tag] = trim($value);
    }

    $manifest = $bag->manifest;

    unset($manifest->pathPrefix);
    $manifest->fileName = basename($manifest->fileName);

    $data_files = array_keys($manifest->data);
    $index['data_files'] = $data_files;

    $index['manifest'] = $manifest;

    $client = new Client([
      'base_uri' => $cmd['elasticsearch_url'],
      ]);
    try {
      $response = $client->post($cmd['elasticsearch_index'] . '/bag/' . $bag_id, ['json' => $index]);
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

/**
 * Gets content of files to populate the 'descriptive' field.
 *
 * @param string $paths
 *   A string containing a comma-separate list of file paths relative to the
 *   Bag's 'data' directory.
 *
 * @return string
 *   The concatentated values of the text from each of the files
 *   in $paths.
 */
function get_descriptive_data($bag_id, $bag_path, $paths) {
  global $descriptive_extensions;
  if (!strlen($paths)) {
    return '';
  }
  $descriptive_text = '';
  $file_paths = explode(',', $paths);
  if (!$archive = UnifiedArchive::open($bag_path)) {
    return '';
  }
  // var_dump($archive->getFileNames());
  foreach ($file_paths as $path) {
    $text = '';
    $path_in_archive = $bag_id . '/data/' . $path;
    $ext = pathinfo($path_in_archive, PATHINFO_EXTENSION);
    $file_content = $archive->getFileContent($path_in_archive);
    if (strlen($file_content)) {
      $file_content = trim($file_content) . ' ';
    }
    var_dump($file_content);
    if ($file_content = $archive->getFileContent($path_in_archive)) {
      // If extension is .xml, parse out the text content of all elements.
      if (in_array($ext, $descriptive_extensions) && ($ext == 'xml')) {
        $dom = new DOMDocument();
        $dom->loadXML($file_content);
        $text = $dom->documentElement->textContent;
        $text = preg_replace('/\s+/', ' ', $descriptive_text);
        var_dump($text);
      }
      // If extension is not .xml, read in the file content as is.
      if (in_array($ext, $descriptive_extensions) && ($ext != 'xml')) {
        $text = preg_replace('/\s+/', ' ', $file_content);
      }
    }
    $descriptive_text .= $text;
  }
  return trim($descriptive_text);
}

<?php

/**
 * Script for querying data from Bags indexed in ElasticSearch.
 *
 * Run 'php bagit_searcher.php --help' for usage.
 */

require 'vendor/autoload.php';
use Elasticsearch\ClientBuilder;

$cmd = new Commando\Command();
$cmd->option('q')
  ->aka('query')
  ->require(true)
  ->describedAs('Query to perform.');
$cmd->option('e')
  ->aka('elasticsearch_url')
  ->describedAs('URL (including port number) of your Elasticsearch endpoint. Default is "http://localhost:9200".')
  ->default('http://localhost:9200');
$cmd->option('x')
  ->aka('elasticsearch_index')
  ->describedAs('Elasticsearch index. Default is "bags".')
  ->default('bags');

$climate = new League\CLImate\CLImate;

$hosts = array($cmd['e']);

$clientBuilder = ClientBuilder::create();
$clientBuilder->setHosts($hosts);
$client = $clientBuilder->build();

// Build the query.
list($field, $query) = explode(':', $cmd['q']);
switch ($field) {
  case 'description':
    $query_field = 'bag-info.External-Description';
    break;
  case 'date':
    $query_field = 'bag-info.Bagging-Date';
    break;
  case 'org':
    $query_field = 'bag-info.Source-Organization';
    break;
  case 'file':
    $query_field = 'data_files';
    break;
  case 'bag_location':
    $query_field = 'bag_location';
    break;
  default:
    print "Sorry, I don't recognize that field; you can use 'description', 'date', 'org', 'file', or 'bag_location'." . PHP_EOL;
    exit;
}

$params = [
    'index' => 'bags',
    'type' => 'bag',
    'body' => [
        'query' => [
            'match' => array($query_field => $query),
        ]
    ]
];

// Get the results and show them to the user.
$results = $client->search($params);
if ($results['hits']['total'] > 0) {
  print "Your query found " . $results['hits']['total'] . " hit(s): " . PHP_EOL;
  $table_data = array();
  foreach ($results['hits']['hits'] as $hit) {
    switch ($field) {
      case 'description':
      $table_data[] = array('Bag ID' => $hit['_id'], 'External-Description' => $hit['_source']['bag-info']['External-Description']);
      break;
    case 'date':
      $table_data[] = array('Bag ID' => $hit['_id'], 'Bagging-Date' => $hit['_source']['bag-info']['Bagging-Date']);
      break;
    case 'org':
      $table_data[] = array('Bag ID' => $hit['_id'], 'Bagging-Date' => $hit['_source']['bag-info']['Source-Organization']);
      break;
    case 'file':
      $table_data[] = array('Bag ID' => $hit['_id'], 'Data files' => implode(", ", $hit['_source']['data_files']));
      break;
    case 'bag_location':
      $table_data[] = array('Bag ID' => $hit['_id'], 'Source path' => $hit['_source']['bag_location']);
      break;
    }
  }
  $climate->table($table_data);
}
else {
  print "Your query found no hits. Sorry." . PHP_EOL;
}


# BagIt Indexer

A proof-of-concept tool for extracting data from [Bags](https://en.wikipedia.org/wiki/BagIt) and indexing it in Elasticsearch. Its purpose is to demonstrate potential techniques for managing Bags, ranging from retrieving a specific file in a Bag to preparing for digital preservation processes such as auditing or format migrations.

For example, questions you can ask of the sample data in this Git repository include:

* which Bags were created on a specific date
* which Bags contain a specific file in their `data` directory
* which Bags have specific keywords in their bag_info.txt description
* which Bags have specific keywords in text or XML files in their `data` directory
* which Bags were created by a specific organization

With a little more developement beyond this proof of concept, you could ask questions like:

* I want to know which Bags were created between two dates
* I want to find all Bags with a specific Bagit version
* I want to know which Bags have `fetch` URLs
* I want to know which Bags have `fetch` URLs that point to a specific hostname
* I have a Bag's identifier and I want to find what storage location/directory the Bag is in
* I have a file, and I want to query Elasticsearch to see if its SHA-1 (or other) hash matches any that are in Bag
* I want to know which Bags have a 'Bag-Group-Identifier' tag that contains the ID of a specific collection
* I want to know which Bags use a specific [BagIt profile](https://github.com/ruebot/bagit-profiles)
* I want to know which Bags contain a specific file that is not managed by the Bag (e.g., "DC.xml" in the root of the Bag)

Using Elasticsearch's [Kibana](https://www.elastic.co/products/kibana), it is possible to create visualizations of the indexed data. This [video](https://youtu.be/mMhnGjp8oOI) provides a useful introduction to Kibana.


Features that may be desirable in a tool based on this proof of concept include:

- [x] On adding new Bags to the input directory, index them automatically.
- [ ] On moving Bags to a different storage location, or renamig them, update their "bag_location" values in the Elasticsearch index
- [ ] On replacing (updating) Bags, replace their records in the Elasticsearch index
- [ ] On deleting Bags, replace their records in the Elasticsearch index with a tombstone
- [x] On indexing, validate the Bags index any validation errors in Elasticsearch
- [x] On indexing, generate a SHA-1 or other checksum of the serialized Bag itself and add it to the Elasticsearch index, to assist in bit-level integrity checking of the Bag itself
- [ ] Log indexing errors
- [x] Add the ability to index specific content files within the Bags, to assist in discovery and management
- [ ] Develop a desktop or web-based app that performs functions similar to this command-line tool

## System requirements and installation

To install and run this proof of concept indexer, you will need:

* PHP 5.5.0 or higher command-line interface
* [Composer](https://getcomposer.org)
* An [Elasticsearch server](https://www.elastic.co/products/elasticsearch) version 5.x or higher.
  * The scripts in the 'vagrant' directory will help you set up an Elasticsearch instance for testing.
* Some Bags. The samples used in this README are in the 'sample_bags' directory.

To install the Bagit Indexer:

* Clone the Git repo
* `cd bagit_indexer`
* `php composer.phar install` (or equivalent on your system, e.g., `./composer install`)

## Indexing Bags

`./index` extracts data from Bags and pushes it into Elasticsearch.

Run `./index --help` to get help info:

```
--help
     Show the help page for this command.

-i/--input <argument>
     Required. Absolute or relative path to either a directory containing Bags (trailing slash is optional), or a Bag filename.

-c/--content_files <argument>
     Comma-separated list of plain text or XML file paths relative to the Bag data directory that are to be indexed into the "content"
     field, e.g., "--content MODS.xml,notes.txt".

-e/--elasticsearch_url <argument>
     URL (including port number) of your Elasticsearch endpoint. Default is "http://localhost:9200".

-x/--elasticsearch_index <argument>
     Elasticsearch index. Default is "bags".
```

To index Bags (serialized or loose) in your input directory, run the `index` script like this:

```
./index -i sample_bags
```

You will see the following:

```
====================================================================================================> 100%
Done. 5 Bags added to http://localhost:9200/bags
```
This indexing results in an Elasticsarch document for each Bag like this:

```json
{
	"_index": "bags",
	"_type": "bag",
	"_id": "bag_03",
	"_version": 74,
	"found": true,
	"_source": {
		"bag_location": "\/home\/mark\/Documents\/hacking\/bagit\/bagit_indexer\/sample_bags",
		"bag_validated": {
			"timestamp": "2017-11-19T18:27:29Z",
			"result": "valid"
		},
		"bag_hash": {
			"type": "sha1",
			"value": "ebd53651c768da1dbca352988e8a93d3f5f9c2d7"
		},
		"bagit_version": {
			"major": 0,
			"minor": 96
		},
		"fetch": {
			"fileName": "fetch.txt",
			"data": [],
			"fileEncoding": "UTF-8"
		},
		"content": "Hi! A sample text file.",
		"bag-info": {
			"External-Description": "A simple bag.",
			"Bagging-Date": "2016-02-28",
			"Internal-Sender-Identifier": "bag_03",
			"Source-Organization": "Acme Bags",
			"Contact-Email": "info@acmebags.com"
		},
		"data_files": ["data\/atextfile.txt", "data\/master.tif", "data\/metadata.xml"],
		"manifest": {
			"fileName": "manifest-sha1.txt",
			"hashEncoding": "sha1",
			"fileEncoding": "UTF-8",
			"data": {
				"data\/atextfile.txt": "eb2614a66a1d34a6d007139864a1a9679c9b96aa",
				"data\/master.tif": "44b16ef126bd6e0ac642460ddb1d8b1551064b03",
				"data\/metadata.xml": "78f4cb10e0ad1302e8f97f199620d8333efaddfb"
			}
		}
	}
}
```

This is the data that you will be querying in the "Finding Bags" section.

## Indexing "Content" files

Including the `--content_files` option will index the content of the specified files and store it in the Elasticsearch 'content' field. You should only include paths to plain text or XML files, not paths to image, word processing, or other binary files. If you list multiple files, the content from all files is combined into one 'content' field.

A possible enhancement to this feature would be to use Apache Tika to extract the text content from a [wide variety of file formats](https://tika.apache.org/1.16/formats.html).

## Finding Bags

The `find` script allows you to perform simple queries against the indexed data. The following types of queries are possible:

* 'content', which queries the contents of plain text or XML files in the Bag's 'data' directory
* 'description', which queries the contents of the `bag-info.txt` 'External-Description' tag
* 'date', which queries the contents of the `bag-info.txt` 'Bagging-Date' tag
* 'org', which queries the contents of the `bag-info.txt` 'Source-Organization' tag
* 'file', which queries filepaths of files in the Bag's `data` directory
* 'bag_location', which queries filepaths of the Bag's storage location, which is the value provided to `index`'s `-input` option when the index was populated

Queries take the form `-q field:query`. For example, to search for the phrase "cold storage" in the description, run the command (note that quotes are required because of the space in the query):

```./find -q "description:cold storage"```

which will return the following results:

```
Your query found 2 hit(s): 
----------------------------------------------------------------------------------------------
| Bag ID | External-Description                                                              |
==============================================================================================
| bag_01 | Contains some stuff we want to put into cold storage.                             |
----------------------------------------------------------------------------------------------
| bag_02 | Contains some stuff we want to put into cold storage, and that is very important. |
----------------------------------------------------------------------------------------------
```

To search for Bags that have a Bagging-Date of "2017-06-18", run this command:

```./find -q date:2017-06-18```

which will return the following result:

```
Your query found 4 hit(s): 
------------------------------
| Bag ID      | Bagging-Date |
==============================
| bag_01002   | 2017-06-18   |
------------------------------
| bag_02      | 2017-06-18   |
------------------------------
| bag_01      | 2017-06-18   |
------------------------------
| bag_z2098-4 | 2017-06-18   |
------------------------------
```

To search for Bags that contain a file under `data` named 'master.tif', run this command:

```./find -q file:master.tif```

which will return the following result:

```
Your query found 1 hit(s): 
-------------------------------------------------------------------
| Bag ID | Data files                                             |
===================================================================
| bag_03 | data/atextfile.txt, data/master.tif, data/metadata.xml |
-------------------------------------------------------------------
```

## Retrieving the Elasticsearch document for a specific Bag

If you want to retrieve the raw Elasticsearch document for a specific Bag, use the `--id` option instead of the `-q` option, and provide the Bag's ID:

```./find --id bag_03```

## Sample Bags

Here are the values from `bag-info.txt` tags and the list of files in the `data` directories for the sample Bags, in case you want to try some searches of your own:

* bag_01
  * External-Description: Contains some stuff we want to put into cold storage.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: Bag_01
  * Source-Organization: Bags R Us
  * Contact-Email: contact@bagrus.com
  * Files
    * data/anotherkindoffile.dat
    * data/anothertextfile.txt
    * data/atextfile.txt
* bag_01002
  * External-Description: The content we said we would send you.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: bag_01002
  * Source-Organization: Acme Bags
  * Contact-Email: info@acmebags.com
  * Files
    * data/anothertextfile.txt
    * data/atextfile-09910.txt
    * data/important.xxx
* bag_02
  * External-Description: Contains some stuff we want to put into cold storage, and that is very important.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: Bag_02
  * Source-Organization: Bags R Us
  * Contact-Email: contact@bagrus.com
  * Files
    * data/anothertextfile.txt
    * data/atextfile-2.txt
    * data/data_2.dat
    * data/subdir/data_3.dat
* bag_03
  * External-Description: A simple bag.
  * Bagging-Date	: 2016-02-28
  * Internal-Sender-Identifier: bag_03
  * Source-Organization: Acme Bags
  * Contact-Email: info@acmebags.com
  * Files
    * data/atextfile.txt
    * data/master.tif
    * data/metadata.xml
* bag_z2098-4
  * External-Description: The content we said we would send you.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: bag_z2098-4
  * Source-Organization: Acme Bags
  * Contact-Email: info@acmebags.com
  * Files
    * data/1/acontentfile.txt
    * data/2/acontentfile.txt
    * data/3/acontentfile.txt

## Monitoring input directories for changes

The Python script `bagit_watcher.py` will monitor a directory for new Bags and index them automatically. Run it like this:

`python bagit_watcher.py /path/to/input/dir`

where `/path/to/input/dir` is the directory you want to watch. This should correspond to the directory specified in the`-i`/`--input` option used with `index`. Currently the watcher only reacts to new files, but it would be possible to make it react to updated, renamed, moved, or deleted Bag files as well (provided those features were added to the `index` script).

## License

![This work is in the Public Domain](http://i.creativecommons.org/p/mark/1.0/88x31.png)

To the extent possible under law, Mark Jordan has waived all copyright and related or neighboring rights to this work. This work is published from: Canada. 

## Contributing

Since this is proof-of-concept code, I don't intend to add a lot more features. However, this proof of concept could be used as the basis for a production application. Fork and enjoy!

That said, if you have any questions or suggestions, feel free to open an issue.

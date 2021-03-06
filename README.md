# BagIt Indexer

A proof-of-concept tool for extracting data from serialized (zipped) [Bags](https://en.wikipedia.org/wiki/BagIt) and indexing it in Elasticsearch. Its purpose is to demonstrate potential techniques for managing Bags, ranging from retrieving a specific file in a Bag to preparing for digital preservation processes such as auditing or format migrations.

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

Using Elasticsearch's [Kibana](https://www.elastic.co/products/kibana), it is possible to create visualizations of the indexed data. This [video](https://youtu.be/mMhnGjp8oOI) provides a useful introduction to Kibana.


Features that may be desirable in a tool based on this proof of concept include:

- [x] On adding new Bags to the input directory, index them automatically.
- [ ] On moving Bags to a different storage location, or renamig them, update their "bag_location" values in the Elasticsearch index
- [ ] On replacing (updating) Bags, replace their records in the Elasticsearch index
- [x] On deleting Bags, replace their records in the Elasticsearch index with a tombstone
- [x] On indexing, validate the Bags and record any validation errors in Elasticsearch
- [ ] Log indexing errors
- [x] Add the ability to index specific content files within the Bags, to assist in discovery and management
- [ ] Develop a desktop or web-based app that performs functions similar to this command-line tool
- [ ] Use Apache Tika to extract content from files for indexing
- [ ] For Bags that are updated, moved, remaned, or deleted, commit the Elasticsearch document to a Git repository in order to track changes to it over time

This proof of concept implementation can index Bags stored at disparate locations (and on heterogeneous hardware):

![Overview diagram](overview.png)

In addition to preservation staff querying the index, automated processes can as well, for example a script to generate a daily list of new Bags added to the index.

## System requirements and installation

To install and run this proof of concept indexer, you will need:

* PHP 5.5.0 or higher command-line interface
* [Composer](https://getcomposer.org)
* An [Elasticsearch server](https://www.elastic.co/products/elasticsearch) version 5.x or higher.
  * The scripts in the 'vagrant' directory will help you set up an Elasticsearch instance for testing.
* Some Bags. The samples used in this README are in the 'sample_bags' directory.
* To use the `watch` script, you will need to install the Python [watchdog](https://pypi.python.org/pypi/watchdog) library

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
	"_id": "ebd53651c768da1dbca352988e8a93d3f5f9c2d7",
	"_version": 2,
	"found": true,
	"_source": {
		"bag_location_exact": "\/home\/mark\/Documents\/hacking\/bagit\/bagit_indexer\/sample_bags\/bag_03.tgz",
		"bag_location": "\/home\/mark\/Documents\/hacking\/bagit\/bagit_indexer\/sample_bags\/bag_03.tgz",
		"bag_validated": {
			"timestamp": "2017-11-19T22:36:52Z",
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
		"serialization": "tgz",
		"content": "",
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
		},
		"tombstone": false
	}
}
```

This is the data that you will be querying in the "Finding Bags" section.

## The Bag's identifier within the index

Within the index, each Bag is identified by its SHA1 checksum value at the time of initial indexing. Using the SHA1 value ensures that each Bag's ID is unique. Alternatives identifiers include the Bag's filename or the value of a required tag in the `bagit-info.txt` file. However, both of these are problematic because it would be very difficult to guarantee that they will provide unique values. Another option is to have the `index` script assign a UUID. The UUID would be unique, but the SHA1 value has the added advantage of being derivable from the serialized Bag file itself in the event that the Elasticsearch index becomes lost.

The advantage of having the file's ID derived from the file itself only applies to Bags that have never been modified. The ability to derive a Bag's ID from its SHA1 checksum is lost once the Bag has been modified. This disadvantage can be mitigated by storing the history of changes to the Elasticsearch document for the Bag in a Git repository, for example, by being able search for the Bag's current SHA1 value in the Git repository and getting its ID from there.

## Indexing "content" files

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
* 'bag_location_exact', which contains the same value as 'bag_location' but provides exact searches on it.

Queries take the form `-q field:query`. For example, to search for the phrase "cold storage" in the description, run the command (note that quotes are required because of the space in the query):

```./find -q "description:cold storage"```

which will return the following results:

```
Your query found 2 hit(s): 
--------------------------------------------------------------------------------------------------------------------------------
| Bag ID                                   | External-Description                                                              |
================================================================================================================================
| 212835b8628503774e482279167a1c965d107303 | Contains some stuff we want to put into cold storage.                             |
--------------------------------------------------------------------------------------------------------------------------------
| 0216ce82b6a3c4ff127c28569f4ae84589bc3e99 | Contains some stuff we want to put into cold storage, and that is very important. |
--------------------------------------------------------------------------------------------------------------------------------
```

To search for Bags that have a Bagging-Date of "2017-06-18", run this command:

```./find -q date:2017-06-18```

which will return the following result:

```
Your query found 4 hit(s): 
-----------------------------------------------------------
| Bag ID                                   | Bagging-Date |
===========================================================
| 0216ce82b6a3c4ff127c28569f4ae84589bc3e99 | 2017-06-18   |
-----------------------------------------------------------
| 212835b8628503774e482279167a1c965d107303 | 2017-06-18   |
-----------------------------------------------------------
| 7c17053b7d30abd69c5e0eb10d5cc4c2ad915f4f | 2017-06-18   |
-----------------------------------------------------------
| fa50e06f6cc12e9e1b90e84da1f394bb8b624d54 | 2017-06-18   |
-----------------------------------------------------------
```

To search for Bags that contain a file under `data` named 'master.tif', run this command:

```./find -q file:master.tif```

which will return the following result:

```
Your query found 1 hit(s): 
-----------------------------------------------------------------------------------------------------
| Bag ID                                   | Data files                                             |
=====================================================================================================
| ebd53651c768da1dbca352988e8a93d3f5f9c2d7 | data/atextfile.txt, data/master.tif, data/metadata.xml |
-----------------------------------------------------------------------------------------------------
```

## Retrieving the Elasticsearch ID and location for all Bags

If you want to see a list of all Bags' IDs and file path locations, issue the following command:

```./find -a``` 

## Retrieving the Elasticsearch document for a specific Bag

If you want to retrieve the raw Elasticsearch document for a specific Bag, use the `--id` option instead of the `-q` option, and provide the Bag's ID:

```./find --id ebd53651c768da1dbca352988e8a93d3f5f9c2d7```

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

The Python script `watch` will monitor a directory for new and updated Bags and index them automatically. Run it like this:

`./watch /path/to/input/dir`

where `/path/to/input/dir` is the directory you want to watch. This should correspond to the directory specified in the`-i`/`--input` option used with `index`. Currently the watcher only reacts to new and deleted Bag files, but it would be possible to make it react to modified, renamed and moved Bag files as well (provided those features were added to the `index` script).

## Tombstones

Deletions of Bags should be recorded with the `tombstone` script, which updates the Bag's entry in the index in the following ways:

* the `tombstone` field is updated to indicate `true`
* the `document_timestamp` field is updated to the date when `tombstone` was run

The `tombstone` command's parameters are:

```
--help
     Show the help page for this command.

-e/--elasticsearch_url <argument>
     URL (including port number) of your Elasticsearch endpoint. Default is "http://localhost:9200".


-x/--elasticsearch_index <argument>
     Elasticsearch index. Default is "bags".

-i/--id <argument>
     The ID of the bag to create the tombstone for. Use either this option or --path.


-p/--path <argument>
     Absolute or relative path to the Bag filename to create the tombstone for. Use either this option or --id.
```

To see which Bag entries in the index are flagged as tombstones, you can issue queries like this:

```
./find -q "tombstone:true"
Your query found 1 hit(s): 
--------------------------------------------------------
| Bag ID                                   | Tombstone |
========================================================
| 212835b8628503774e482279167a1c965d107303 | 1         |
--------------------------------------------------------

./find -q "tombstone:false"
Your query found 4 hit(s): 
--------------------------------------------------------
| Bag ID                                   | Tombstone |
========================================================
| 0216ce82b6a3c4ff127c28569f4ae84589bc3e99 |           |
--------------------------------------------------------
| ebd53651c768da1dbca352988e8a93d3f5f9c2d7 |           |
--------------------------------------------------------
| 7c17053b7d30abd69c5e0eb10d5cc4c2ad915f4f |           |
--------------------------------------------------------
| fa50e06f6cc12e9e1b90e84da1f394bb8b624d54 |           |
--------------------------------------------------------
```

The false values show up as blank in the results - that is normal.

## License

![This work is in the Public Domain](http://i.creativecommons.org/p/mark/1.0/88x31.png)

To the extent possible under law, Mark Jordan has waived all copyright and related or neighboring rights to this work. This work is published from Canada. 

## Contributing

Since this is proof-of-concept code, I don't intend to add a lot more features. However, this proof of concept could be used as the basis for a production application. Fork and enjoy!

That said, if you have any questions or suggestions, feel free to open an issue.

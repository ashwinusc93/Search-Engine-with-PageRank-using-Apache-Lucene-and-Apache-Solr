# Search-Engine-with-PageRank-using-Apache-Lucene-and-Apache-Solr

This is a Search Engine implemented using Apache Lucene and Apache Solr which has both Spell correction and autocomplete features

The Search can be performed using 2 algorithms - Solr's default Lucene Vector Space Algorithm and PageRank Algorithm

The Python Code generates PageRank file by first building a NetworkX graph, created based on the number of outlinks between the pages extracted from a particular directory.

This external pagerank file is placed into the Solr core which is re-indexed and Search can then be performed

A PHP application is built which helps users enter search queries and get results

The Spell Correction  is implemented in PHP using Norvig's Algorithm 




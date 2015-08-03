<?php

namespace Odango;

include 'NyaaTorrent.php';

class Nyaa {
	protected $categories = [
        "All" => "0_0",
	    "Anime" => "1_0",
	    "Anime Music Video" => "1_32",
	    "English-translated Anime" => "1_37",
	    "Non-English-translated Anime" => "1_38",
	    "Raw Anime" => "1_11",
	    "Audio" => "3_0",
	    "Lossless Audio" => "3_14",
	    "Lossy Audio" => "3_15",
	    "Literature" => "2_0",
	    "English-translated Literature" => "2_12",
	    "Non-English-translated Literature" => "2_39",
	    "Raw Literature" => "2_13",
	    "Live Action" => "5_0",
	    "English-translated Live Action" => "5_19",
	    "Live Action Promotional Video" => "5_22",
	    "Non-English-translated Live Action" => "5_21",
	    "Raw Live Action" => "5_20",
	    "Pictures" => "4_0",
	    "Graphics" => "4_18",
	    "Photos" => "4_17",
	    "Software" => "6_0",
	    "Applications" => "6_23",
	    "Games" => "6_24"
	];

	protected $baseUrl = 'http://www.nyaa.se/';

	protected $filters = [
		"All" => 0,
		"Remakes" => 1,
		"Trusted" => 2,
		"A+" => 3
	];

	public function getFeed($options = []) {
		$get = [
			'page' => 'rss'
		];

		if (!empty($options['query'])) {
			$get['term'] = $options['query'];
		}

		if (!empty($options['category'])) {
			$get['cats'] = $options['category'];
		}
		
		if (!empty($options['filter'])) {
			$get['filter'] = $options['filter'];
		}

		if (!empty($options['offset'])) {
			$get['offset'] = $options['offset'];
		}

		if (!empty($options['user'])) {
			$get['user'] = $options['user'];
		}

		$fullUrl = $this->baseUrl.'?'.http_build_query($get);
		$xml = file_get_contents($fullUrl);
		$simple = simplexml_load_string($xml);

		$items = $simple->channel->item;

		$torrents = [];

		foreach ($items as $item) {
			$torrents[] = NyaaTorrent::fromSimpleXml($item, isset($get['user']) ? $get['user'] : null);
		}

		return $torrents;
	}
}

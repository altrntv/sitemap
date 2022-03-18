<?php

use blitz\sitemap\SiteMap;

require "src/sitemap.php";

$array = array(
    [
        "loc" => "https://site.ru/",
        "lastmod" => "2022-03-11",
        "priority" => 1,
        "changefreq" => "daily",
    ],
    [
        "loc" => "https://site.ru/map",
        "lastmod" => "2022-03-10",
        "priority" => 0.5,
        "changefreq" => "daily"
    ]
);

$map = new SiteMap();

echo $map->CreateMap($array, "csv", "./var/www/site.ru/upload/sitemap.csv")."<br>";
echo $map->CreateMap($array, "xml", "./var/www/site.ru/upload/sitemap.xml")."<br>";
echo $map->CreateMap($array, "json", "./var/www/site.ru/upload/sitemap.json")."<br>";
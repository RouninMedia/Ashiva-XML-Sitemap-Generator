<?php

  //=================//
 // ERROR REPORTING //
//=================//

    error_reporting(E_ALL);
    ini_set('display_errors', 1);



  //=========================//
 // SET UP GLOBAL VARIABLES //
//=========================//

    $Time_of_Generation = date("Y-m-d_H-i-s");
    $Path = $_SERVER['DOCUMENT_ROOT'].'/';
    $Resource_List =  array();


  //===================//
 // GET ALL FILENAMES //
//===================//

function Get_All_Filenames($Path, $Resource_List) {

    $Skip_Folders = array('.', '..', '.htaccess', 'cgi-bin', '.assets', 'serviceworker.js', 'sitemap.xml');

    $Subfolders = scandir($Path);

    for ($i = 0; $i < count($Subfolders); $i++) {

        if (in_array($Subfolders[$i], $Skip_Folders)) continue;

        if (is_dir($Path.$Subfolders[$i])) {

            $Resource_List = Get_All_Filenames($Path.$Subfolders[$i].'/', $Resource_List);
        }

        else {
            
            $URL_To_Add = str_replace($_SERVER['DOCUMENT_ROOT'], 'https://'.$_SERVER['HTTP_HOST'], $Path.$Subfolders[$i]);

            $URL_To_Add_Array = explode('/', $URL_To_Add);

            if (substr($URL_To_Add_Array[(count($URL_To_Add_Array) - 1)], 0, 5) === 'index') {
            
                $URL_To_Add_Array[(count($URL_To_Add_Array) - 1)] = '';
            }

            $URL_To_Add = implode('/', $URL_To_Add_Array);

            $Resource_List[] = $URL_To_Add;    
        }
    }

    return $Resource_List;
}


$Complete_Resource_List = Get_All_Filenames($Path, $Resource_List);
$Complete_Resource_List = array_unique($Complete_Resource_List);
sort($Complete_Resource_List);



  //====================//
 // CREATE XML SITEMAP //
//====================//

$Ashiva_XML_Sitemap = '';
$Ashiva_XML_Sitemap .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$Ashiva_XML_Sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n\n";

for ($i = 0; $i < count($Complete_Resource_List); $i++) {

    $Ashiva_XML_Sitemap .= '    <url><loc>'.$Complete_Resource_List[$i].'</loc></url>'."\n";
}

$Ashiva_XML_Sitemap .= "\n".'</urlset>';


$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/sitemap.xml', 'w');
fwrite($fp, $Ashiva_XML_Sitemap);
fclose($fp);


  //====================//
 // UPDATE XML SITEMAP //
//====================//

if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/.assets/system/sitemaps/xml/archive')) {
    mkdir($_SERVER['DOCUMENT_ROOT'].'/.assets/system/sitemaps/xml/archive', 0777);
}

unlink($_SERVER['DOCUMENT_ROOT']."/.assets/system/sitemaps/xml/archive/sitemap.xml");
copy($_SERVER['DOCUMENT_ROOT']."/sitemap.xml", $_SERVER['DOCUMENT_ROOT']."/.assets/system/sitemaps/xml/archive/sitemap.xml");
copy($_SERVER['DOCUMENT_ROOT']."/sitemap.xml", $_SERVER['DOCUMENT_ROOT']."/.assets/system/sitemaps/xml/archive/sitemap_".$Time_of_Generation.".xml");
$Ashiva_XML_Sitemap = '';

?>

<?php

  //*****************//
 // ERROR REPORTING //
//*****************//

error_reporting(E_ALL);
ini_set('display_errors', 1);



  //*************************//
 // SET UP GLOBAL VARIABLES //
//*************************//

$Time_of_Generation = date("Y-m-d_H-i-s");
$Path = $_SERVER['DOCUMENT_ROOT'].'/.assets/content/pages/';
$Resource_List =  array();



  //*******************//
 // GET ALL FILENAMES //
//*******************//

function Get_All_Filenames($Path, $Resource_List) {

  $Skip_Folders = array('.', '..');

  $Subfolders = scandir($Path);

  for ($i = 0; $i < count($Subfolders); $i++) {

    if (in_array($Subfolders[$i], $Skip_Folders)) continue;

    if (is_dir($Path.$Subfolders[$i])) {

      $Resource_List = Get_All_Filenames($Path.$Subfolders[$i].'/', $Resource_List);
    }

    else {

      if ($Subfolders[$i] !== 'index.php') continue;
    
      if ((in_array('index.php', $Subfolders)) && (in_array('page.json', $Subfolders))) {

        $URL_To_Add = str_replace($_SERVER['DOCUMENT_ROOT'].'/.assets/content/pages', 'https://'.$_SERVER['HTTP_HOST'], $Path.$Subfolders[$i]);
      
        // CUSTOM
        $Language_Homepages = ['scotia-beauty-homepage/', 'scotia-beauty-startseite/'];
        $URL_To_Add = str_replace($Language_Homepages, '', $URL_To_Add);


        $URL_To_Add = urlencode($URL_To_Add);
        $URL_To_Add = str_replace('%3A', ':', $URL_To_Add);
        $URL_To_Add = str_replace(['%2F', '/index.php'], '/', $URL_To_Add);

        $Resource_List[] = $URL_To_Add;
      }   
    }
  }

  return $Resource_List;
}


$Complete_Resource_List = Get_All_Filenames($Path, $Resource_List);
$Complete_Resource_List = array_unique($Complete_Resource_List);
sort($Complete_Resource_List);



  //********************//
 // CREATE XML SITEMAP //
//********************//

$Ashiva_XML_Sitemap = '';
$Ashiva_XML_Sitemap .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$Ashiva_XML_Sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'."\n";
$Ashiva_XML_Sitemap .= '  xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n\n";

for ($i = 0; $i < count($Complete_Resource_List); $i++) {

  $Resource_Location = $Complete_Resource_List[$i];
  $Resource_Path = urldecode(str_replace('https://'.$_SERVER['HTTP_HOST'], $_SERVER['DOCUMENT_ROOT'].'/.assets/content/pages', $Resource_Location));
  $Resource_Stem = urldecode(str_replace('.assets/content/pages/', '', $Resource_Path).'index.php');

  $Resource_Page_Manifest = json_decode(file_get_contents($Resource_Path.'page.json'), TRUE);
  
  // IF ROBOTS DIRECTIVES INCLUDE NOINDEX, CONTINUE
  $Resource_Robots_Directives = $Resource_Page_Manifest['Document_Overview']['Document_Information']['Robots'];
  if (($Resource_Robots_Directives[0] === TRUE) && (in_array('noindex', $Resource_Robots_Directives[1]))) continue;

  // LANGUAGE ALTERNATIVES
  $Resource_Language_Alternatives_Array = $Resource_Page_Manifest['Document_Overview']['Document_Information']['Document_Translations'];

  $Resource_Language_Alternatives = FALSE;

  if ($Resource_Language_Alternatives_Array[0] === TRUE) {

    $Resource_Language_Alternatives = '';

    foreach ($Resource_Language_Alternatives_Array[1] as $Language => $Link_Array) {

      if ($Link_Array[0] === TRUE) {

        $Resource_Language_Alternatives .= '    <xhtml:link rel="alternate" ';
        $Resource_Language_Alternatives .= 'hreflang="'.$Language.'" ';
        $Resource_Language_Alternatives .= 'href="'.$Link_Array[1].'" ';
        $Resource_Language_Alternatives .= '/>'."\n";
      }
    }
  }

  $Resource_Page_Stem_Updated = filemtime($Resource_Stem);
  $Resource_Page_Content_Updated = filemtime($Resource_Path.'index.php');
  $Resource_Page_Manifest_Updated = filemtime($Resource_Path.'page.json');
  $Resource_Last_Modified = date('c', max($Resource_Page_Stem_Updated, $Resource_Page_Content_Updated, $Resource_Page_Manifest_Updated));

  $Resource_Change_Frequency = 'monthly';
  $Resource_Priority = '0.5';

  $Ashiva_XML_Sitemap .= '  <url>'."\n";
  $Ashiva_XML_Sitemap .= '    <loc>'.$Resource_Location.'</loc>'."\n";
  $Ashiva_XML_Sitemap .= '    <lastmod>'.$Resource_Last_Modified.'</lastmod>'."\n";
  $Ashiva_XML_Sitemap .= '    <changefreq>'.$Resource_Change_Frequency.'</changefreq>'."\n";
  $Ashiva_XML_Sitemap .= '    <priority>'.$Resource_Priority.'</priority>'."\n";

  if ($Resource_Language_Alternatives !== FALSE) {$Ashiva_XML_Sitemap .= $Resource_Language_Alternatives;}

  $Ashiva_XML_Sitemap .= '  </url>'."\n\n";
}

$Ashiva_XML_Sitemap .= '</urlset>';


  //********************//
 // UPDATE XML SITEMAP //
//********************//

$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/sitemap.xml', 'w');
fwrite($fp, $Ashiva_XML_Sitemap);
fclose($fp);


  //*****************************//
 // PRINT XML SITEMAP ON SCREEN //
//*****************************//

echo '<h1>Sitemap Generated ('.$Time_of_Generation.')</h1>';
echo '<pre>'.htmlspecialchars(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/sitemap.xml')).'</pre>';


  //****************************//
 // UPDATE XML SITEMAP ARCHIVE //
//****************************//

if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/archive')) {
  mkdir($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/archive', 0777);
}

copy($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/sitemap.xml', $_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/archive/sitemap_'.$Time_of_Generation.'.xml');

$XML_Sitemap_Archive = array_reverse(scandir($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/archive/'));

for ($i = 0; $i < count($XML_Sitemap_Archive); $i++) {

  if (in_array($XML_Sitemap_Archive[$i], ['.', '..'])) continue;
  
  if ($i < 12) continue;

  unlink($_SERVER['DOCUMENT_ROOT'].'/.assets/content/sitemaps/xml/documents/archive/'.$XML_Sitemap_Archive[$i]);
}

?>

<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");?>
<?
CModule::IncludeModule("iblock"); 
use Bitrix\Iblock\InheritedProperty;


global $USER;
if (!$USER->IsAdmin()) {
	return die;
}


$section_id = 0;
if (isset($_REQUEST['PDF_SECTION_ID'])) {
	$section_id = (int)$_REQUEST['PDF_SECTION_ID'];
}

$arCountries = array();
$arSections = array();
$arElements = array();


$IBLOCK_ID = 3;
$COUNTRY_IBLOCK_ID = 26;

$arFilter = Array('IBLOCK_ID' => $COUNTRY_IBLOCK_ID);
$res = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter, false, Array("nPageSize" => 9999), array('ID', 'NAME', 'DETAIL_PICTURE'));
while ($ob = $res->GetNextElement()) {
	$arFields = $ob->GetFields();
	$arCountries[$arFields['ID']] = $arFields;
}



$arFilter = Array('IBLOCK_ID' => $IBLOCK_ID, 'GLOBAL_ACTIVE' => 'Y');
if ($section_id > 0) {
	$arFilter['ID'] = $section_id;
}

$db_list = CIBlockSection::GetList(Array("NAME" => "ASC"), $arFilter, true, array('ID', 'NAME', 'CODE', 'DESCRIPTION'), array('nPageSize' => 9999));

while ($ar_result = $db_list->GetNext()) {
	$arSections[] = $ar_result;
}


$arSelect = Array("ID", "IBLOCK_ID", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL", "PROPERTY_*");
$arFilter = Array("IBLOCK_ID" => $IBLOCK_ID, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
if ($section_id > 0) {
	$arFilter['SECTION_ID'] = $section_id;
	$arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
}

$res = CIBlockElement::GetList(Array('PROPERTY_NUMBER_OF_UNITS' => 'DESC', "SORT" => "ASC", 'ID' => 'DESC'), $arFilter, false, Array("nPageSize" => 9999), $arSelect);
while ($ob = $res->GetNextElement()) { 
	$arFields = $ob->GetFields();
	$arProps = $ob->GetProperties();
	
	$arFields['NUMBER_OF_UNITS'] = $arProps['NUMBER_OF_UNITS']['VALUE'];
	$arFields['COUNTRY'] = '';
	
	
	if ((int)$arProps['FLAG']['VALUE'] > 0) {
		$arFields['COUNTRY'] = $arCountries[$arProps['FLAG']['VALUE']]['NAME'];
	}
	
	
	$rsSections = CIBlockElement::GetElementGroups($arFields['ID']);
	while ($arSection = $rsSections->Fetch())  {
		$arFields['SECTIONS'][] = $arSection['ID'];
	}
	
	
	
	$arElements[] = $arFields;
}




// Include the main TCPDF library (search for installation path).
require_once($_SERVER["DOCUMENT_ROOT"].'/tcpdf/tcpdf.php');


// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {
   public function Header() {
        $image_file = 'http://www.tbs-semi.ru/image/logo-tbx-40px.png';
        $this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
	
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('OpenSans', '', 8);
        $this->Cell(0, 10, 'Страница '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('TBS');
$pdf->SetTitle('TBS Catalog');
$pdf->SetSubject('TBS Catalog');
$pdf->SetKeywords('TBS, PDF, Catalog');

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);



// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$pdf->setFontSubsetting(true);

$fontname = TCPDF_FONTS::addTTFfont($_SERVER["DOCUMENT_ROOT"].'/tcpdf/fonts/OpenSans-Regular.ttf', 'TrueTypeUnicode', '', 32);
$pdf->SetFont($fontname, '', 9);

$fontname = TCPDF_FONTS::addTTFfont($_SERVER["DOCUMENT_ROOT"].'/tcpdf/fonts/OpenSans-Bold.ttf', 'TrueTypeUnicode', '', 32);
$pdf->SetFont($fontname, '', 9);

$fontname = TCPDF_FONTS::addTTFfont($_SERVER["DOCUMENT_ROOT"].'/tcpdf/fonts/OpenSans-Italic.ttf', 'TrueTypeUnicode', '', 32);
$pdf->SetFont($fontname, '', 9);



$page_count = 0;


// define some HTML content with style
$css = <<<EOF
<style>
	* {
		font-family: 'Open Sans', sans-serif;
	}

    h1 {
        text-align: center;
		border-bottom: 1px solid #C6C6C6;
		margin-left: 40px;
		margin-bottom: 8px;
		font-family: 'Open Sans', sans-serif;
		font-size: 20px;
		padding: 0px 0px 15px 0px;
    }
	
    p.section-description {
        color: #222;
        font-family: 'Open Sans', sans-serif;
        font-size: 16px;
		line-height: 1.4;
		background-color: #f0f9e6;
    }
	
	p.product-name {
		font-size: 13px;
	}
	
	p.product-descr {
		font-size: 11px;
	}
	
	p.product-country {
		font-size: 11px;
	}
	
	p.product-count {
		font-size: 11px;
	}
</style>
EOF;




$html_category = '';

foreach ($arSections as $section) {
	$section['~DESCRIPTION'] = strip_tags(trim($section['~DESCRIPTION']));
	
	
	$html_category = <<<EOF
<h1 class="title">{$section['NAME']}</h1>
<p class="section-description">
<table cellpadding="20" cellspacing="0" border="0">
 <tr nobr="true"><td>{$section['~DESCRIPTION']}</td></tr>
</table>
</p>
EOF;


	foreach ($arElements as $element) {
		if (!in_array($section['ID'], $element['SECTIONS'])) {
			continue;
		}
		
		$file = CFile::ResizeImageGet($element['PREVIEW_PICTURE'], array('width' => 200, 'height' => 200), BX_RESIZE_IMAGE_PROPORTIONAL, true);                
		
		
		$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($IBLOCK_ID, $element['ID']); 
		$SEO = $ipropValues->getValues();
		
		$coutry = '';
		$count = '';
		
		if (!empty($element['COUNTRY'])) {
			$coutry = '<p class="product-country"><b>Страна производитель:</b> '.$element['COUNTRY'].'</p>';
		}
		
		if (!empty($element['NUMBER_OF_UNITS']) && $element['NUMBER_OF_UNITS'] > 0) {
			$count = '<p class="product-count"><b>Установлено в России:</b> '.$element['NUMBER_OF_UNITS'].'</p>';
		}
		
		$html_category .= <<<EOF
<table cellpadding="4" cellspacing="6" style="border-bottom: 1px solid #eee">
 <tr nobr="true">
  <td width="200" align="center">
	<p><a href="{$element['DETAIL_PAGE_URL']}" target="_blank"><img src="{$file['src']}" width="{$file['width']}" height="{$file['height']}" /></a></p>
  </td>
  <td width="400" rowspan="6" class="second">
	<p class="product-name"><b>{$SEO['ELEMENT_META_TITLE']}</b></p><br>
	<p class="product-descr">{$element['PREVIEW_TEXT']}</p>
	{$coutry}
	{$count}
  </td>
 </tr>
</table>
EOF;

	}



	$pdf->AddPage();
	$page_count++;
	$pdf->SetFont('OpenSans', '', 12);
	$pdf->setCellHeightRatio(1.8);
	$pdf->Bookmark($section['NAME'], 0, 0, '', '', array(51, 122, 183));
	
	$pdf->writeHTML($css.$html_category, true, false, true, false, '');
}



// оглавление только если не указан конкретный раздел
if (!$section_id) {
	// add a new page for TOC
	$pdf->addTOCPage();
	$pdf->SetFont('OpenSans', 'B', 14);
	$pdf->MultiCell(0, 0, 'ОГЛАВЛЕНИЕ', 0, 'C', 0, 1, '', '', true, 0);
	$pdf->Ln();


	$pdf->SetFont('OpenSans', '', 12);
	// add a simple Table Of Content at first page
	// (check the example n. 59 for the HTML version)
	$pdf->addTOC(1, 'OpenSans', '.', 'INDEX', '', array(34, 34, 34));

	// end of TOC page
	$pdf->endTOCPage();
}

 
$pdf->Output('example_051.pdf', 'I');
?>
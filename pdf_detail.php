<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");?>
<?
CModule::IncludeModule("iblock"); 
use Bitrix\Iblock\InheritedProperty;


global $USER;
if (!$USER->IsAdmin()) {
	return die;
}


$element_id = 0;
if (isset($_REQUEST['ID'])) {
	$element_id = (int)$_REQUEST['ID'];
}

$arCountries = array();
$arElements = array();


$IBLOCK_ID = 3;
$COUNTRY_IBLOCK_ID = 26;

$arFilter = Array('IBLOCK_ID' => $COUNTRY_IBLOCK_ID);
$res = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter, false, Array("nPageSize" => 9999), array('ID', 'NAME', 'DETAIL_PICTURE'));
while ($ob = $res->GetNextElement()) {
	$arFields = $ob->GetFields();
	$arCountries[$arFields['ID']] = $arFields;
}


$arSelect = Array("ID", "IBLOCK_ID", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL", "PROPERTY_*");
$arFilter = Array("IBLOCK_ID" => $IBLOCK_ID, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", 'ID' => $element_id);

$res = CIBlockElement::GetList(Array('PROPERTY_NUMBER_OF_UNITS' => 'DESC', "SORT" => "ASC", 'ID' => 'DESC'), $arFilter, false, Array("nPageSize" => 9999), $arSelect);
while ($ob = $res->GetNextElement()) {
	$arFields = $ob->GetFields();
	$arProps = $ob->GetProperties();
	
	
	$element['PREVIEW_TEXT'] = trim($element['PREVIEW_TEXT']);
	
	$arFields['NUMBER_OF_UNITS'] = $arProps['NUMBER_OF_UNITS']['VALUE'];
	$arFields['COUNTRY'] = '';
	
	
	if ((int)$arProps['FLAG']['VALUE'] > 0) {
		$arFields['COUNTRY'] = $arCountries[$arProps['FLAG']['VALUE']]['NAME'];
	}
	
	$arFields['MAJOR_OPPORTUNITIES'] = $arProps['MAJOR_OPPORTUNITIES']['VALUE'];
	$arFields['ENGINEERING_SERVICE'] = array();
	
	
	if (count($arProps['ENGINEERING_SERVICE']['VALUE'])) {
		$arFilterService = Array("IBLOCK_ID" => 4, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", 'ID' => $arProps['ENGINEERING_SERVICE']['VALUE']);
		$resService = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilterService, false, Array("nPageSize" => 9999), Array("ID", "NAME", "DETAIL_PAGE_URL"));
		
		while ($obService = $resService->GetNextElement()) {
			$arFieldsService = $obService->GetFields();
			
			$arFields['ENGINEERING_SERVICE'][] = $arFieldsService;
		}
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

$pdf->setJPEGQuality(100);

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
		margin: 0;
		padding: 0;
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
	
	
	
	
	.elementSEOTitle {
		text-align: center;
		border-bottom: 1px solid #C6C6C6;
		margin-left: 40px;
		margin-bottom: 15px;
		font-family: 'Open Sans', sans-serif;
		font-weight: bold;
		font-size: 16px;
		padding: 0px 0px 15px 0px;
	}
	
	
	.catalog_text p {
		font-family: 'Open Sans', sans-serif;
		font-size: 11px;
	}
	
	.catalog_text_ogl {
		font-family: 'Open Sans', sans-serif;
		font-weight: bold;
		font-size: 13px;
		padding-top: 40px;
	}

	.catalog_text span {
		font-family: 'Open Sans', sans-serif;
		font-weight: bold;
		font-size: 11px;
	}
	
	
	.catalog_text div.ul li {
		list-style-type: none;
		background-image: url(http://www.tbs-semi.ru/image/mark-icon.png);
		background-repeat: no-repeat;
		background-position: 0px 50%;
		padding: 5px 0px 5px 20px;
		font-size: 11px;
	}
	
	.catalog_text a {
		background-image: url(http://www.tbs-semi.ru/image/service-mark-icon.png);
		background-repeat: no-repeat;
		background-position: 0px 50%;
		padding: 5px 0px 5px 20px;
		margin-right: 20px;
		color: #000;
		display: inline-block;
		font-size: 11px;
	}
	
</style>
EOF;




$html_category = '';

	foreach ($arElements as $element) {
		$file = CFile::ResizeImageGet($element['PREVIEW_PICTURE'], array('width' => 600, 'height' => 600), BX_RESIZE_IMAGE_PROPORTIONAL, true);                
		
		foreach ($arCountries as $item_country) {
			if ($item_country['NAME'] == $element['COUNTRY']) {
				$flag = CFile::ResizeImageGet($item_country['DETAIL_PICTURE'], array('width' => 40, 'height' => 25), BX_RESIZE_IMAGE_PROPORTIONAL, true);
			}
		}
		
		
		$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($IBLOCK_ID, $element['ID']); 
		$SEO = $ipropValues->getValues();
		/*echo '<pre>', print_r($SEO), '</pre>';
		die;
		*/
		$coutry = '';
		
		
		if (!empty($element['COUNTRY'])) {
			$coutry = '<p class="product-country"><b>Страна производитель:</b> '.$element['COUNTRY'].'</p>';
		}
		
		
		
		$major = '';
		if (count($arFields['MAJOR_OPPORTUNITIES'])) {
			$major = '<p class="catalog_text_ogl"><span>Основные возможности:</span></p>';
			$major .= '<div class="ul" style="list-style-type: none; list-style-position:inside;">';
			
			foreach ($arFields['MAJOR_OPPORTUNITIES'] as $item) {
				$major .= '<li><img class="icon-service" src="http://www.tbs-semi.ru/image/mark-icon.png" width="10px" height="10px" /> '.$item.'</li>';
			}
			
			$major .= '</div>';
		}
		
		
		$service = '';
		if (count($arFields['ENGINEERING_SERVICE'])) {
			$service = '<p class="catalog_text_ogl"><span>Инжиниринговые услуги:</span></p>';
			$service .= '<div class="ul" style="list-style-type: none;">';
			
			foreach ($arFields['ENGINEERING_SERVICE'] as $item) {
				$service .= '<li><img class="icon-service" src="http://www.tbs-semi.ru/image/service-mark-icon.png" width="10px" height="10px" /> <a href="'.$item['DETAIL_PAGE_URL'].'">'.$item['NAME'].'</a></li>';
			}
			
			$service .= '</div>';
		}
		
		
		$html_category .= <<<EOF
<h1 class="elementSEOTitle">{$SEO['ELEMENT_PAGE_TITLE']}</h1>
<table cellpadding="4" cellspacing="6">
 <tr nobr="true">
  <td width="280" align="left">
	<p style="text-align: right;">
		<a href="{$element['DETAIL_PAGE_URL']}" target="_blank"><img src="{$file['src']}" /></a>
		
		<img style="position: absolute; top: -100px;" src="{$flag['src']}" width="{$flag['width']}" height="{$flag['height']}" />
	</p>
  </td>
  <td width="340" align="left" rowspan="6" class="second">
	<div class="catalog_text">
		<p>{$element['PREVIEW_TEXT']}</p>
	<p>{$coutry}</p>
	{$major}{$service}</div>
  </td>
 </tr>
</table>
EOF;

	}



	$pdf->AddPage();
	$page_count++;
	$pdf->SetFont('OpenSans', '', 12);
	$pdf->setCellHeightRatio(1.2);
	
	$pdf->writeHTML($css.$html_category, true, false, true, false, '');


 
$pdf->Output('example_051.pdf', 'I');
?>
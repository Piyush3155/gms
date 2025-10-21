<?php
class FPDF {
    var $page;
    var $n;
    var $offsets;
    var $buffer;
    var $pages;
    var $state;
    var $compress;
    var $k;
    var $DefOrientation;
    var $CurOrientation;
    var $StdPageSizes;
    var $DefPageSize;
    var $CurPageSize;
    var $PageSizes;
    var $wPt, $hPt;
    var $w, $h;
    var $lMargin;
    var $tMargin;
    var $rMargin;
    var $bMargin;
    var $cMargin;
    var $x, $y;
    var $lasth;
    var $LineWidth;
    var $fontpath;
    var $FontFamily;
    var $FontStyle;
    var $underline;
    var $CurrentFont;
    var $FontSizePt;
    var $FontSize;
    var $DrawColor;
    var $FillColor;
    var $TextColor;
    var $ColorFlag;
    var $ws;
    var $images;
    var $PageLinks;
    var $links;
    var $AutoPageBreak;
    var $PageBreakTrigger;
    var $InHeader;
    var $InFooter;
    var $ZoomMode;
    var $LayoutMode;
    var $title;
    var $subject;
    var $author;
    var $keywords;
    var $creator;
    var $fonts;
    var $FontFiles;
    var $diffs;
    var $CoreFonts;
    var $PageInfo;

    function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->buffer = '';
        $this->_dochecks();
        $this->page = 0;
        $this->n = 2;
        $this->offsets = array();
        $this->pages = array();
        $this->PageSizes = array();
        $this->state = 0;
        $this->fonts = array();
        $this->FontFiles = array();
        $this->diffs = array();
        $this->images = array();
        $this->links = array();
        $this->PageLinks = array();
        $this->InHeader = false;
        $this->InFooter = false;
        $this->lasth = 0;
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->ws = 0;
        if(defined('FPDF_FONTPATH')) $this->fontpath = FPDF_FONTPATH;
        else $this->fontpath = dirname(__FILE__).'/font/';
        $this->CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
        $this->k = 72/25.4;
        $this->DefOrientation = $orientation;
        $this->CurOrientation = $orientation;
        $this->_stdPageSizes();
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        if($orientation=='P') {
            $this->w = $size[0]/$this->k;
            $this->h = $size[1]/$this->k;
        } else {
            $this->w = $size[1]/$this->k;
            $this->h = $size[0]/$this->k;
        }
        $this->wPt = $size[0];
        $this->hPt = $size[1];
        $this->lMargin = 28.35/$this->k;
        $this->tMargin = 28.35/$this->k;
        $this->rMargin = 28.35/$this->k;
        $this->bMargin = 28.35/$this->k;
        $this->cMargin = 0;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->LineWidth = .567/$this->k;
        $this->SetAutoPageBreak(true, 2*28.35/$this->k);
        $this->SetDisplayMode('default');
        $this->SetCompression(true);
    }

    function SetMargins($left, $top, $right=null) {
        $this->lMargin = $left/$this->k;
        $this->tMargin = $top/$this->k;
        if($right===null) $right = $left;
        $this->rMargin = $right/$this->k;
    }

    function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin/$this->k;
        $this->PageBreakTrigger = $this->h - $this->bMargin;
    }

    function AddPage($orientation='', $size='') {
        if($this->state==0) $this->Open();
        $family = $this->FontFamily;
        $style = $this->FontStyle.($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        if($this->page>0) {
            $this->_endpage();
        }
        $this->_beginpage($orientation,$size);
        $this->_out('2 J');
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w',$lw*$this->k));
        if($family) $this->SetFont($family,$style,$fontsize);
        $this->DrawColor = $dc;
        if($dc!='0 G') $this->_out($dc);
        $this->FillColor = $fc;
        if($fc!='0 g') $this->_out($fc);
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        if($this->page>1) {
            if($this->InHeader) $this->Header();
            if($this->InFooter) $this->Footer();
        }
        $this->_out('1 0 0 1 0 0 cm');
    }

    function SetFont($family, $style='', $size=0) {
        $family = strtolower($family);
        if($family=='') $family = $this->FontFamily;
        if($family=='arial') $family = 'helvetica';
        elseif($family=='symbol' || $family=='zapfdingbats') $style = '';
        $style = strtoupper($style);
        if(strpos($style,'U')!==false) {
            $this->underline = true;
            $style = str_replace('U','',$style);
        } else $this->underline = false;
        if($style=='IB') $style = 'BI';
        if($size==0) $size = $this->FontSizePt;
        if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size) return;
        $fontkey = $family.$style;
        if(!isset($this->fonts[$fontkey])) {
            if($family=='helvetica') $this->AddFont($family,$style);
            else $this->Error('Undefined font: '.$family.' '.$style);
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        if($this->page>0) $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $k = $this->k;
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
            $x = $this->x;
            $ws = $this->ws;
            if($ws>0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation,$this->CurPageSize);
            $this->x = $x;
            if($ws>0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw',$ws*$k));
            }
        }
        if($w==0) $w = $this->w - $this->rMargin - $this->x;
        $s = '';
        if($fill || $border==1) {
            if($fill) $op = ($border==1) ? 'B' : 'f';
            else $op = 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
        }
        if(is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if(strpos($border,'L')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
            if(strpos($border,'T')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
            if(strpos($border,'R')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
            if(strpos($border,'B')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
        }
        if($txt!=='') {
            if($align=='R') $dx = $w - $this->GetStringWidth($txt) - $this->cMargin;
            elseif($align=='C') $dx = ($w - $this->GetStringWidth($txt))/2;
            else $dx = $this->cMargin;
            if($this->ColorFlag) $s .= 'q '.$this->TextColor.' ';
            $txt2 = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
            if($this->underline) $s .= ' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
            if($this->ColorFlag) $s .= ' Q';
            if($link) $this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
        }
        if($s) $this->_out($s);
        $this->lasth = $h;
        if($ln>0) {
            $this->y += $h;
            if($ln==1) $this->x = $this->lMargin;
        } else $this->x += $w;
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        if($this->CurrentFont === null) return; // Safety check
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $b = 0;
        if($border) {
            if($border==1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if(strpos($border,'L')!==false) $b2 .= 'L';
                if(strpos($border,'R')!==false) $b2 .= 'R';
                $b = (strpos($border,'T')!==false) ? $b2.'T' : $b2;
            }
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") {
                if($this->ws>0) {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if($border && $nl==2) $b = $b2;
                continue;
            }
            if($c==' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += isset($cw[ord($c)]) ? $cw[ord($c)] : 600;
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j) $i++;
                    if($this->ws>0) {
                        $this->ws = 0;
                        $this->_out('0 Tw');
                    }
                    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                } else {
                    if($align=='J') {
                        $this->ws = ($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                        $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
                    }
                    $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                    $i = $sep+1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if($border && $nl==2) $b = $b2;
            } else $i++;
        }
        if($this->ws>0) {
            $this->ws = 0;
            $this->_out('0 Tw');
        }
        if($border && strpos($border,'B')!==false) $b .= 'B';
        $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
        $this->x = $this->lMargin;
    }

    function Output($name='', $dest='') {
        if($this->state<3) $this->Close();
        $dest = strtoupper($dest);
        if($dest=='') {
            if($name=='') {
                $name = 'doc.pdf';
                $dest = 'I';
            } else $dest = 'F';
        }
        switch($dest) {
            case 'I':
                if(ob_get_length()) $this->Error('Some data has already been output, can\'t send PDF file');
                if(php_sapi_name()!='cli') {
                    header('Content-Type: application/pdf');
                    if(headers_sent()) $this->Error('Some data has already been output, can\'t send PDF file');
                    header('Content-Length: '.strlen($this->buffer));
                    header('Content-Disposition: inline; filename="'.$name.'"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                break;
            case 'D':
                if(ob_get_length()) $this->Error('Some data has already been output, can\'t send PDF file');
                header('Content-Type: application/pdf');
                if(headers_sent()) $this->Error('Some data has already been output, can\'t send PDF file');
                header('Content-Length: '.strlen($this->buffer));
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
                break;
            case 'F':
                $f = fopen($name,'wb');
                if(!$f) $this->Error('Unable to create output file: '.$name);
                fwrite($f,$this->buffer,strlen($this->buffer));
                fclose($f);
                break;
            case 'S':
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
    }

    function Close() {
        if($this->state==3) return;
        if($this->page==0) $this->AddPage();
        $this->_endpage();
        $this->_putpages();
        $this->_putresources();
        $this->_putinfo();
        $this->_putcatalog();
        $this->_out('xref');
        $this->_out('0 '.($this->n+1));
        $this->_out('0000000000 65535 f ');
        for($i=1;$i<=$this->n;$i++) $this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
        $this->_out('trailer');
        $this->_out('<<');
        $this->_out('/Size '.($this->n+1));
        $this->_out('/Root '.$this->n.' 0 R');
        $this->_out('/Info '.($this->n-1).' 0 R');
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out(strlen($this->buffer));
        $this->_out('%%EOF');
        $this->state = 3;
    }

    // Private methods
    function _dochecks() {
        $this->_out('%PDF-1.7');
    }
    function _stdPageSizes() {
        $this->StdPageSizes = array(
            'a3' => array(841.89, 1190.55),
            'a4' => array(595.28, 841.89),
            'a5' => array(420.94, 595.28),
            'letter' => array(612, 792),
            'legal' => array(612, 1008)
        );
    }
    function _getpagesize($size) {
        if(is_string($size)) {
            $size = strtolower($size);
            if(!isset($this->StdPageSizes[$size])) $this->Error('Unknown page size: '.$size);
            $a = $this->StdPageSizes[$size];
            return array($a[0], $a[1]);
        } else {
            if($size[0]>$size[1]) return array($size[1], $size[0]);
            else return $size;
        }
    }
    function _beginpage($orientation, $size) {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if($orientation=='') $orientation = $this->DefOrientation;
        if($size=='') $size = $this->DefPageSize;
        else $size = $this->_getpagesize($size);
        if($this->CurPageSize === null || $orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if($orientation=='P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w*$this->k;
            $this->hPt = $this->h*$this->k;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        if($this->CurPageSize === null || $orientation!=$this->DefOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) $this->PageSizes[$this->page] = array($this->wPt, $this->hPt);
    }
    function _endpage() {
        $this->state = 1;
    }
    function _out($s) {
        if($this->state==2) $this->pages[$this->page] .= $s."\n";
        else $this->buffer .= $s."\n";
    }
    function _putpages() {
        $nb = $this->page;
        for($n=1;$n<=$nb;$n++) {
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if(isset($this->PageSizes[$n])) $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageSizes[$n][0],$this->PageSizes[$n][1]));
            $this->_out('/Resources 2 0 R');
            if(isset($this->PageLinks[$n])) {
                $annots = '/Annots [';
                foreach($this->PageLinks[$n] as $pl) {
                    $rect = sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
                    $annots .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
                    if(is_string($pl[4])) $annots .= '/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
                    else {
                        $l = $this->links[$pl[4]];
                        $h = isset($l[0]) ? $l[0] : $this->hPt;
                        $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',$this->PageInfo[$l[1]]['n'],$h);
                    }
                }
                $this->_out($annots.']');
            }
            $this->_out('/Contents '.($this->n+1).' 0 R>>');
            $this->_out('endobj');
            $this->_newobj();
            $this->_out('<<');
            $this->_putstreamobject($this->pages[$n]);
            $this->_out('endobj');
        }
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<<');
        $this->_out('/Type /Pages');
        $this->_out('/Kids ['.implode(' ', range(3, 3 + ($nb-1)*2, 2)).']');
        $this->_out('/Count '.$nb);
        $this->_out('>>');
        $this->_out('endobj');
    }
    function _putresources() {
        $this->_putfonts();
        $this->_putimages();
        $this->offsets[2] = strlen($this->buffer);
        $this->_out('2 0 obj');
        $this->_out('<<');
        $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach($this->fonts as $font) $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
        $this->_out('>>');
        if(count($this->images)) {
            $this->_out('/XObject <<');
            foreach($this->images as $image) $this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
            $this->_out('>>');
        }
        $this->_out('>>');
        $this->_out('endobj');
    }
    function _putfonts() {
        foreach($this->fonts as $k => $font) {
            $this->fonts[$k]['n'] = $this->n+1;
            $this->_newobj();
            $this->_out('<</Type /Font');
            $this->_out('/BaseFont /'.$font['name']);
            $this->_out('/Subtype /Type1');
            if($font['name']!='Symbol' && $font['name']!='ZapfDingbats') $this->_out('/Encoding /WinAnsiEncoding');
            $this->_out('>>');
            $this->_out('endobj');
        }
    }
    function _putimages() {}
    function _putinfo() {
        $this->_newobj();
        $this->_out('<<');
        $this->_out('/Producer '.$this->_textstring('FPDF '.FPDF_VERSION));
        if(!empty($this->title)) $this->_out('/Title '.$this->_textstring($this->title));
        if(!empty($this->subject)) $this->_out('/Subject '.$this->_textstring($this->subject));
        if(!empty($this->author)) $this->_out('/Author '.$this->_textstring($this->author));
        if(!empty($this->keywords)) $this->_out('/Keywords '.$this->_textstring($this->keywords));
        if(!empty($this->creator)) $this->_out('/Creator '.$this->_textstring($this->creator));
        $this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
        $this->_out('>>');
    }
    function _putcatalog() {
        $this->_newobj();
        $this->_out('<<');
        $this->_out('/Type /Catalog');
        $this->_out('/Pages 1 0 R');
        if($this->ZoomMode=='fullpage') $this->_out('/OpenAction [3 0 R /Fit]');
        elseif($this->ZoomMode=='fullwidth') $this->_out('/OpenAction [3 0 R /FitH null]');
        elseif($this->ZoomMode=='real') $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
        elseif(!is_string($this->ZoomMode)) $this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
        if($this->LayoutMode=='single') $this->_out('/PageLayout /SinglePage');
        elseif($this->LayoutMode=='continuous') $this->_out('/PageLayout /OneColumn');
        elseif($this->LayoutMode=='two') $this->_out('/PageLayout /TwoColumnLeft');
        $this->_out('>>');
    }

    function _newobj() {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_out($this->n.' 0 obj');
    }
    function _putstreamobject($s) {
        $this->_out('<<');
        $this->_out('/Length '.strlen($s));
        $this->_out('>>');
        $this->_out('stream');
        $this->_out($s);
        $this->_out('endstream');
        $this->_out('endobj');
    }
    function _textstring($s) {
        return '(' . str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $s))) . ')';
    }
    function AddFont($family, $style='') {
        if(!is_array($this->fonts)) $this->fonts = array(); // Safety check
        $fontkey = $family.$style;
        if(isset($this->fonts[$fontkey])) return;
        $name = $family;
        if($style=='B') $name .= 'Bold';
        elseif($style=='I') $name .= 'Oblique';
        elseif($style=='BI') $name .= 'BoldOblique';
        $this->fonts[$fontkey] = array('i' => count($this->fonts)+1, 'name' => $name, 'up' => -100, 'ut' => 50, 'cw' => $this->_loadfont($name));
    }
    function _loadfont($font) {
        // Simplified font loading - in real FPDF, this would load actual font metrics
        $cw = array();
        for($i=0;$i<=255;$i++) $cw[$i] = 600; // Default width
        return $cw;
    }
    function GetStringWidth($s) {
        $s = (string)$s;
        if($this->CurrentFont === null) return 0; // Safety check
        $cw = &$this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for($i=0;$i<$l;$i++) $w += isset($cw[ord($s[$i])]) ? $cw[ord($s[$i])] : 600;
        return $w*$this->FontSize/1000;
    }
    function Error($msg) {
        die('<b>FPDF error:</b> '.$msg);
    }
    function Open() {
        $this->state = 1;
    }
    function SetDisplayMode($zoom, $layout='default') {
        $this->ZoomMode = $zoom;
        $this->LayoutMode = $layout;
    }
    function SetCompression($compress) {
        $this->compress = function_exists('gzcompress') ? $compress : false;
    }
    function SetTitle($title, $isUTF8=false) {
        $this->title = $title;
    }
    function SetAuthor($author, $isUTF8=false) {
        $this->author = $author;
    }
    function SetSubject($subject, $isUTF8=false) {
        $this->subject = $subject;
    }
    function SetKeywords($keywords, $isUTF8=false) {
        $this->keywords = $keywords;
    }
    function SetCreator($creator, $isUTF8=false) {
        $this->creator = $creator;
    }
    function _dounderline($x, $y, $txt) {
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        return sprintf('%.2F %.2F %.2F %.2F re f',$x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,$this->GetStringWidth($txt)*$this->k,-$ut/1000*$this->FontSizePt);
    }
    function Link($x, $y, $w, $h, $link) {
        $this->PageLinks[$this->page][] = array($x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link);
    }

    function Ln($h=null) {
        if($h===null) $this->x = $this->lMargin;
        else $this->y += $h;
    }

    function SetY($y) {
        $this->x = $this->lMargin;
        $this->y = $y;
    }

    function SetX($x) {
        $this->x = $x;
    }

    function Header() {
        // To be overridden
    }

    function Footer() {
        // To be overridden
    }
}

define('FPDF_VERSION','1.7');
if(!defined('FPDF_FONTPATH')) define('FPDF_FONTPATH', dirname(__FILE__).'/font/');
?>

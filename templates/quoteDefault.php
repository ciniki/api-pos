<?php
//
// Description
// ===========
// This method will produce a PDF of the Quote.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_templates_quoteDefault(&$ciniki, $tnid, $invoice_id, $tenant_details, $sapos_settings) {
    //
    // Get the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];
    
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 0;      // The height of the image and address
        public $tenant_details = array();
        public $sapos_settings = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                $image_ratio = $width/$height;
                if( count($this->header_addr) == 0 && $this->header_name == '' ) {
                    $img_width = 180;
                } else {
                    $img_width = 120;
                }
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
                        $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
                        0, $this->header_height-5, 'JPEG', '', 'L', 2, '150');
                }
            }

            //
            // Add the contact information
            //
            if( !isset($this->sapos_settings['invoice-header-contact-position']) 
                || $this->sapos_settings['invoice-header-contact-position'] != 'off' ) {
                if( isset($this->sapos_settings['invoice-header-contact-position'])
                    && $this->sapos_settings['invoice-header-contact-position'] == 'left' ) {
                    $align = 'L';
                } elseif( isset($this->sapos_settings['invoice-header-contact-position'])
                    && $this->sapos_settings['invoice-header-contact-position'] == 'right' ) {
                    $align = 'R';
                } else {
                    $align = 'C';
                }
                $this->Ln(8);
                if( $this->header_name != '' ) {
                    $this->SetFont('times', 'B', 20);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, 10, '', 0);
                    }
                    $this->Cell(180-$img_width, 10, $this->header_name, 
                        0, false, $align, 0, '', 0, false, 'M', 'M');
                    $this->Ln(5);
                }
                $this->SetFont('times', '', 10);
                if( count($this->header_addr) > 0 ) {
                    $address_lines = count($this->header_addr);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, ($address_lines*5), '', 0);
                    }
                    $this->MultiCell(180-$img_width, $address_lines, implode("\n", $this->header_addr), 
                        0, $align, 0, 0, '', '', true, 0, false, true, 0, 'M', false);
                    $this->Ln();
                }
            }

            //
            // Output the invoice details which should be at the top of each page.
            //
            $this->SetCellPadding(2);
            if( count($this->header_details) <= 6 ) {
                if( $this->header_name == '' && count($this->header_addr) == 0 ) {
                    $this->Ln($this->header_height+6);
                } elseif( $this->header_name == '' && count($this->header_addr) > 0 ) {
                    $used_space = 4 + count($this->header_addr)*5;
                    if( $used_space < 30 ) {
                        $this->Ln(30-$used_space+5);
                    } else {
                        $this->Ln(7);
                    }
                } elseif( $this->header_name != '' && count($this->header_addr) > 0 ) {
                    $used_space = 10 + count($this->header_addr)*5;
                    if( $used_space < 30 ) {
                        $this->Ln(30-$used_space+6);
                    } else {
                        $this->Ln(5);
                    }
                } elseif( $this->header_name != '' && count($this->header_addr) == 0 ) {
                    $this->Ln(25);
                }
                $this->SetFont('times', '', 10);
                $num_elements = count($this->header_details);
                if( $num_elements == 3 ) {
                    $w = array(60,60,60);
                } elseif( $num_elements == 4 ) {
                    $w = array(45,45,45,45);
                } elseif( $num_elements == 5 ) {
                    $w = array(36,36,36,36,36);
                } else {
                    $w = array(30,30,30,30,30,30);
                }
                $lh = 6;
                $this->SetFont('', 'B');
                for($i=0;$i<$num_elements;$i++) {
                    if( $this->header_details[$i]['label'] != '' ) {
                        $this->SetFillColor(224);
                        $this->Cell($w[$i], $lh, $this->header_details[$i]['label'], 1, 0, 'C', 1);
                    } else {
                        $this->SetFillColor(255);
                        $this->Cell($w[$i], $lh, '', 'T', 0, 'C', 1);
                    }
                }
                $this->Ln();
                $this->SetFillColor(255);
                $this->SetFont('');
                for($i=0;$i<$num_elements;$i++) {
                    if( $this->header_details[$i]['label'] != '' ) {
                        $this->Cell($w[$i], $lh, $this->header_details[$i]['value'], 1, 0, 'C', 1);
                    } else {
                        $this->Cell($w[$i], $lh, '', 0, 0, 'C', 1);
                    }
                }
                $this->Ln();
            }
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            if( isset($this->sapos_settings['quote-footer-message']) 
                && $this->sapos_settings['quote-footer-message'] != '' ) {
                $this->Cell(90, 10, $this->sapos_settings['quote-footer-message'],
                    0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                    0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // Center the page number if no footer message.
                $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                    0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_name = '';
    if( !isset($sapos_settings['invoice-header-contact-position'])
        || $sapos_settings['invoice-header-contact-position'] != 'off' ) {
        if( !isset($sapos_settings['invoice-header-tenant-name'])
            || $sapos_settings['invoice-header-tenant-name'] == 'yes' ) {
            $pdf->header_name = $tenant_details['name'];
            $pdf->header_height = 8;
        }
        if( !isset($sapos_settings['invoice-header-tenant-address'])
            || $sapos_settings['invoice-header-tenant-address'] == 'yes' ) {
            if( isset($tenant_details['contact.address.street1']) 
                && $tenant_details['contact.address.street1'] != '' ) {
                $pdf->header_addr[] = $tenant_details['contact.address.street1'];
            }
            if( isset($tenant_details['contact.address.street2']) 
                && $tenant_details['contact.address.street2'] != '' ) {
                $pdf->header_addr[] = $tenant_details['contact.address.street2'];
            }
            $city = '';
            if( isset($tenant_details['contact.address.city']) 
                && $tenant_details['contact.address.city'] != '' ) {
                $city .= $tenant_details['contact.address.city'];
            }
            if( isset($tenant_details['contact.address.province']) 
                && $tenant_details['contact.address.province'] != '' ) {
                $city .= ($city!='')?', ':'';
                $city .= $tenant_details['contact.address.province'];
            }
            if( isset($tenant_details['contact.address.postal']) 
                && $tenant_details['contact.address.postal'] != '' ) {
                $city .= ($city!='')?'  ':'';
                $city .= $tenant_details['contact.address.postal'];
            }
            if( $city != '' ) {
                $pdf->header_addr[] = $city;
            }
        }
        if( !isset($sapos_settings['invoice-header-tenant-phone'])
            || $sapos_settings['invoice-header-tenant-phone'] == 'yes' ) {
            if( isset($tenant_details['contact.phone.number']) 
                && $tenant_details['contact.phone.number'] != '' ) {
                $pdf->header_addr[] = 'phone: ' . $tenant_details['contact.phone.number'];
            }
            if( isset($tenant_details['contact.tollfree.number']) 
                && $tenant_details['contact.tollfree.number'] != '' ) {
                $pdf->header_addr[] = 'phone: ' . $tenant_details['contact.tollfree.number'];
            }
        }
        if( !isset($sapos_settings['invoice-header-tenant-cell'])
            || $sapos_settings['invoice-header-tenant-cell'] == 'yes' ) {
            if( isset($tenant_details['contact.cell.number']) 
                && $tenant_details['contact.cell.number'] != '' ) {
                $pdf->header_addr[] = 'cell: ' . $tenant_details['contact.cell.number'];
            }
        }
        if( (!isset($sapos_settings['invoice-header-tenant-fax'])
            || $sapos_settings['invoice-header-tenant-fax'] == 'yes')
            && isset($tenant_details['contact.fax.number']) 
            && $tenant_details['contact.fax.number'] != '' ) {
            $pdf->header_addr[] = 'fax: ' . $tenant_details['contact.fax.number'];
        }
        if( (!isset($sapos_settings['invoice-header-tenant-email'])
            || $sapos_settings['invoice-header-tenant-email'] == 'yes')
            && isset($tenant_details['contact.email.address']) 
            && $tenant_details['contact.email.address'] != '' ) {
            $pdf->header_addr[] = $tenant_details['contact.email.address'];
        }
        if( (!isset($sapos_settings['invoice-header-tenant-website'])
            || $sapos_settings['invoice-header-tenant-website'] == 'yes')
            && isset($tenant_details['contact-website-url']) 
            && $tenant_details['contact-website-url'] != '' ) {
            $pdf->header_addr[] = $tenant_details['contact-website-url'];
        }
    }
    $pdf->header_height += (count($pdf->header_addr)*5);

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    }

    //
    // Load the header image
    //
    if( isset($sapos_settings['invoice-header-image']) && $sapos_settings['invoice-header-image'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, 
            $sapos_settings['invoice-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->tenant_details = $tenant_details;
    $pdf->sapos_settings = $sapos_settings;

    //
    // Determine the header details
    //
    $pdf->header_details = array(
        array('label'=>'Quote Number', 'value'=>$invoice['invoice_number']),
        array('label'=>'Quote Date', 'value'=>$invoice['invoice_date']),
        );
    if( isset($invoice['po_number']) && $invoice['po_number'] != '' ) {
        $pdf->header_details[] = array('label'=>'PO Number', 'value'=>$invoice['po_number']);
    }
    if( isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
        $pdf->header_details[] = array('label'=>'Due Date', 'value'=>$invoice['due_date']);
    }
    $pdf->header_details[] = array('label'=>'Estimate Total', 'value'=>$invoice['balance_amount_display']);

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle('Quote #' . $invoice['invoice_number']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, $pdf->header_height+33, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);


    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->AddPage();
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Determine the billing address information
    //
    $baddr = array();
    if( isset($invoice['billing_name']) && $invoice['billing_name'] != '' ) {
        $baddr[] = $invoice['billing_name'];
    }
    if( isset($invoice['billing_address1']) && $invoice['billing_address1'] != '' ) {
        $baddr[] = $invoice['billing_address1'];
    }
    if( isset($invoice['billing_address2']) && $invoice['billing_address2'] != '' ) {
        $baddr[] = $invoice['billing_address2'];
    }
    $city = '';
    if( isset($invoice['billing_city']) && $invoice['billing_city'] != '' ) {
        $city = $invoice['billing_city'];
    }
    if( isset($invoice['billing_province']) && $invoice['billing_province'] != '' ) {
        $city .= (($city!='')?', ':'') . $invoice['billing_province'];
    }
    if( isset($invoice['billing_postal']) && $invoice['billing_postal'] != '' ) {
        $city .= (($city!='')?',  ':'') . $invoice['billing_postal'];
    }
    if( $city != '' ) {
        $baddr[] = $city;
    }
    if( isset($invoice['billing_country']) && $invoice['billing_country'] != '' ) {
        $baddr[] = $invoice['billing_country'];
    }

    //
    // Determine the shipping information
    //
    $waddr = array();
    if( ($ciniki['tenant']['modules']['ciniki.sapos']['flags']&0x020000) > 0 ) {
        if( isset($invoice['work_address1']) && $invoice['work_address1'] != '' ) {
            $waddr[] = $invoice['work_address1'];
        }
        if( isset($invoice['work_address2']) && $invoice['work_address2'] != '' ) {
            $waddr[] = $invoice['work_address2'];
        }
        $city = '';
        if( isset($invoice['work_city']) && $invoice['work_city'] != '' ) {
            $city = $invoice['work_city'];
        }
        if( isset($invoice['work_province']) && $invoice['work_province'] != '' ) {
            $city .= (($city!='')?', ':'') . $invoice['work_province'];
        }
        if( isset($invoice['work_postal']) && $invoice['work_postal'] != '' ) {
            $city .= (($city!='')?',  ':'') . $invoice['work_postal'];
        }
        if( $city != '' ) {
            $waddr[] = $city;
        }
        if( isset($invoice['work_country']) && $invoice['work_country'] != '' ) {
            $waddr[] = $invoice['work_country'];
        }
    }

    //
    // Output the bill to and ship to information
    //
    if( ($ciniki['tenant']['modules']['ciniki.sapos']['flags']&0x020000) > 0 ) {
        $w = array(90, 90);
    } else {
        $w = array(100, 80);
    }
    $lh = 6;
    $pdf->SetFillColor(224);
    $pdf->setCellPadding(2);
    if( count($baddr) > 0 || count($waddr) > 0 ) {
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Bill To:', 1, 0, 'L', 1);
        $border = 1;
        if( ($ciniki['tenant']['modules']['ciniki.sapos']['flags']&0x020000) > 0 ) {
            $pdf->Cell($w[1], $lh, 'Work Location:', 1, 0, 'L', 1);
            $border = 1;
            $diff_lines = (count($baddr) - count($waddr));
            // Add padding so the boxes line up
            if( $diff_lines > 0 ) {
                for($i=0;$i<$diff_lines;$i++) {
                    $waddr[] = " ";
                }
            } elseif( $diff_lines < 0 ) {
                for($i=0;$i<abs($diff_lines);$i++) {
                    $baddr[] = " ";
                }
            }
        }
        $pdf->Ln($lh);  
        $pdf->SetFont('');
        $pdf->setCellPaddings(2, 4, 2, 2);
        $pdf->MultiCell($w[0], $lh, implode("\n", $baddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
        if( ($ciniki['tenant']['modules']['ciniki.sapos']['flags']&0x020000) > 0 ) {
            $pdf->MultiCell($w[1], $lh, implode("\n", $waddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
        }
        $pdf->Ln($lh);
    }
    $pdf->Ln();

    //
    // Add an extra space for invoices with few items
    //
    if( count($baddr) == 0 && count($waddr) == 0 && count($invoice['items']) < 5 ) {
        $pdf->Ln(10);
    }

    //
    // Add the invoice items
    //
    $w = array(100, 50, 30);
    $pdf->SetFillColor(224);
    $pdf->SetFont('', 'B');
    $pdf->SetCellPadding(2);
    $pdf->Cell($w[0], 6, 'Item', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Quantity/Price', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 6, 'Total', 1, 0, 'C', 1);
    $pdf->Ln();
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetFont('');

    $fill=0;
    foreach($invoice['items'] as $item) {
        $discount = '';
        if( $item['item']['discount_amount'] != 0 ) {
            if( $item['item']['unit_discount_amount'] > 0 ) {
                $discount .= '-' . $item['item']['unit_discount_amount_display'] . (($item['item']['quantity']>0&&$item['item']['quantity']!=1)?('x'.$item['item']['quantity']):'');
            }
            if( $item['item']['unit_discount_percentage'] > 0 ) {
                if( $discount != '' ) { 
                    $discount .= ', '; 
                }
                $discount .= '-' . $item['item']['unit_discount_percentage'] . '%';
            }
            $discount .= ' (-' . $item['item']['discount_amount_display'] . ')';
        }
        $lh = ($discount!=''&&($invoice['flags']&0x01)==0)?13:6;
//      $pdf->Cell($w[0], $lh, $item['item']['description'], 1, 0, 'L', $fill, '', 0, false, 'T', 'T');
        if( isset($item['item']['code']) && $item['item']['code'] != '' ) {
            $item['item']['description'] = $item['item']['code'] . ' - ' . $item['item']['description'];
        }
        if( isset($item['item']['notes']) && $item['item']['notes'] != '' ) {
            $item['item']['description'] .= "\n    " . $item['item']['notes'];
        }
        $nlines = $pdf->getNumLines($item['item']['description'], $w[0]);
        if( $nlines == 2 ) {
            $lh = 3+($nlines*5);
        } elseif( $nlines > 2 ) {
            $lh = 2+($nlines*5);
        }
        // Check if we need a page break
        $height = $pdf->getStringHeight($w[0], $item['item']['description']);
        if( $pdf->getY() > ($pdf->getPageHeight() - 30 - $height) ) {
            $pdf->AddPage();
            $pdf->SetFillColor(224);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Item', 1, 0, 'C', 1);
            $pdf->Cell($w[1], 6, 'Quantity/Price', 1, 0, 'C', 1);
            $pdf->Cell($w[2], 6, 'Total', 1, 0, 'C', 1);
            $pdf->Ln();
            $pdf->SetFillColor(236);
            $pdf->SetTextColor(0);
            $pdf->SetFont('');
        }
        $pdf->MultiCell($w[0], $lh, $item['item']['description'], 1, 'L', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $quantity = (($item['item']['quantity']>0&&$item['item']['quantity']!=1)?($item['item']['quantity'].' @ '):'');
        if( ($invoice['flags']&0x01) > 0 ) {
            $pdf->MultiCell($w[1], $lh, $quantity . $item['item']['unit_discounted_amount_display'], 1, 'R', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
        } else if( $discount == '' ) {
//          $pdf->Cell($w[1], $lh, $quantity . $item['item']['unit_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
            $pdf->MultiCell($w[1], $lh, $quantity . $item['item']['unit_amount_display'], 1, 'R', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
        } else {
            $pdf->MultiCell($w[1], $lh, $quantity . '' . $item['item']['unit_amount_display'] . (($discount!='')?"\n" . $discount:""), 1, 'R', $fill, 0, '', '', true, 0, false, true, 0, 'T', false);
        }
//      $pdf->Cell($w[2], $lh, $item['item']['total_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->MultiCell($w[2], $lh, $item['item']['total_amount_display'], 1, 'R', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->Ln(); 
        $fill=!$fill;
    }

    // Check if we need a page break
    if( $pdf->getY() > ($pdf->getPageHeight() - 40) ) {
        $pdf->AddPage();
    }

    //
    // Output the invoice tallies
    //
    $lh = 6;
    $blank_border = '';
    $pdf->Cell($w[0], $lh, '', $blank_border);
    $pdf->Cell($w[1], $lh, 'Subtotal', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
    $pdf->Cell($w[2], $lh, $invoice['subtotal_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
    $pdf->Ln();
    $fill=!$fill;
    if( $invoice['discount_amount'] > 0 ) {
        $discount = '';
        if( $invoice['subtotal_discount_amount'] != 0 ) {
            $discount = '-' . $invoice['subtotal_discount_amount_display'];
        }
        if( $invoice['subtotal_discount_percentage'] != 0 ) {
            $discount .= (($invoice['subtotal_discount_amount']!=0)?', ':'') . '-' . $invoice['subtotal_discount_percentage'] . '%';
        }
        $pdf->Cell($w[0], $lh, '', $blank_border);
        $pdf->Cell($w[1], $lh, 'Overall Discount (' . $discount . ')', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Cell($w[2], $lh, $invoice['subtotal_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Ln();
        $fill=!$fill;
    }

    if( $invoice['shipping_status'] > 0 ) {
        $pdf->Cell($w[0], $lh, '', $blank_border);
        $pdf->Cell($w[1], $lh, 'Shipping & Handling', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Cell($w[2], $lh, '$' . ((isset($invoice['shipping_amount'])&&$invoice['shipping_amount']>0)?$invoice['shipping_amount_display']:'0.00'), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Ln();
        $fill=!$fill;
    }

    //
    // Add taxes
    //
    if( isset($invoice['taxes']) && count($invoice['taxes']) > 0 ) {
        foreach($invoice['taxes'] as $tax) {
            $pdf->Cell($w[0], $lh, '', $blank_border);
            $pdf->Cell($w[1], $lh, $tax['tax']['description'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
            $pdf->Cell($w[2], $lh, $tax['tax']['amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
            $pdf->Ln();
            $fill=!$fill;
        }
    }


    //
    // If paid_amount > 0
    //
    if( $invoice['paid_amount'] > 0 ) {
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, '', $blank_border);
        $pdf->Cell($w[1], $lh, 'Total:', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Cell($w[2], $lh, $invoice['total_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Ln();
        $fill=!$fill;

        $pdf->SetFont('', '');
        $pdf->Cell($w[0], $lh, '', $blank_border);
        $pdf->Cell($w[1], $lh, 'Paid:', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Cell($w[2], $lh, $invoice['paid_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Ln();
        $fill=!$fill;

        $pdf->SetFont('', '');
        $pdf->Cell($w[0], $lh, '', (($blank_border!='')?'LB':''));
        $pdf->Cell($w[1], $lh, 'Balance:', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Cell($w[2], $lh, $invoice['balance_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Ln();
        $fill=!$fill;
    } else {
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, '', (($blank_border!='')?'LB':''));
        $pdf->Cell($w[1], $lh, 'Estimate Total:', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Cell($w[2], $lh, $invoice['total_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
        $pdf->Ln();
        $fill=!$fill;
    }

    //
    // Check if there is a notes to be displayed
    //
    if( isset($invoice['customer_notes']) 
        && $invoice['customer_notes'] != '' ) {
        $pdf->Ln();
        $pdf->SetFont('');
        $pdf->MultiCell(180, 5, $invoice['customer_notes'], 0, 'L');
    }

    //
    // Check if there is a message to be displayed
    //
    if( isset($sapos_settings['quote-bottom-message']) 
        && $sapos_settings['quote-bottom-message'] != '' ) {
        $pdf->Ln();
        $pdf->SetFont('');
        $pdf->MultiCell(180, 5, $sapos_settings['quote-bottom-message'], 0, 'L');
    }

    // ---------------------------------------------------------

    //Close and output PDF document
    $pdf->Output('quote_' . $invoice['invoice_number'] . '.pdf', 'D');

    return array('stat'=>'exit');
}
?>

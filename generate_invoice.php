<?php
require('fpdf/fpdf.php'); 
include 'db_connect.php';  // Incluyendo conexión a la base de datos

class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image('fpdf/logo.png', 165, 12, 35, 35, 'PNG');
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(32, 100, 210);
        $this->Cell(150, 10, iconv("UTF-8", "ISO-8859-1", strtoupper("GYM SOFTWARE")), 0, 0, 'L');
        $this->Ln(9);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->SetMargins(17, 17, 17);
$pdf->AddPage();

// Establecer la codificación de caracteres a utf8mb4
$conn->set_charset("utf8mb4");

if (isset($_GET['registration_id'])) {
    $registration_id = $_GET['registration_id'];

    // Obtener la información del cliente, el pago, el entrenador y el plan
    $qry = $conn->query("SELECT r.*, m.firstname, m.lastname, m.email, m.contact, m.address, e.name AS trainer_name, p.plan AS plan_months, pp.package 
      FROM registration_info r 
      INNER JOIN members m ON m.id = r.member_id 
      INNER JOIN trainers e ON e.id = r.trainer_id
      INNER JOIN plans p ON p.id = r.plan_id 
      INNER JOIN packages pp ON pp.id = r.package_id
      WHERE r.id = $registration_id")->fetch_assoc();

    // Obtener el pago más reciente
    $last_payment = $conn->query("SELECT * FROM payments WHERE registration_id = $registration_id ORDER BY date_created DESC LIMIT 1")->fetch_assoc();

    # Encabezado y datos de la empresa #
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(39, 39, 51);
    $pdf->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", $qry['address']), 0, 0, 'L');
    $pdf->Ln(5);
    $pdf->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono: " . $qry['contact']), 0, 0, 'L');
    $pdf->Ln(5);
    $pdf->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $qry['email']), 0, 0, 'L');
    $pdf->Ln(10);

    # Datos de la factura #
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(30, 7, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión:"), 0, 0);
    $pdf->SetTextColor(97, 97, 97);
    $pdf->Cell(116, 7, iconv("UTF-8", "ISO-8859-1", date("d/m/Y") . " " . date("h:i A")), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(39, 39, 51);
    
    // Generar número de factura único
    $invoice_number = 1;
    $last_invoice = $conn->query("SELECT MAX(invoice_number) as last_invoice FROM invoices WHERE registration_id = $registration_id");
    if ($last_invoice->num_rows > 0) {
        $invoice_number = $last_invoice->fetch_assoc()['last_invoice'] + 1;
    }

    $pdf->Cell(35, 7, iconv("UTF-8", "ISO-8859-1", strtoupper("Factura Nro. " . $invoice_number)), 0, 0, 'C');
    $pdf->Ln(7);

    # Información del cliente #
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(39, 39, 51);
    $pdf->Cell(13, 7, iconv("UTF-8", "ISO-8859-1", "Cliente:"), 0, 0);
    $pdf->SetTextColor(97, 97, 97);
    $pdf->Cell(60, 7, iconv("UTF-8", "ISO-8859-1", $qry['firstname'] . ' ' . $qry['lastname']), 0, 0, 'L');
    $pdf->SetTextColor(39, 39, 51);
    $pdf->Cell(7, 7, iconv("UTF-8", "ISO-8859-1", "Tel:"), 0, 0, 'L');
    $pdf->SetTextColor(97, 97, 97);
    $pdf->Cell(35, 7, iconv("UTF-8", "ISO-8859-1", $qry['contact']), 0, 0);
    $pdf->SetTextColor(39, 39, 51);
    $pdf->Ln(7);

    $pdf->SetTextColor(39, 39, 51);
    $pdf->Cell(6, 7, iconv("UTF-8", "ISO-8859-1", "Dir:"), 0, 0);
    $pdf->SetTextColor(97, 97, 97);
    $pdf->Cell(109, 7, iconv("UTF-8", "ISO-8859-1", $qry['address']), 0, 0);
    $pdf->Ln(7);

    # Zona de descripción #
    $pdf->SetTextColor(39, 39, 51);
    $pdf->Cell(150, 7, iconv("UTF-8", "ISO-8859-1", "Plan: " . $qry['plan_months'] . " Meses"), 0, 0, 'L');
    $pdf->Ln(10);

    # Tabla de productos #
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetFillColor(23, 83, 201);
    $pdf->SetDrawColor(23, 83, 201);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(90, 8, iconv("UTF-8", "ISO-8859-1", "Descripción"), 1, 0, 'C', true);
    $pdf->Cell(25, 8, iconv("UTF-8", "ISO-8859-1", "Precio"), 1, 0, 'C', true);
    $pdf->Cell(32, 8, iconv("UTF-8", "ISO-8859-1", "Subtotal"), 1, 0, 'C', true);
    $pdf->Ln(8);

    # Detalles de la tabla #
    $pdf->SetTextColor(39, 39, 51);
    $pdf->Cell(90, 7, iconv("UTF-8", "ISO-8859-1", $qry['package']), 'L', 0, 'C'); // Paquete
    $pdf->Cell(25, 7, iconv("UTF-8", "ISO-8859-1", "$" . number_format($last_payment['amount'], 2) . " ARS"), 'L', 0, 'C'); // Precio
    $pdf->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "$" . number_format($last_payment['amount'], 2) . " ARS"), 'LR', 0, 'C'); // Subtotal
    $pdf->Ln(7);

    # Totales #
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), 'T', 0, 'C');
    $pdf->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", 'Total:'), 'T', 0, 'C');
    $pdf->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "$" . number_format($last_payment['amount'], 2) . " ARS"), 'T', 0, 'C');

    // Guardar en la base de datos sin el campo 'amount'
    $sql = "INSERT INTO invoices (registration_id, invoice_number, date_created) VALUES ($registration_id, $invoice_number, NOW())";
    $conn->query($sql);

    // Output the PDF
    $pdf->Output();
}
?>
``

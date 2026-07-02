<?php
session_start();
require_once '../config/database.php';
require_once 'fpdf/fpdf.php';

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$studentId = (int) $_SESSION['user_id'];

// Load student details.
$stmt = mysqli_prepare($conn, 'SELECT full_name, matric_no, department, level FROM students WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    die('Student profile could not be found.');
}

// Load registered courses for the slip.
$courses = [];
$totalUnits = 0;
$stmt = mysqli_prepare($conn, 'SELECT c.course_code, c.course_title, c.units, r.status FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ? ORDER BY c.course_code');
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
    $totalUnits += (int) $row['units'];
}
mysqli_stmt_close($stmt);

$pdf = new FPDF();
$pdf->SetTitle('Course Registration Slip');
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, 'CHRISLAND UNIVERSITY (CLU)', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'COURSE REGISTRATION SLIP', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, 'Session: 2025/2026 | Semester: 2nd', 0, 1, 'C');
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Student Details', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, 'Name: ' . $student['full_name'], 0, 1);
$pdf->Cell(0, 8, 'Matric Number: ' . $student['matric_no'], 0, 1);
$pdf->Cell(0, 8, 'Department: ' . $student['department'], 0, 1);
$pdf->Cell(0, 8, 'Level: ' . $student['level'], 0, 1);
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Registered Courses', 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(12, 8, 'S/N', 1);
$pdf->Cell(30, 8, 'Course Code', 1);
$pdf->Cell(95, 8, 'Course Title', 1);
$pdf->Cell(18, 8, 'Units', 1);
$pdf->Cell(35, 8, 'Status', 1, 1);

$pdf->SetFont('Arial', '', 10);
if (empty($courses)) {
    $pdf->Cell(0, 8, 'No courses registered.', 1, 1);
} else {
    foreach ($courses as $index => $course) {
        $pdf->Cell(12, 8, (string) ($index + 1), 1);
        $pdf->Cell(30, 8, $course['course_code'], 1);
        $pdf->Cell(95, 8, $course['course_title'], 1);
        $pdf->Cell(18, 8, (string) $course['units'], 1);
        $pdf->Cell(35, 8, $course['status'], 1, 1);
    }
}

$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'Total Credit Units: ' . $totalUnits, 0, 1);
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 8, 'Date generated: ' . date('F d, Y'), 0, 1);
$pdf->Cell(0, 8, 'This slip is computer generated', 0, 1);

$filename = 'course_registration_slip_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $student['matric_no']) . '.pdf';
$pdf->Output('D', $filename);
?>

<?php include 'db.php'; 

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Optional: restrict POS to certain roles
// Example: allow both admin and cashier, but block others
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'cashier') {
    echo "<h2>Access Denied. You do not have permission to use the POS.</h2>";
    exit;
}

?>

<link rel="stylesheet" type="text/css" href="style.css">

<header>
    <h1>EL Cajarito Cafe POS</h1>
</header>
<nav>
    <a href="inventory.php">Inventory</a>
    <a href="pos.php">POS</a>
    <a href="report.php">Reports</a>
    <a href="login.php">Logout (<?php echo $_SESSION['user']; ?>)</a>
</nav>

<h2>Sales Report</h2>
<form method="GET">
    <input type="date" name="start_date" required>
    <input type="date" name="end_date" required>
    <button type="submit" name="filter">Filter</button>
</form>

<?php
$where = "";
if (isset($_GET['filter'])) {
    $start = $_GET['start_date'];
    $end = $_GET['end_date'];
    $where = "WHERE sale_date BETWEEN '$start' AND '$end'";
}

/* ---------------- TOTAL SALES PER DAY ---------------- */
echo "<h2>Total Sales Per Day</h2>";
$dailyTotals = $conn->query("SELECT DATE(sale_date) as day, SUM(total) as total_sales
                             FROM sales $where
                             GROUP BY DATE(sale_date)
                             ORDER BY day ASC");

echo "<table><tr><th>Date</th><th>Total Sales</th></tr>";
while ($row = $dailyTotals->fetch_assoc()) {
    echo "<tr>
            <td>{$row['day']}</td>
            <td>₱" . number_format($row['total_sales'], 2) . "</td>
          </tr>";
}
echo "</table>";

/* ---------------- DAILY REPORT ---------------- */
echo "<h2>Daily Report (Per Item)</h2>";
$daily = $conn->query("SELECT DATE(sale_date) as day, inventory.item_name, SUM(sales.quantity) as qty, SUM(sales.total) as total_sales
                       FROM sales 
                       JOIN inventory ON sales.item_id=inventory.id
                       $where
                       GROUP BY DATE(sale_date), inventory.item_name
                       ORDER BY day ASC");
echo "<table><tr><th>Date</th><th>Item</th><th>Quantity</th><th>Total Sales</th></tr>";
while ($row = $daily->fetch_assoc()) {
    echo "<tr><td>{$row['day']}</td><td>{$row['item_name']}</td><td>{$row['qty']}</td><td>₱{$row['total_sales']}</td></tr>";
}
echo "</table>";

/* ---------------- WEEKLY REPORT ---------------- */
echo "<h2>Weekly Report (Per Item)</h2>";
$weekly = $conn->query("SELECT YEARWEEK(sale_date,1) as week, inventory.item_name, SUM(sales.quantity) as qty, SUM(sales.total) as total_sales
                        FROM sales 
                        JOIN inventory ON sales.item_id=inventory.id
                        $where
                        GROUP BY YEARWEEK(sale_date,1), inventory.item_name
                        ORDER BY week ASC");
echo "<table><tr><th>Week</th><th>Item</th><th>Quantity</th><th>Total Sales</th></tr>";
while ($row = $weekly->fetch_assoc()) {
    echo "<tr><td>{$row['week']}</td><td>{$row['item_name']}</td><td>{$row['qty']}</td><td>₱{$row['total_sales']}</td></tr>";
}
echo "</table>";

/* ---------------- MONTHLY REPORT ---------------- */
echo "<h2>Monthly Report (Per Item)</h2>";
$monthly = $conn->query("SELECT DATE_FORMAT(sale_date,'%Y-%m') as month, inventory.item_name, SUM(sales.quantity) as qty, SUM(sales.total) as total_sales
                         FROM sales 
                         JOIN inventory ON sales.item_id=inventory.id
                         $where
                         GROUP BY DATE_FORMAT(sale_date,'%Y-%m'), inventory.item_name
                         ORDER BY month ASC");
echo "<table><tr><th>Month</th><th>Item</th><th>Quantity</th><th>Total Sales</th></tr>";
while ($row = $monthly->fetch_assoc()) {
    echo "<tr><td>{$row['month']}</td><td>{$row['item_name']}</td><td>{$row['qty']}</td><td>₱{$row['total_sales']}</td></tr>";
}
echo "</table>";

/* ---------------- PER RECEIPT REPORT ---------------- */
echo "<h2>Totals Per Receipt</h2>";
$perReceipt = $conn->query("SELECT receipt_id, payment_method, SUM(total) as total_sales, MAX(vat) as vat, MAX(service_charge) as service_charge, MAX(sale_date) as sale_date 
                            FROM sales $where GROUP BY receipt_id, payment_method ORDER BY sale_date DESC");
echo "<table><tr><th>Date</th><th>Receipt ID</th><th>Payment Method</th><th>Total Sales</th><th>VAT</th><th>Service Charge</th><th>Action</th></tr>";
while ($row = $perReceipt->fetch_assoc()) {
    echo "<tr>
            <td>{$row['sale_date']}</td>
            <td>{$row['receipt_id']}</td>
            <td>{$row['payment_method']}</td>
            <td>₱{$row['total_sales']}</td>
            <td>₱{$row['vat']}</td>
            <td>₱{$row['service_charge']}</td>
            <td>
                <form method='POST' style='display:inline;'>
                    <input type='hidden' name='receipt_id' value='{$row['receipt_id']}'>
                    <button type='submit' name='reprint'>Reprint</button>
                </form>
            </td>
          </tr>";
}
echo "</table>";

// Handle reprint
if (isset($_POST['reprint'])) {
    $receipt_id = $_POST['receipt_id'];
    $result = $conn->query("SELECT sales.*, inventory.item_name 
                            FROM sales 
                            JOIN inventory ON sales.item_id=inventory.id 
                            WHERE receipt_id='$receipt_id'");
    $total = 0;
    $vat = 0;
    $service_charge = 0;
    $payment = "";
    $items_html = "";
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['total'];
        $total += $subtotal;
        $vat = $row['vat'];
        $service_charge = $row['service_charge'];
        $payment = $row['payment_method'];
        $items_html .= "<p>{$row['item_name']} x {$row['quantity']} = ₱{$subtotal}</p>";
    }
    $grand_total = $total + $service_charge; // exclude VAT from payment

    echo "<script>
        var receiptWindow = window.open('', 'PrintReceipt', 'width=400,height=600');
        receiptWindow.document.write('<html><head><title>Receipt</title></head><body style=\"font-family:Arial;\">');
        receiptWindow.document.write('<h2 style=\"text-align:center;\">EL Cajarito Cafe - Receipt Copy</h2>');
        receiptWindow.document.write('<p>Receipt ID: $receipt_id</p>');
        receiptWindow.document.write('<p>Payment Method: $payment</p>');
        receiptWindow.document.write('$items_html');
        receiptWindow.document.write('<p>Total Items: ₱$total</p>');
        receiptWindow.document.write('<p>Captured VAT (12%): ₱$vat</p>');
        receiptWindow.document.write('<p>Service Charge (10%): ₱$service_charge</p>');
        receiptWindow.document.write('<h3>Grand Total (Payment): ₱$grand_total</h3>');
        receiptWindow.document.write('<button onclick=\"window.print()\">Print</button>');
        receiptWindow.document.write('</body></html>');
        receiptWindow.document.close();
    </script>";
}
?>
